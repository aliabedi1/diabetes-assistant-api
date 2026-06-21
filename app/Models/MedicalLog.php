<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class MedicalLog
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $medicine_id
 * @property float $amount
 * @property string|null $type
 * @property Carbon $logged_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property User $user
 * @property Medicine|null $medicine
 */
class MedicalLog extends BaseModel
{
    use SoftDeletes;

    protected $table = 'medical_logs';

    protected $casts = [
        'user_id' => 'int',
        'medicine_id' => 'int',
        'amount' => 'float',
        'logged_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'medicine_id',
        'amount',
        'type',
        'logged_at',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
