<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

class DismissNotificationController extends Controller
{
    public function __invoke(#[CurrentUser] User $user, string $id): RedirectResponse
    {
        $user->notifications()->findOrFail($id)->markAsRead();

        return to_route('dashboard');
    }
}
