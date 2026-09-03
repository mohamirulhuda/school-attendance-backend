<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

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

        return response()->json([
            'authenticated' => Auth::check(),
            'user' => $user,
        ]);
    }
}
