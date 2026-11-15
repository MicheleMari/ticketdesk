<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('un utente può effettuare il login con credenziali valide', function () {
    $company = Company::factory()->create();

    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.role', User::ROLE_ADMIN)
        ->assertJsonStructure(['token'])
        ->assertJsonMissingPath('user.password');

    $this->assertDatabaseCount('personal_access_tokens', 1);
});

test('il login rifiuta credenziali errate', function () {
    $company = Company::factory()->create();

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password-errata',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('il login richiede email e password valide', function () {
    $this->postJson('/api/login', [
        'email' => 'non-valida',
        'password' => '',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

test('un utente autenticato può recuperare il proprio profilo', function () {
    $company = Company::factory()->create();

    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role', User::ROLE_SUPERVISOR)
        ->assertJsonPath('data.company_id', $company->id)
        ->assertJsonMissingPath('data.password');
});

test('un utente non autenticato non può recuperare il profilo', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized();
});

test('il logout revoca il token corrente', function () {
    $company = Company::factory()->create();

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertNoContent();

    $this->assertDatabaseCount('personal_access_tokens', 0);

    Auth::forgetGuards();

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});

test('un utente non autenticato non può eseguire il logout', function () {
    $this->postJson('/api/logout')
        ->assertUnauthorized();
});