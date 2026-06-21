<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Medicine
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $name_en
 * @property string|null $name_fa
 * @property bool $is_global
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property User|null $user
 * @property Collection|MedicalLog[] $medical_logs
 */
class Medicine extends BaseModel
{
    use SoftDeletes;

    protected $table = 'medicines';

    protected $casts = [
        'user_id' => 'int',
        'is_global' => 'bool',
    ];

    protected $fillable = [
        'user_id',
        'name_en',
        'name_fa',
        'is_global',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medical_logs(): HasMany
    {
        return $this->hasMany(MedicalLog::class);
    }
}
