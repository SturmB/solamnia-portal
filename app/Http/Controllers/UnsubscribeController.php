<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnsubscribeController extends Controller
{
    /**
     * Opt a Subscriber out via a signed, login-free link.
     *
     * Serves both verbs on one signed URL: a GET when a reader clicks the
     * footer link (answered with a confirmation page), and a POST when a mail
     * client fires the RFC 8058 `List-Unsubscribe: One-Click` header (answered
     * with an empty 204, since no page is shown to a human).
     */
    public function __invoke(Request $request, Subscriber $subscriber): View|Response
    {
        $subscriber->update(['unsubscribed_at' => now()]);

        if ($request->isMethod('post')) {
            return response()->noContent();
        }

        return view('unsubscribe');
    }
}
