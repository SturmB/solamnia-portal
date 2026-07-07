<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subject')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                MarkdownEditor::make('body_markdown')
                    ->label('Body')
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('campaigns')
                    ->columnSpanFull()
                    ->live(debounce: '1s'),

                TextEntry::make('preview')
                    ->label('Preview')
                    ->state(function (Get $get): string {
                        $markdown = $get('body_markdown');

                        if (blank($markdown)) {
                            return '<p style="color: #6b7280">Nothing to preview yet.</p>';
                        }

                        $html = new Campaign([
                            'subject' => $get('subject') ?? '',
                            'body_markdown' => $markdown,
                        ])->renderHtml();

                        return '<iframe title="Campaign preview" style="width:100%;height:600px;border:1px solid #e5e7eb;border-radius:0.5rem" srcdoc="'.e($html).'"></iframe>';
                    })
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString($state))
                    ->columnSpanFull(),
            ]);
    }
}
