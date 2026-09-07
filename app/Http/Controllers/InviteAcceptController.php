<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InviteAcceptController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse|View
    {
        $request->merge(['username' => Str::lower($request->string('username'))]);
        $request->validate([
            'username' => [
                'required',
                'string',
                'regex:/^[a-z][a-z0-9._-]{2,31}$/',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ], [
            'username.regex' => 'The username must start with a lowercase letter and contain only lowercase letters, numbers, dots, underscores, or hyphens.',
        ]);

        return back();
    }
}
