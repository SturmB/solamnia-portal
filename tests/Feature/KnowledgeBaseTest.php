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
