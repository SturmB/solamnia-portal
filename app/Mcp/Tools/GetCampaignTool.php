<?php

namespace App\Mcp\Tools;

use App\Models\Campaign;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_campaign')]
#[Description('Fetch one Campaign in full — subject, Markdown body, derived status, and timestamps — so it can be inspected before revising.')]
class GetCampaignTool extends Tool
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

        return Response::text(json_encode([
            'id' => $campaign->id,
            'subject' => $campaign->subject,
            'body_markdown' => $campaign->body_markdown,
            'status' => $campaign->status()->value,
            'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
            'sent_at' => $campaign->sent_at?->toIso8601String(),
            'created_at' => $campaign->created_at?->toIso8601String(),
            'updated_at' => $campaign->updated_at?->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES));
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
                ->description('The Campaign id to fetch.')
                ->required(),
        ];
    }
}
