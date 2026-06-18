<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\LogOutRequest;
use App\Http\Requests\Api\V1\Auth\MeRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\Authentication\AuthResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\Role;
use App\Models\User;
use Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::query()->create(array_merge($request->validated(),[
            'password' => bcrypt($request->input('password')),
        ]));

        $role = Role::query()
            ->where('slug', 'user')
            ->first();

        if ($role) {
            $user->roles()->attach($role->id);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return Response::store(
            new AuthResource((object)[
                'user'  => $user,
                'token' => $token,
            ])
        );
    }


    public function login(LoginRequest $request)
    {
        $user = User::query()
            ->where('email', $request->input('login'))
            ->orWhere('username', $request->input('login'))
            ->first();

        if ( ! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials'],
            ]);
        }

        return Response::success(
            AuthResource::make([
                'user'  => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ])
        );
    }


    public function me(MeRequest $request)
    {
        return Response::success(
            UserResource::make(
                $request->user()->load('roles')
            )
        );
    }


    public function logout(LogOutRequest $request)
    {
        $request->user()->tokens()->delete();

        return Response::destroy('Logged out successfully');
    }
}
