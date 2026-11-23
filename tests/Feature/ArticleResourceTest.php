<?php

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\MarkdownEditor;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
});

test('the admin can create an article', function (): void {
    livewire(CreateArticle::class)
        ->fillForm([
            'title' => "A movie or show won't play",
            'slug' => 'a-movie-or-show-wont-play',
            'body_markdown' => 'Restart the Plex app on your TV, then try the title again.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::sole())->title->toBe("A movie or show won't play")
        ->slug->toBe('a-movie-or-show-wont-play')
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

test('a non-admin cannot reach the article screens', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(ArticleResource::getUrl('index'))
        ->assertForbidden();
});

test("a new article's slug is filled from its title", function (): void {
    livewire(CreateArticle::class)
        ->fillForm(['title' => "A movie or show won't play"])
        ->assertSchemaStateSet(['slug' => 'a-movie-or-show-wont-play']);
});

test("editing an article's title keeps its slug", function (): void {
    $article = Article::factory()->create(['slug' => 'plex-buffering']);

    livewire(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['title' => 'Fixing playback that keeps pausing'])
        ->assertSchemaStateSet(['slug' => 'plex-buffering']);
});

test('a duplicate slug is rejected', function (): void {
    Article::factory()->create(['slug' => 'plex-buffering']);

    livewire(CreateArticle::class)
        ->fillForm([
            'title' => 'Plex buffering',
            'slug' => 'plex-buffering',
            'body_markdown' => 'Restart the Plex app on your TV.',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);

    expect(Article::count())->toBe(1);
});

test('article images upload to a public disk', function (): void {
    livewire(CreateArticle::class)
        ->assertFormFieldExists('body_markdown', fn (MarkdownEditor $field): bool => $field->getFileAttachmentsDiskName() === 'public'
            && $field->getFileAttachmentsVisibility() === 'public'
            && $field->getFileAttachmentsDirectory() === 'kb');
});
