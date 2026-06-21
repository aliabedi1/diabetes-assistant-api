<?php

namespace App\Http\Controllers\Api\V1\Glucose;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Glucose\GlucoseLog\GlucoseChartRequest;
use App\Http\Requests\Api\V1\Glucose\GlucoseLog\GlucoseLogIndexRequest;
use App\Http\Requests\Api\V1\Glucose\GlucoseLog\GlucoseLogStoreRequest;
use App\Http\Resources\Api\V1\Glucose\GlucoseLogResource;
use App\Http\Resources\PaginationResource;
use App\Models\GlucoseLog;
use App\Models\GlucoseLogMonthly;
use App\Models\GlucoseLogYearly;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class GlucoseLogController extends Controller
{
    public function index(GlucoseLogIndexRequest $request)
    {
        $userId = $request->user()->id;

        $data = Cache::remember(
            "logs:user:{$userId}",
            now()->addDay(),
            function () use ($request) {
                $paginator = $request->user()
                    ->glucose_logs()
                    ->orderBy('logged_at')
                    ->paginate();

                return (new PaginationResource(
                    GlucoseLogResource::collection($paginator)
                ))->toArray($request);
            }
        );

        return Response::success(data: $data);
    }

    public function store(GlucoseLogStoreRequest $request)
    {
        $log = GlucoseLog::query()->create([
            'user_id' => $request->user()->id,
            'glucose_amount' => $request->validated('glucose_amount'),
            'logged_at' => $request->validated('logged_at') ?? now(),
            'note' => $request->validated('note'),
        ]);

        Cache::forget("logs:user:{$request->user()->id}");

        return Response::store(new GlucoseLogResource($log));
    }

    public function chart(GlucoseChartRequest $request)
    {
        $userId = $request->user()->id;
        $tz = config('app.timezone');

        $from = Carbon::parse($request->validated('from'), $tz)->startOfDay();
        $to = Carbon::parse($request->validated('to') ?? now($tz)->toDateString(), $tz)->endOfDay();

        $currentMonthStart = now($tz)->startOfMonth();

        // Determine point granularity based on range width.
        // ≤ 7 days  → individual readings; ≤ 366 days → daily buckets; else → monthly buckets.
        $diffDays = (int) $from->diffInDays($to);
        $granularity = match (true) {
            $diffDays <= 7 => 'reading',
            $diffDays <= 366 => 'day',
            default => 'month',
        };

        $points = collect();

        // ── Current in-progress month: always query raw rows ──────────────
        if ($to >= $currentMonthStart) {
            $rawFrom = Carbon::max($from, $currentMonthStart);

            $raw = GlucoseLog::where('user_id', $userId)
                ->whereBetween('logged_at', [$rawFrom, $to])
                ->orderBy('logged_at')
                ->get(['glucose_amount', 'logged_at']);

            $points = $points->merge($this->aggregateRaw($raw, $granularity, $tz));
        }

        // ── Completed past months: use snapshots ──────────────────────────
        if ($from < $currentMonthStart) {
            $pastTo = Carbon::min($to, $currentMonthStart->copy()->subSecond());

            if ($granularity === 'month') {
                $points = $points->merge(
                    $this->pointsFromYearRange($userId, $from, $pastTo)
                );
            } else {
                $points = $points->merge(
                    $this->pointsFromMonthRange($userId, $from, $pastTo)
                );
            }
        }

        return Response::success([
            'granularity' => $granularity,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'points' => $points->sortBy('period')->values()->all(),
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function aggregateRaw(Collection $raw, string $granularity, string $tz): Collection
    {
        if ($raw->isEmpty()) {
            return collect();
        }

        if ($granularity === 'reading') {
            return $raw->map(fn ($l) => [
                'period' => $l->logged_at->toIso8601String(),
                'count' => 1,
                'avg' => round((float) $l->glucose_amount, 2),
                'min' => (float) $l->glucose_amount,
                'max' => (float) $l->glucose_amount,
            ]);
        }

        // Aggregate to daily buckets using the app's configured timezone.
        return $raw
            ->groupBy(fn ($l) => $l->logged_at->timezone($tz)->toDateString())
            ->map(function ($group, $date) {
                $vals = $group->pluck('glucose_amount')->map(fn ($v) => (float) $v);

                return [
                    'period' => $date,
                    'count' => $vals->count(),
                    'avg' => round($vals->average(), 2),
                    'min' => $vals->min(),
                    'max' => $vals->max(),
                ];
            })
            ->values();
    }

    /**
     * Day-granularity points from monthly snapshots, expanded to per-day entries.
     * Falls back gracefully if a monthly snapshot is missing for a month in range.
     */
    private function pointsFromMonthRange(int $userId, Carbon $from, Carbon $pastTo): Collection
    {
        $snaps = GlucoseLogMonthly::where('user_id', $userId)
            ->where(function ($q) use ($from) {
                $q->where('year', '>', $from->year)
                    ->orWhere(fn ($q2) => $q2->where('year', $from->year)->where('month', '>=', $from->month));
            })
            ->where(function ($q) use ($pastTo) {
                $q->where('year', '<', $pastTo->year)
                    ->orWhere(fn ($q2) => $q2->where('year', $pastTo->year)->where('month', '<=', $pastTo->month));
            })
            ->get();

        $fromDate = $from->toDateString();
        $toDate = $pastTo->toDateString();

        $points = collect();

        foreach ($snaps as $snap) {
            foreach (($snap->data['days'] ?? []) as $day => $stats) {
                $date = sprintf('%d-%02d-%02d', $snap->year, $snap->month, (int) $day);
                if ($date >= $fromDate && $date <= $toDate) {
                    $points->push([
                        'period' => $date,
                        'count' => (int) $stats['count'],
                        'avg' => round((float) $stats['avg'], 2),
                        'min' => (float) $stats['min'],
                        'max' => (float) $stats['max'],
                    ]);
                }
            }
        }

        return $points;
    }

    /**
     * Month-granularity points from yearly + monthly snapshots.
     * Yearly snapshots are used for full completed years; monthly summaries
     * fill in any year that lacks a yearly snapshot.
     */
    private function pointsFromYearRange(int $userId, Carbon $from, Carbon $pastTo): Collection
    {
        $fromYear = $from->year;
        $toYear = $pastTo->year;

        $yearlySnaps = GlucoseLogYearly::where('user_id', $userId)
            ->whereBetween('year', [$fromYear, $toYear])
            ->get()
            ->keyBy('year');

        $monthlySnaps = GlucoseLogMonthly::where('user_id', $userId)
            ->whereBetween('year', [$fromYear, $toYear])
            ->get()
            ->groupBy('year')
            ->map(fn ($g) => $g->keyBy('month'));

        $points = collect();

        for ($y = $fromYear; $y <= $toYear; $y++) {
            $yFromMonth = $y === $fromYear ? $from->month : 1;
            $yToMonth = $y === $toYear ? $pastTo->month : 12;
            $fullYear = $yFromMonth === 1 && $yToMonth === 12;

            if ($fullYear && isset($yearlySnaps[$y])) {
                foreach (($yearlySnaps[$y]->data['months'] ?? []) as $m => $stats) {
                    $points->push([
                        'period' => sprintf('%d-%02d', $y, (int) $m),
                        'count' => (int) $stats['count'],
                        'avg' => round((float) $stats['avg'], 2),
                        'min' => (float) $stats['min'],
                        'max' => (float) $stats['max'],
                    ]);
                }

                continue;
            }

            // No complete yearly snapshot — fall back to per-month summaries.
            $yearMonthly = $monthlySnaps[$y] ?? collect();
            for ($m = $yFromMonth; $m <= $yToMonth; $m++) {
                if (isset($yearMonthly[$m])) {
                    $summary = $yearMonthly[$m]->data['summary'];
                    $points->push([
                        'period' => sprintf('%d-%02d', $y, $m),
                        'count' => (int) $summary['count'],
                        'avg' => round((float) $summary['avg'], 2),
                        'min' => (float) $summary['min'],
                        'max' => (float) $summary['max'],
                    ]);
                }
            }
        }

        return $points;
    }
}
