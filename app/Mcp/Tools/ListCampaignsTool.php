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

#[Name('list_campaigns')]
#[Description('List all Campaigns (newest first) with id, subject, derived status, and timestamps. Use get_campaign for the full body.')]
class ListCampaignsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $campaigns = Campaign::query()
            ->latest()
            ->get()
            ->map(fn (Campaign $campaign): array => [
                'id' => $campaign->id,
                'subject' => $campaign->subject,
                'status' => $campaign->status()->value,
                'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
                'sent_at' => $campaign->sent_at?->toIso8601String(),
            ]);

        return Response::text(json_encode($campaigns, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
