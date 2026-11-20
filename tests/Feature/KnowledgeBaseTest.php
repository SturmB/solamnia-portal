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
]);
