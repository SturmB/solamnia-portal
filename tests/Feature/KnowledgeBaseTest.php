<?php

use App\Models\Article;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('kb.index'));
    $response->assertRedirect(route('login'));
});

test('members can visit the knowledge base', function (): void {
    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.index'))
        ->assertOk();
});

test('members see only published articles, sorted by title', function (): void {
    Article::factory()->published()->create(['title' => 'Wi-Fi keeps dropping']);
    Article::factory()->published()->create(['title' => 'Adding subtitles in Plex']);
    Article::factory()->create(['title' => 'Draft: new media server']);

    $this->actingAs(User::factory()->sso()->create());

    Livewire::test('pages::kb.index')
        ->assertSeeInOrder([
            'Adding subtitles in Plex',
            'Wi-Fi keeps dropping',
        ])
        ->assertDontSee('Draft: new media server');
});

test('search finds articles by words in the title or body', function (): void {
    Article::factory()->published()->create(['title' => 'Adding subtitles in Plex']);
    Article::factory()->published()->create([
        'title' => 'Wi-Fi keeps dropping',
        'body_markdown' => 'Restart the router, then reopen Plex.',
    ]);
    Article::factory()->published()->create([
        'title' => 'Requesting a movie',
        'body_markdown' => 'Use the Requests service.',
    ]);
    Article::factory()->create(['title' => 'Draft: Plex on the new server']);

    $this->actingAs(User::factory()->sso()->create());

    Livewire::test('pages::kb.index')
        ->set('search', 'plex')
        ->assertSee('Adding subtitles in Plex')
        ->assertSee('Wi-Fi keeps dropping')
        ->assertDontSee('Requesting a movie')
        ->assertDontSee('Draft: Plex on the new server');
});

test('the count line says how many articles are shown', function (string $search, string $expected): void {
    Article::factory()->published()->create(['title' => 'Adding subtitles in Plex']);
    Article::factory()->published()->create([
        'title' => 'Wi-Fi keeps dropping',
        'body_markdown' => 'Restart the router, then reopen Plex.',
    ]);
    Article::factory()->published()->create([
        'title' => 'Requesting a movie',
        'body_markdown' => 'Use the Requests service.',
    ]);

    $this->actingAs(User::factory()->sso()->create());

    Livewire::test('pages::kb.index')
        ->set('search', $search)
        ->assertSee($expected);
})->with([
    'no search' => ['', '3 articles'],
    'one match' => ['subtitles', '1 article matches “subtitles”'],
    'several matches' => ['plex', '2 articles match “plex”'],
    'no matches' => ['roku', 'No articles match “roku”'],
]);

test('a search with no matches offers to ask Chris', function (): void {
    config(['app.admin_contact_email' => 'admin@example.com']);
    Article::factory()->published()->create();

    $this->actingAs(User::factory()->sso()->create());

    Livewire::test('pages::kb.index')
        ->set('search', 'roku')
        ->assertSee('Nothing on that.')
        ->assertSeeHtml('mailto:admin@example.com?subject=Knowledge%20base%3A%20roku');
});

test('an empty knowledge base offers to ask Chris', function (): void {
    config(['app.admin_contact_email' => 'admin@example.com']);
    Article::factory()->create();

    $this->actingAs(User::factory()->sso()->create());

    Livewire::test('pages::kb.index')
        ->assertSee('Nothing written yet.')
        ->assertSeeHtml('mailto:admin@example.com?subject=Knowledge%20base"')
        ->assertDontSee('Nothing on that.');
});

test('the header links to the knowledge base', function (): void {
    $this->actingAs(User::factory()->sso()->create())
        ->get(route('dashboard'))
        ->assertSee(route('kb.index'));
});

test('each article shows a plain-text excerpt of its body', function (): void {
    Article::factory()->published()->create([
        'body_markdown' => "**Restart** the [router](https://example.com) & wait a minute.\n\n"
            .str_repeat('Then try again. ', 10).'THE END',
    ]);

    $this->actingAs(User::factory()->sso()->create());

    Livewire::test('pages::kb.index')
        ->assertSee('Restart the router & wait a minute.')
        ->assertDontSee('**Restart**')
        ->assertDontSee('THE END');
});

test('members can read a published article', function (): void {
    $article = Article::factory()->published()->create();

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertOk()
        ->assertSee($article->title);
});

test('an unpublished article is not found', function (): void {
    $article = Article::factory()->create();

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertNotFound();
});

test('article markdown renders with raw html escaped', function (): void {
    $article = Article::factory()->published()->create([
        'body_markdown' => "**Restart** the router <script>alert('x')</script>\n\n[Click me](javascript:alert('x'))",
    ]);

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertSee('<strong>Restart</strong>', false)
        ->assertDontSee("<script>alert('x')</script>", false)
        ->assertDontSee('href="javascript:', false);
});

test('each article in the list links to its page', function (): void {
    $article = Article::factory()->published()->create();

    $this->actingAs(User::factory()->sso()->create());

    Livewire::test('pages::kb.index')
        ->assertSeeHtml('href="'.route('kb.show', $article).'"');
});

test('an article shows when it was last updated', function (): void {
    $article = Article::factory()->published()->create([
        'updated_at' => '2026-03-14',
    ]);

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertSee('Updated March 2026');
});

test('an article offers to ask Chris about it', function (): void {
    config(['app.admin_contact_email' => 'admin@example.com']);
    $article = Article::factory()->published()->create([
        'title' => 'Wi-Fi keeps dropping',
    ]);

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertSee('mailto:admin@example.com?subject=About%3A%20Wi-Fi%20keeps%20dropping', false);
});

test('without a contact email the ask-chris line has no link', function (): void {
    config(['app.admin_contact_email' => null]);
    $article = Article::factory()->published()->create();

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertSee('Didn’t fix it?', false)
        ->assertDontSee('mailto:', false);
});

test('an article links to a focused search', function (): void {
    $article = Article::factory()->published()->create();

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertSee(route('kb.index', ['focus' => 1]), false);
});

test('the search input is focused only when asked for', function (array $query, bool $focused): void {
    $html = $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.index', $query))
        ->getContent();

    expect(str_contains($html, 'autofocus'))->toBe($focused);
})->with([
    'from the pill' => [['focus' => 1], true],
    'normal visit' => [[], false],
]);

test('article tables are wrapped so they can scroll', function (): void {
    $article = Article::factory()->published()->create([
        'body_markdown' => "| Quality | Speed |\n| --- | --- |\n| 4K | 25 Mbps |",
    ]);

    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.show', $article))
        ->assertSee('<div class="kb-table"><table>', false);
});

test('the kb nav items say where you are', function (Closure $url, string $expected): void {
    $html = $this->actingAs(User::factory()->sso()->create())
        ->get($url())
        ->getContent();

    expect(substr_count($html, $expected))->toBe(2);
})->with([
    'on the index' => [fn (): string => route('kb.index'), 'aria-current="page"'],
    'on an article' => [fn (): string => route('kb.show', Article::factory()->published()->create()), 'aria-current="location"'],
]);

test('the aurora dims on kb pages', function (Closure $url, string $expected): void {
    $this->actingAs(User::factory()->sso()->create())
        ->get($url())
        ->assertSee($expected, false);
})->with([
    'the dashboard' => [fn (): string => route('dashboard'), 'data-aurora="0.25"'],
    'the kb index' => [fn (): string => route('kb.index'), 'data-aurora="0.1"'],
    'an article' => [fn (): string => route('kb.show', Article::factory()->published()->create()), 'data-aurora="0.1"'],
]);
