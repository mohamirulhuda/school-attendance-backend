<?php

namespace App\Services\Auth;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as GoogleUser;

class GoogleAuthenticationService
{
    public function authenticate(GoogleUser $googleUser): User
    {
        $teacher = Teacher::query()
            ->where('email', $googleUser->getEmail())
            ->firstOrFail();

        $user = User::query()->firstOrCreate(
            [
                'email' => $teacher->email,
            ],
            [
                'name' => $teacher->name,
            ],
        );

        if ($user->wasRecentlyCreated) {
            $user->assignRole('guru_mapel');
        }

        Auth::login($user, true);

        request()->session()->regenerate();

        return $user;
    }
}
