<?php

use App\Models\Campaign;

it('renders the Markdown body into branded HTML', function () {
    $campaign = Campaign::factory()->make([
        'subject' => 'Spring Update',
        'body_markdown' => "## Big news\n\nHello friends. [Reply](mailto:admin@solamnia.tv).",
    ]);

    $html = $campaign->renderHtml();

    expect($html)
        ->toContain('Big news')                         // heading text .tv)survives markdown→html
        ->toContain('Hello friends')                    // paragraph survives
        ->toContain('href="mailto:admin@solamnia.tv"')  // mailto CTA renders as a real link
        ->toContain('<html');                           // MJML compiled a full document
});

it('renders the Aurora treatment: night ground, violet links, gradient band, no legacy brand or OKLCH', function () {
    $html = Campaign::factory()->make()->renderHtml();

    expect($html)
        ->not->toContain('#400080')            // pre-Aurora purple is gone
        ->not->toContain('Marcellus')          // pre-Aurora display face is gone
        ->not->toContain('Instrument Sans')    // pre-Aurora body face is gone
        ->not->toContain('oklch')              // email clients get hex/rgb only
        ->toContain('Clash Display')           // masthead face
        ->toContain('Avenir Next')             // its email-safe fallback
        ->toContain('#010813')                 // night ground
        ->toContain('#cbaaff')                 // lifted violet, the only link color
        ->toContain('radial-gradient');        // the pre-baked aurora band (no shader, no JS)
});

it('promotes a standalone image paragraph to a full-width fluid image block', function () {
    $campaign = Campaign::factory()->make([
        'body_markdown' => "## The vault doubled\n\n![The rack, fully lit](https://solamnia.tv/img/rack.webp)\n\nMore room for 4K.",
    ]);

    $html = $campaign->renderHtml();

    expect($html)
        ->toContain('src="https://solamnia.tv/img/rack.webp"')
        ->toContain('alt="The rack, fully lit"')
        ->toContain('width="536"')                  // full column width (600 − 2×32 gutter), not an inline prose <img>
        ->toContain('More room for 4K');            // surrounding prose still renders
});

it('promotes a linked standalone image to a full-width image block that keeps its link', function () {
    $campaign = Campaign::factory()->make([
        'body_markdown' => "Intro.\n\n[![The rack](https://solamnia.tv/img/rack.webp)](https://solamnia.tv/kb/vault)\n\nOutro.",
    ]);

    $html = $campaign->renderHtml();

    expect($html)
        ->toContain('src="https://solamnia.tv/img/rack.webp"')
        ->toContain('width="536"')                              // full-width block, not inline prose
        ->toMatch('/<a\s+href="https:\/\/solamnia\.tv\/kb\/vault"[^>]*>\s*<img/');  // link survives around the image
});

it('pairs consecutive ### stories into a two-column row while the lead story stays full-width', function () {
    $campaign = Campaign::factory()->make([
        'body_markdown' => implode("\n\n", [
            '## The vault doubled',
            'Lead story prose, full width.',
            '### Immich learned faces',
            'Name the faces it found.',
            '### Movie night, democratized',
            'Overseerr is open to everyone.',
        ]),
    ]);

    $html = $campaign->renderHtml();

    expect($html)
        ->toContain('mj-column-per-50')             // the two ### stories sit side by side (and stack under 480px)
        ->toContain('Immich learned faces')
        ->toContain('Movie night, democratized')
        ->toContain('Lead story prose');

    // exactly one two-column row: both columns of the pair, nothing else halved
    expect(substr_count($html, 'class="mj-column-per-50'))->toBe(2);
});
