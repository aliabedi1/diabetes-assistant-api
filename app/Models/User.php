<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
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
 * @property Collection|GlucoseLog[] $glucose_logs
 * @property Collection|MedicalLog[] $medical_logs
 * @property Collection|Role[]       $roles
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use SoftDeletes;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

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


    public function glucose_logs()
    {
        return $this->hasMany(GlucoseLog::class);
    }


    public function medical_logs()
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
