<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Mjml\Mjml;

#[Fillable(['subject', 'body_markdown'])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function status(): CampaignStatus
    {
        return match (true) {
            $this->sent_at !== null => CampaignStatus::Sent,
            $this->scheduled_at !== null => CampaignStatus::Scheduled,
            default => CampaignStatus::Draft,
        };
    }

    public function renderHtml(?Subscriber $subscriber = null): string
    {
        $unsubscribeUrl = $subscriber ? URL::signedRoute('unsubscribe', ['subscriber' => $subscriber]) : null;
        $viewUrl = $subscriber ? URL::signedRoute('campaigns.view', ['campaign' => $this, 'subscriber' => $subscriber]) : null;

        $mjml = view('mail.campaign', [
            'subject' => $this->subject,
            'bodyMjml' => $this->bodyMjml(Str::markdown($this->body_markdown)),
            'unsubscribeUrl' => $unsubscribeUrl,
            'viewUrl' => $viewUrl,
        ])->render();

        return Mjml::new()->toHtml($mjml);
    }

    /**
     * Map the Admin's Markdown onto the email layout as MJML blocks: a paragraph
     * holding only an image becomes a full-width fluid <mj-image>, consecutive
     * `###` stories pair up into two side-by-side columns (stacking on mobile),
     * and everything else flows as full-width <mj-text>.
     */
    private function bodyMjml(string $bodyHtml): string
    {
        $dom = HTMLDocument::createFromString("<body>{$bodyHtml}</body>", LIBXML_NOERROR, 'UTF-8');

        /** @var list<array{kind: 'image', src: string, alt: string}|array{kind: 'text'|'story', html: string}> $blocks */
        $blocks = [];

        foreach ($dom->body->childNodes as $node) {
            if (! $node instanceof Element) {
                continue;
            }

            if ($image = $this->standaloneImage($node)) {
                $blocks[] = ['kind' => 'image', ...$image];

                continue;
            }

            $tag = strtolower($node->localName);
            $html = $dom->saveHtml($node);
            $last = array_key_last($blocks);

            if ($tag === 'h3') {
                $blocks[] = ['kind' => 'story', 'html' => $html];
            } elseif ($last !== null && $blocks[$last]['kind'] !== 'image' && ! in_array($tag, ['h1', 'h2'], true)) {
                // flow content continues whatever block is open — a ### story or plain text
                $blocks[$last]['html'] .= "\n".$html;
            } else {
                $blocks[] = ['kind' => 'text', 'html' => $html];
            }
        }

        $sections = [];

        for ($i = 0, $count = count($blocks); $i < $count; $i++) {
            $block = $blocks[$i];

            if ($block['kind'] === 'image') {
                $sections[] = sprintf(
                    '<mj-section><mj-column><mj-image fluid-on-mobile="true" border-radius="10px" src="%s" alt="%s" /></mj-column></mj-section>',
                    htmlspecialchars($block['src'], ENT_QUOTES),
                    htmlspecialchars($block['alt'], ENT_QUOTES),
                );
            } elseif ($block['kind'] === 'story' && ($blocks[$i + 1]['kind'] ?? null) === 'story') {
                $sections[] = '<mj-section>'
                    ."<mj-column padding-right=\"10px\"><mj-text>{$block['html']}</mj-text></mj-column>"
                    ."<mj-column padding-left=\"10px\"><mj-text>{$blocks[$i + 1]['html']}</mj-text></mj-column>"
                    .'</mj-section>';
                $i++;
            } else {
                // plain flow, or a ### story with no partner — full width either way
                $sections[] = "<mj-section><mj-column><mj-text>{$block['html']}</mj-text></mj-column></mj-section>";
            }
        }

        return implode("\n", $sections);
    }

    /**
     * @return array{src: string, alt: string}|null
     */
    private function standaloneImage(Element $node): ?array
    {
        if (strtolower($node->localName) !== 'p' || trim($node->textContent) !== '') {
            return null;
        }

        $children = iterator_to_array($node->childNodes);
        $elements = array_values(array_filter($children, fn ($child): bool => $child instanceof Element));

        if (count($elements) !== 1 || strtolower($elements[0]->localName) !== 'img') {
            return null;
        }

        return [
            'src' => $elements[0]->getAttribute('src') ?? '',
            'alt' => $elements[0]->getAttribute('alt') ?? '',
        ];
    }
}
