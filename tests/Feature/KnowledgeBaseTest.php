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
