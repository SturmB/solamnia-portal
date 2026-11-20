<?php

use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Knowledge base')] class extends Component {
    /**
     * @return Collection<int, Article>
     */
    #[Computed]
    public function articles(): Collection
    {
        return Article::published()->orderBy('title')->get();
    }
};
?>

<section class="w-full">
    <h1>Knowledge base</h1>

    <ul role="list">
        @foreach ($this->articles as $article)
            <li>{{ $article->title }}</li>
        @endforeach
    </ul>
</section>
