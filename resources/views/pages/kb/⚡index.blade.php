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

    #[Computed]
    public function countLine(): string
    {
        $count = $this->articles->count();

        return $this->search === '' ? trans_choice(':count article|:count articles', $count) : trans_choice('{0} No articles match “:term”|{1} :count article matches “:term”|[2,*] :count articles match “:term”', $count, ['term' => $this->search]);
    }

    #[Computed]
    public function askChrisUrl(): string
    {
        $subject = $this->search === '' ? 'Knowledge base' : "Knowledge base: {$this->search}";

        return 'mailto:' . config('app.admin_contact_email') . '?subject=' . rawurlencode($subject);
    }
};
?>

<section class="w-full">
    <h1>Knowledge base</h1>

    <flux:input type="search" icon="magnifying-glass" wire:model.live.debounce.300ms="search"
        aria-label="Search articles" />

    <p class="text-muted" aria-live="polite">{{ $this->countLine }}</p>

    @if ($this->articles->isNotEmpty())
        <ul role="list">
            @foreach ($this->articles as $article)
                <li>{{ $article->title }}</li>
            @endforeach
        </ul>
    @elseif ($this->search !== '')
        <p>
            Nothing on that.
            <a href="{{ $this->askChrisUrl }}" class="text-violet-text font-semibold underline">Ask Chris</a>
            — the answer usually exists before the article does.
        </p>
    @else
        <p>
            Nothing written yet.
            <a href="{{ $this->askChrisUrl }}" class="text-violet-text font-semibold underline">Ask Chris</a>
            in the meantime.
        </p>
    @endif
</section>
