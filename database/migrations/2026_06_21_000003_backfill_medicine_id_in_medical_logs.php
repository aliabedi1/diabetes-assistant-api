<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Idempotent: only processes rows where medicine_id IS NULL and type IS NOT NULL.
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('medical_logs')
            ->select('user_id', 'type')
            ->whereNotNull('type')
            ->whereNull('medicine_id')
            ->whereNull('deleted_at')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            $medicine = DB::table('medicines')
                ->where('user_id', $row->user_id)
                ->whereRaw('LOWER(name_en) = ?', [strtolower($row->type)])
                ->whereNull('deleted_at')
                ->first();

            if ($medicine) {
                $medicineId = $medicine->id;
            } else {
                $medicineId = DB::table('medicines')->insertGetId([
                    'user_id' => $row->user_id,
                    'name_en' => $row->type,
                    'name_fa' => null,
                    'is_global' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('medical_logs')
                ->where('user_id', $row->user_id)
                ->where('type', $row->type)
                ->whereNull('medicine_id')
                ->update(['medicine_id' => $medicineId]);
        }
    }

    public function down(): void
    {
        // Reversing a data migration is not safe; no-op.
    }
};
