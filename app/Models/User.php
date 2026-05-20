<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int                     $id
 * @property string                  $username
 * @property string                  $name
 * @property string                  $last_name
 * @property string                  $email
 * @property Carbon|null             $email_verified_at
 * @property string                  $password
 * @property string|null             $remember_token
 * @property Carbon|null             $created_at
 * @property Carbon|null             $updated_at
 * @property string|null             $deleted_at
 *
 * @property Collection|GlucoseLog[] $glucoseLogs
 * @property Collection|MedicalLog[] $medicalLogs
 * @property Collection|Role[]       $roles
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use SoftDeletes;
    use Notifiable;
    use HasApiTokens;

    protected $table = 'users';
    public static $snakeAttributes = false;

    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $fillable = [
        'username',
        'name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'remember_token'
    ];


    public function glucoseLogs()
    {
        return $this->hasMany(GlucoseLog::class);
    }


    public function medicalLogs()
    {
        return $this->hasMany(MedicalLog::class);
    }


    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('id', 'deleted_at')
            ->withTimestamps();
    }
}
