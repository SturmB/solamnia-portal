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
