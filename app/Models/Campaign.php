<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Mjml\Mjml;

#[Fillable(['subject', 'body_markdown'])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    public function renderHtml(): string
    {
        $bodyHtml = Str::markdown($this->body_markdown);

        // ponytail: entire Markdown-HTML fragment goes into one <mj-text>; ample for a
        // prose newsletter. If rich per-block layout is ever needed, parse to MJML components.
        $mjml = view('mail.campaign', [
            'subject' => $this->subject,
            'bodyHtml' => $bodyHtml,
        ])->render();

        return Mjml::new()->toHtml($mjml);
    }
}
