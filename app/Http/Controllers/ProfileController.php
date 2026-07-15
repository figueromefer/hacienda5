<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, GoogleCalendarService $google): View
    {
        $connection = $request->user()->googleCalendarConnection;
        $calendars = [];
        $calendarError = null;
        if ($connection) {
            try {
                $calendars = $google->calendars($connection);
            } catch (Throwable $exception) {
                report($exception);
                $calendarError = 'No fue posible consultar los calendarios de Google en este momento.';
            }
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'googleConnection' => $connection,
            'googleCalendars' => $calendars,
            'googleCalendarError' => $calendarError,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
