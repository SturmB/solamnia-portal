<?php

namespace App\Mcp\Tools;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('update_campaign')]
#[Description('Revise the subject and/or Markdown body of an existing draft or scheduled Campaign. Sent Campaigns are immutable.')]
class UpdateCampaignTool extends Tool
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

        if ($campaign->status() === CampaignStatus::Sent) {
            return Response::error("Campaign {$campaign->id} has already been sent and is immutable. Create a new Campaign instead.");
        }

        $rules = Campaign::rules();

        $campaign->update($request->validate([
            'subject' => ['sometimes', ...$rules['subject']],
            'body_markdown' => ['sometimes', ...$rules['body_markdown']],
        ]));

        return Response::text(json_encode([
            'id' => $campaign->id,
            'status' => $campaign->status()->value,
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
                ->description('The Campaign id to revise.')
                ->required(),

            'subject' => $schema->string()
                ->description('New email subject line (max 255 characters).'),

            'body_markdown' => $schema->string()
                ->description('New Campaign body as Markdown, replacing the current body entirely.'),
        ];
    }
}
