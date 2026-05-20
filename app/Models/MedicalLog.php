<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class MedicalLog
 * 
 * @property int $id
 * @property int $user_id
 * @property float $amount
 * @property string|null $type
 * @property Carbon $logged_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class MedicalLog extends Model
{
	use SoftDeletes;
	protected $table = 'medical_logs';
	public static $snakeAttributes = false;

	protected $casts = [
		'user_id' => 'int',
		'amount' => 'float',
		'logged_at' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'amount',
		'type',
		'logged_at',
		'note'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
