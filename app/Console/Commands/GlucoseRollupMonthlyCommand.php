<?php

namespace App\Console\Commands;

use App\Models\GlucoseLog;
use App\Models\GlucoseLogMonthly;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('glucose:rollup-monthly')]
#[Description('Roll up completed months of glucose logs into monthly JSON snapshots.')]
class GlucoseRollupMonthlyCommand extends Command
{
    public function handle(): int
    {
        $tz = config('app.timezone');
        $currentMonthStart = now($tz)->startOfMonth();

        // All (user_id, year, month) groups that have raw rows in completed months.
        $candidates = DB::table('glucose_logs')
            ->selectRaw('user_id, YEAR(logged_at) AS year, MONTH(logged_at) AS month')
            ->whereNull('deleted_at')
            ->where('logged_at', '<', $currentMonthStart)
            ->groupByRaw('user_id, YEAR(logged_at), MONTH(logged_at)')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No completed months to roll up.');

            return Command::SUCCESS;
        }

        // Build a set of already-snapshotted keys for O(1) lookup.
        $existingKeys = array_flip(
            GlucoseLogMonthly::select(['user_id', 'year', 'month'])
                ->get()
                ->map(fn ($r) => "{$r->user_id}-{$r->year}-{$r->month}")
                ->all()
        );

        $processed = 0;
        $skipped = 0;

        foreach ($candidates as $row) {
            $key = "{$row->user_id}-{$row->year}-{$row->month}";

            if (isset($existingKeys[$key])) {
                $skipped++;

                continue;
            }

            DB::transaction(function () use ($row, &$processed) {
                $logs = GlucoseLog::where('user_id', $row->user_id)
                    ->whereNull('deleted_at')
                    ->whereRaw('YEAR(logged_at) = ? AND MONTH(logged_at) = ?', [$row->year, $row->month])
                    ->get(['glucose_amount', 'logged_at']);

                if ($logs->isEmpty()) {
                    return;
                }

                // Group readings by UTC day-of-month.
                $days = [];
                foreach ($logs->groupBy(fn ($l) => $l->logged_at->day) as $day => $group) {
                    $vals = $group->pluck('glucose_amount')->map(fn ($v) => (float) $v);
                    $days[(string) $day] = [
                        'count' => $vals->count(),
                        'avg' => round($vals->average(), 2),
                        'min' => $vals->min(),
                        'max' => $vals->max(),
                    ];
                }

                $all = $logs->pluck('glucose_amount')->map(fn ($v) => (float) $v);

                GlucoseLogMonthly::updateOrCreate(
                    ['user_id' => $row->user_id, 'year' => $row->year, 'month' => $row->month],
                    [
                        'data' => [
                            'days' => $days,
                            'summary' => [
                                'count' => $all->count(),
                                'avg' => round($all->average(), 2),
                                'min' => $all->min(),
                                'max' => $all->max(),
                            ],
                        ],
                        'entries_count' => $all->count(),
                    ]
                );

                $processed++;
            });
        }

        $this->info("Monthly rollup complete. Processed: {$processed}, already up-to-date: {$skipped}.");

        return Command::SUCCESS;
    }
}
