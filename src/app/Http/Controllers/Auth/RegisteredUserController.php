<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterUser $register): RedirectResponse
    {
        // The service is enumeration-safe; the response is identical whether the email was new or
        // already registered (specs/11), so nothing here branches on the result.
        $register->register($request->toData());

        return redirect()->route('register.pending');
    }
}
