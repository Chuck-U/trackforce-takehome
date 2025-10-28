<?php

use App\Http\Middleware\ValidateProviderToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Create a test route with the middleware
    Route::post('/test-auth', function () {
        return response()->json(['success' => true]);
    })->middleware(ValidateProviderToken::class);
});

test('allows requests with valid Bearer token', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer valid-test-token-12345',
    ])->postJson('/test-auth');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('rejects requests without authorization header', function () {
    $response = $this->postJson('/test-auth');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
            ],
        ]);
});

test('rejects requests with invalid token format', function () {
    $response = $this->withHeaders([
        'Authorization' => 'InvalidFormat token',
    ])->postJson('/test-auth');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
            ],
        ]);
});

test('rejects requests with empty token', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ',
    ])->postJson('/test-auth');

    $response->assertStatus(401);
});

test('rejects requests with too short token', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer short',
    ])->postJson('/test-auth');

    $response->assertStatus(401);
});

