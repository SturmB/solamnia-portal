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

#[Name('delete_campaign')]
#[Description('Delete a draft or scheduled Campaign. Sent Campaigns are send history and can never be deleted.')]
class DeleteCampaignTool extends Tool
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
            return Response::error("Campaign {$campaign->id} has already been sent. Send history is never deleted.");
        }

        $campaign->delete();

        return Response::text("Deleted Campaign {$campaign->id} (\"{$campaign->subject}\").");
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
                ->description('The Campaign id to delete.')
                ->required(),
        ];
    }
}
