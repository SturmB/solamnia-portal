<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    |
    | The Services a Member can open from the dashboard, in display order.
    | Public links only — never put credentials or tokens here.
    |
    */

    'services' => [
        ['name' => 'Plex', 'description' => 'Watch movies and shows', 'url' => 'https://app.plex.tv'],
        ['name' => 'Requests', 'description' => 'Request a movie or show', 'url' => 'https://requests.solamnia.tv'],
        ['name' => 'E-books', 'description' => 'Find your next read', 'url' => 'https://books.solamnia.tv'],
        ['name' => 'Audiobooks', 'description' => 'Listen to your next book', 'url' => 'https://audiobooks.solamnia.tv'],
        ['name' => 'Recipes', 'description' => 'Find something good to cook', 'url' => 'https://recipes.solamnia.tv'],
    ],

];
