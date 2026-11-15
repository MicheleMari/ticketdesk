
<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può visualizzare gli utenti', function () {
    $this->getJson('/api/users')
        ->assertUnauthorized();
});

test('un utente visualizza soltanto gli utenti della propria azienda', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'name' => 'Amministratore',
    ]);

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
        'name' => 'Supervisore',
    ]);

    User::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Utente esterno',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $supervisor->id,
            'name' => 'Supervisore',
            'role' => User::ROLE_SUPERVISOR,
        ])
        ->assertDontSee('Utente esterno')
        ->assertDontSee('email');
});

test('un richiedente può visualizzare gli utenti della propria azienda', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_REQUESTER,
    ]);

    $this->actingAs($requester)
        ->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $requester->id);
});
