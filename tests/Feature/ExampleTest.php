<?php

test('the application returns a successful response', function () {
    $this->get('/')->assertRedirect('/dashboard');

    $this->get('/login')->assertOk();
});
