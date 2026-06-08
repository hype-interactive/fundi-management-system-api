<?php

it('returns a successful response', function () {
    // $response = $this->get('/login');

    $response = $this->get('/'); // Just while testing

    $response->assertStatus(200);
});
