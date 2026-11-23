<?php

use App\Models\Article;
use Illuminate\Contracts\View\View;
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
};
?>

<article>
    <h1>{{ $article->title }}</h1>
    {!! $article->body_html !!}
</article>
