<?php

/**
 * Smoke tests: no DB fixtures; verifies API wiring responds without 500s.
 *
 * Note: If `php artisan test` fails with vendor PHP version errors (e.g. typed
 * class constants), upgrade PHP to match project tooling or adjust dev dependencies.
 */

it('api yes endpoint returns success', function () {
    $response = $this->getJson('/api/yes');

    $response->assertOk();
    expect($response->getContent())->toContain('yes');
});

it('protected client route rejects unauthenticated access', function () {
    $response = $this->getJson('/api/client/dashboard');

    expect($response->status())->toBeIn([401, 403]);
});

it('protected provider route rejects unauthenticated access', function () {
    $response = $this->getJson('/api/provider/dashboard');

    expect($response->status())->toBeIn([401, 403]);
});
