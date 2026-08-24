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

#[Name('create_campaign')]
#[Description('Create a draft newsletter Campaign from a subject and Markdown body. Drafts only — scheduling and sending are human actions in the admin panel.')]
class CreateCampaignTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $campaign = Campaign::create($request->validate(Campaign::rules()));

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
            'subject' => $schema->string()
                ->description('The email subject line (max 255 characters).')
                ->required(),

            'body_markdown' => $schema->string()
                ->description('The Campaign body as Markdown. Standalone image paragraphs become full-width images; consecutive ### stories pair into columns.')
                ->required(),
        ];
    }
}
