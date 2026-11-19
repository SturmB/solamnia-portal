<?php

use App\Models\Article;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('the admin can create an article', function (): void {
    livewire(CreateArticle::class)
        ->fillForm([
            'title' => "A movie or show won't play",
            'slug' => 'a-movie-or-show-won-t-play',
            'body_markdown' => 'Restart the Plex app on your TV, then try the title again.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::sole())->title->toBe("A movie or show won't play")
        ->slug->toBe('a-movie-or-show-won-t-play')
        ->body_markdown->toBe('Restart the Plex app on your TV, then try the title again.')
        ->is_published->toBeFalse();
});
