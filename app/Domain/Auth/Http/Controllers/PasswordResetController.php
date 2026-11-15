<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::withoutInstitutionScope()
            ->where('email', $request->email)
            ->first();

        if (! $user) {
            // For security, do not leak whether email exists
            return back()->with('status', 'If an account exists for this email, a reset link has been dispatched.');
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        // Dispatched outside of any transaction
        // In production, Mail::to($user->email)->send(new ResetPasswordNotification($token));

        return back()->with('status', 'If an account exists for this email, a reset link has been dispatched.')
            ->with('dev_reset_token', $token); // Helpful in test / dev environment
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid or expired password reset token.',
            ]);
        }

        // Check token expiration (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            throw ValidationException::withMessages([
                'email' => 'Password reset token has expired.',
            ]);
        }

        $user = User::withoutInstitutionScope()
            ->where('email', $request->email)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => 'User not found.']);
        }

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
            ]);

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        });

        return redirect()->route('login')->with('status', 'Your password has been successfully reset. Please log in.');
    }
}
