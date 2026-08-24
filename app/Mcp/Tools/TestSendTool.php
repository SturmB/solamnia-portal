<?php

namespace App\Mcp\Tools;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Mail;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('test_send')]
#[Description('Send a Campaign through the real branded MJML pipeline to the admin Member\'s email as a test. Does not touch scheduling or real recipients.')]
class TestSendTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $campaign = Campaign::find($request->integer('id'));

        if (! $campaign instanceof Campaign) {
            return Response::error("No Campaign with id {$request->integer('id')}.");
        }

        // Stdio has no authenticated user, so the recipient is looked up: the
        // admin — the same account the panel's own "Send test to myself" targets.
        $admin = User::query()->where('is_admin', true)->first();

        if ($admin === null) {
            return Response::error('No admin user found to receive the test send.');
        }

        Mail::to($admin)->send(new CampaignMail($campaign));

        return Response::text("Test of Campaign {$campaign->id} sent to {$admin->email}.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The Campaign id to test-send.')
                ->required(),
        ];
    }
}
