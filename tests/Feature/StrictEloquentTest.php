<?php

use Illuminate\Database\Eloquent\Model;

it('runs Eloquent in strict mode outside production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});
