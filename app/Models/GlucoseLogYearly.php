<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlucoseLogYearly
 *
 * @property int $id
 * @property int $user_id
 * @property int $year
 * @property array $data
 * @property int $entries_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property User $user
 */
class GlucoseLogYearly extends BaseModel
{
    use SoftDeletes;

    protected $table = 'glucose_log_yearly';

    protected $casts = [
        'user_id' => 'int',
        'year' => 'int',
        'data' => 'array',
        'entries_count' => 'int',
    ];

    protected $fillable = [
        'user_id',
        'year',
        'data',
        'entries_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
