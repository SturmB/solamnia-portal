<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        Article::factory()->published()->create([
            'title' => 'Adding subtitles in Plex',
            'body_markdown' => <<<'MD'
                Most films and shows already have subtitles; you just have to switch them on.

                ## On a TV or streaming stick

                1. Start playing the film or episode.
                2. Press **Select** (or **OK**) on your remote to bring up the player controls.
                3. Choose the speech-bubble icon, then pick a language under **Subtitles**.

                > If the language you want isn't listed, choose **Search** at the bottom of the list. Plex looks online for a match, which takes a few seconds.

                ## In a web browser

                1. Hover over the playing video.
                2. Open `Settings → Subtitles` and choose a language.

                ### Subtitles out of sync?

                Open the same menu and use **Offset** to nudge them earlier or later.
                MD,
        ]);
        Article::factory()->published()->create([
            'title' => 'Wi-Fi keeps dropping',
            'body_markdown' => <<<'MD'
                If streams stall or buffer, the problem is usually your home Wi-Fi rather than the server.

                ## Quick fixes

                - Move the router somewhere open and high, away from the TV cabinet.
                - Restart the router: unplug it, count to ten, and plug it back in.
                - If your TV has an Ethernet port, a cable beats Wi-Fi every time.

                ## Check your speed

                | Quality | Speed needed |
                | --- | --- |
                | 720p | 4 Mbps |
                | 1080p | 10 Mbps |
                | 4K | 25 Mbps |

                Run a test at [fast.com](https://fast.com) from the same room as your TV.
                MD,
        ]);
        Article::factory()->published()->create([
            'title' => 'Markdown sampler',
            'body_markdown' => <<<'MD'
                This article exists only on local machines, to show every piece of Markdown the knowledge base styles.

                ## A second-level heading

                Body text with **bold**, *italic*, a [link](https://example.com), and a menu path like `Settings → Account`.

                ### A third-level heading

                1. A step long enough to wrap onto a second line, so you can check that wrapped lines indent past the numbered ring instead of sliding underneath it.
                2. A short step.
                3. A third step.

                - A bullet
                - Another bullet

                > A note. It should sit in a tinted panel with a full border, never a stripe down one side.

                ```
                docker compose --project-name solamnia-portal --file /opt/stacks/solamnia-portal/compose.yaml restart plex
                ```

                Raw HTML must show as text: <script>alert("not escaped!");</script> <b>not bold</b>
                MD,
        ]);
        Article::factory()->create([
            'title' => 'Draft: moving to the new server',
        ]);
    }
}
