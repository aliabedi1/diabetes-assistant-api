<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlucoseLog
 * 
 * @property int $id
 * @property int $user_id
 * @property float $glucose_amount
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
class GlucoseLog extends BaseModel
{
	use SoftDeletes;
	protected $table = 'glucose_logs';

	protected $casts = [
		'user_id' => 'int',
		'glucose_amount' => 'float',
		'logged_at' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'glucose_amount',
		'logged_at',
		'note'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
