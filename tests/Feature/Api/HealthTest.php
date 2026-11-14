<?php

test('the versioned API health endpoint returns its status', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('data.service', 'api')
        ->assertJsonPath('data.version', 'v1');
});
