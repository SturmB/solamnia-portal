<?php

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\User;
use Filament\Actions\DeleteAction;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
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

test('the admin can edit an article', function (): void {
    $article = Article::factory()->create();

    livewire(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm([
            'title' => "A movie or show won't play",
            'slug' => 'a-movie-or-show-won-t-play',
            'body_markdown' => 'Restart the Plex app on your TV, then try the title again.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Article::sole())->title->toBe("A movie or show won't play")
        ->slug->toBe('a-movie-or-show-won-t-play')
        ->body_markdown->toBe('Restart the Plex app on your TV, then try the title again.');
});

test('the admin can publish a draft article', function (): void {
    $article = Article::factory()->create();

    livewire(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['is_published' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Article::sole())->is_published->toBeTrue();
});

test('the admin can unpublish a published article', function (): void {
    $article = Article::factory()->published()->create();

    livewire(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['is_published' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Article::sole())->is_published->toBeFalse();
});

test('the admin can delete an article', function (): void {
    $article = Article::factory()->create();

    livewire(EditArticle::class, ['record' => $article->getRouteKey()])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($article);
});
