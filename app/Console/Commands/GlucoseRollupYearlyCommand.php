<?php

namespace App\Console\Commands;

use App\Models\GlucoseLogMonthly;
use App\Models\GlucoseLogYearly;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('glucose:rollup-yearly')]
#[Description('Compact 12 monthly snapshots into yearly JSON snapshots.')]
class GlucoseRollupYearlyCommand extends Command
{
    public function handle(): int
    {
        $currentYear = now(config('app.timezone'))->year;

        // Find (user_id, year) pairs where all 12 monthly snapshots exist
        // but no yearly snapshot has been created yet.
        $candidates = GlucoseLogMonthly::selectRaw('user_id, year, COUNT(*) AS month_count')
            ->where('year', '<', $currentYear)
            ->groupBy('user_id', 'year')
            ->having('month_count', '=', 12)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No complete years to roll up.');

            return Command::SUCCESS;
        }

        $existingKeys = array_flip(
            GlucoseLogYearly::select(['user_id', 'year'])
                ->get()
                ->map(fn ($r) => "{$r->user_id}-{$r->year}")
                ->all()
        );

        $processed = 0;
        $skipped = 0;

        foreach ($candidates as $row) {
            $key = "{$row->user_id}-{$row->year}";

            if (isset($existingKeys[$key])) {
                $skipped++;

                continue;
            }

            DB::transaction(function () use ($row, &$processed) {
                $monthlySnaps = GlucoseLogMonthly::where('user_id', $row->user_id)
                    ->where('year', $row->year)
                    ->orderBy('month')
                    ->get();

                $months = [];
                $totalCount = 0;
                $weightedSum = 0.0;
                $globalMin = null;
                $globalMax = null;

                foreach ($monthlySnaps as $snap) {
                    $s = $snap->data['summary'];
                    $count = (int) $s['count'];
                    $avg = (float) $s['avg'];

                    $months[(string) $snap->month] = [
                        'count' => $count,
                        'avg' => round($avg, 2),
                        'min' => (float) $s['min'],
                        'max' => (float) $s['max'],
                    ];

                    $totalCount += $count;
                    $weightedSum += $count * $avg;
                    $globalMin = $globalMin === null ? (float) $s['min'] : min($globalMin, (float) $s['min']);
                    $globalMax = $globalMax === null ? (float) $s['max'] : max($globalMax, (float) $s['max']);
                }

                $globalAvg = $totalCount > 0 ? round($weightedSum / $totalCount, 2) : 0.0;

                GlucoseLogYearly::updateOrCreate(
                    ['user_id' => $row->user_id, 'year' => $row->year],
                    [
                        'data' => [
                            'months' => $months,
                            'summary' => [
                                'count' => $totalCount,
                                'avg' => $globalAvg,
                                'min' => $globalMin ?? 0.0,
                                'max' => $globalMax ?? 0.0,
                            ],
                        ],
                        'entries_count' => $totalCount,
                    ]
                );

                $processed++;
            });
        }

        $this->info("Yearly rollup complete. Processed: {$processed}, already up-to-date: {$skipped}.");

        return Command::SUCCESS;
    }
}
