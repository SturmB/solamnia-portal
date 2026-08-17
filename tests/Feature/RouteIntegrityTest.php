<?php

use Illuminate\Support\Facades\Route;

test('route names are unique so route:cache can serialize', function () {
    $names = collect(Route::getRoutes())->map->getName()->filter();

    expect($names->duplicates()->values()->all())->toBe([]);
});

test('the login name points at the member door, backup.login at the break-glass one', function () {
    expect(route('login', absolute: false))->toBe('/login')
        ->and(route('backup.login', absolute: false))->toBe('/backup/login');
});
