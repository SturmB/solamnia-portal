<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'body_markdown', 'is_published'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * The start of the body as plain text, for the knowledge base list.
     *
     * @return Attribute<string, never>
     */
    protected function excerpt(): Attribute
    {
        return Attribute::get(fn (): string => Str::markdown($this->body_markdown, ['html_input' => 'strip'])
            |> strip_tags(...)
            |> html_entity_decode(...)
            |> (fn (string $text): string => Str::limit($text, 110, '…', preserveWords: true)));
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
