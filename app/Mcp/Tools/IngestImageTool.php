<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\File;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('ingest_image')]
#[Description('Store a local image file on the public disk under campaigns/ — the same place the panel editor uploads to — and return its public URL for embedding in body_markdown.')]
class IngestImageTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate(['source_path' => ['required', 'string']]);

        $sourcePath = $validated['source_path'];

        if (! is_file($sourcePath)) {
            return Response::error("No readable file at {$sourcePath}. The path must be visible to the app process.");
        }

        $file = new File($sourcePath);
        $mimeType = $file->getMimeType();

        if ($mimeType === null || ! str_starts_with($mimeType, 'image/')) {
            return Response::error("The file at {$sourcePath} is not an image (detected: ".($mimeType ?? 'unknown').').');
        }

        $storedPath = Storage::disk('public')->putFile('campaigns', $file, 'public');

        if ($storedPath === false) {
            return Response::error("Failed to store {$sourcePath} on the public disk.");
        }

        $url = Storage::disk('public')->url($storedPath);

        return Response::text(json_encode([
            'path' => $storedPath,
            'url' => $url,
            'markdown' => "![]({$url})",
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
            'source_path' => $schema->string()
                ->description('Absolute path to an image file readable by the app process.')
                ->required(),
        ];
    }
}
