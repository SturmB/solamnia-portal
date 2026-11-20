<?php

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Knowledge base')] class extends Component {
    public string $search = '';

    /**
     * @return Collection<int, Article>
     */
    #[Computed]
    public function articles(): Collection
    {
        return Article::published()
            ->when($this->search !== '', fn(Builder $query) => $query->whereAny(['title', 'body_markdown'], 'like', "%{$this->search}%"))
            ->orderBy('title')
            ->get();
    }
};
?>

<section class="w-full">
    <h1>Knowledge base</h1>

    <flux:input type="search" icon="magnifying-glass" wire:model.live.debounce.300ms="search"
        aria-label="Search articles" />

    <ul role="list">
        @foreach ($this->articles as $article)
            <li>{{ $article->title }}</li>
        @endforeach
    </ul>
</section>
