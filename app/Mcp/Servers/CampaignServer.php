<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateCampaignTool;
use App\Mcp\Tools\DeleteCampaignTool;
use App\Mcp\Tools\GetCampaignTool;
use App\Mcp\Tools\IngestImageTool;
use App\Mcp\Tools\ListCampaignsTool;
use App\Mcp\Tools\TestSendTool;
use App\Mcp\Tools\UpdateCampaignTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Solamnia Campaigns')]
#[Version('1.0.0')]
#[Instructions('Author draft newsletter Campaigns for the Solamnia portal: create, revise, and delete drafts, ingest images for embedding, and test-send to the admin. Draft-only by design — scheduling and sending are human actions in the admin panel, and sent Campaigns are immutable.')]
class CampaignServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        CreateCampaignTool::class,
        UpdateCampaignTool::class,
        DeleteCampaignTool::class,
        GetCampaignTool::class,
        ListCampaignsTool::class,
        IngestImageTool::class,
        TestSendTool::class,
    ];
}
