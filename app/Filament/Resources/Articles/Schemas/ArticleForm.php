<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                MarkdownEditor::make('body_markdown')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_published'),
            ]);
    }
}
