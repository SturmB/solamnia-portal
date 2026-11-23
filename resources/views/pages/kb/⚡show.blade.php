<?php

use App\Models\Article;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Article $article;

    public function mount(Article $article): void
    {
        abort_unless($article->is_published, 404);
    }

    public function render(): View
    {
        return $this->view()->title($this->article->title);
    }

    #[Computed]
    public function askChrisUrl(): ?string
    {
        $email = config('app.admin_contact_email');

        if (blank($email)) {
            return null;
        }

        return "mailto:{$email}?subject=" . rawurlencode("About: {$this->article->title}");
    }
};
?>

<article class="max-w-184 mx-auto w-full">
    <a href="{{ route('kb.index') }}" wire:navigate class="text-violet-text text-sm font-semibold">
        <span aria-hidden="true">←</span> Knowledge base
    </a>

    <h1
        class="font-display text-ink mt-6 text-balance text-[clamp(1.75rem,1.2rem+2vw,2.5rem)] font-semibold leading-tight">
        {{ $article->title }}
    </h1>
    <p class="text-muted mt-2 text-sm">Updated {{ $article->updated_at->format('F Y') }}</p>

    <div class="mt-8">{!! $article->body_html !!}</div>

    <p class="mt-12">
        Didn’t fix it?
        @if ($this->askChrisUrl)
            <a href="{{ $this->askChrisUrl }}" class="text-violet-text font-semibold underline">Ask Chris <span
                    aria-hidden="true">→</span></a>
        @else
            Ask Chris.
        @endif
    </p>
</article>
