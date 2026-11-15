<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può visualizzare le categorie', function () {
    $this->getJson('/api/categories')
        ->assertUnauthorized();
});

test('un richiedente visualizza soltanto le categorie della propria azienda', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();
    $otherCategory = Category::factory()->for($otherCompany)->create();

    $this->actingAs($requester)
        ->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $category->id)
        ->assertJsonMissing(['id' => $otherCategory->id]);
});

test('un utente può visualizzare il dettaglio di una categoria della propria azienda', function () {
    $company = Company::factory()->create();
    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);
    $category = Category::factory()->for($company)->create();

    $this->actingAs($requester)
        ->getJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.name', $category->name);
});

test('un utente non può visualizzare una categoria di un altra azienda', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($otherCompany)->create();

    $this->actingAs($admin)
        ->getJson("/api/categories/{$category->id}")
        ->assertForbidden();
});

test('un amministratore può creare una categoria', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/categories', [
            'name' => 'Assistenza informatica',
            'color' => '#2563EB',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Assistenza informatica');

    $this->assertDatabaseHas('categories', [
        'company_id' => $company->id,
        'name' => 'Assistenza informatica',
        'color' => '#2563EB',
    ]);
});

test('un richiedente non può creare categorie', function () {
    $company = Company::factory()->create();
    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAs($requester)
        ->postJson('/api/categories', [
            'name' => 'Assistenza informatica',
            'color' => '#2563EB',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('categories', 0);
});

test('un amministratore può modificare una categoria della propria azienda', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $this->actingAs($admin)
        ->patchJson("/api/categories/{$category->id}", [
            'name' => 'Problemi software',
            'color' => '#16A34A',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Problemi software');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Problemi software',
        'color' => '#16A34A',
    ]);
});

test('un amministratore non può modificare categorie di altre aziende', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($otherCompany)->create();

    $this->actingAs($admin)
        ->patchJson("/api/categories/{$category->id}", [
            'name' => 'Nuovo nome categoria',
        ])
        ->assertForbidden();
});

test('non è possibile creare due categorie con lo stesso nome nella stessa azienda', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    Category::factory()->for($company)->create([
        'name' => 'Assistenza informatica',
    ]);

    $this->actingAs($admin)
        ->postJson('/api/categories', [
            'name' => 'Assistenza informatica',
            'color' => '#2563EB',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('un amministratore può eliminare una categoria senza ticket', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $this->actingAs($admin)
        ->deleteJson("/api/categories/{$category->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('non è possibile eliminare una categoria collegata a un ticket', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create();

    $this->actingAs($admin)
        ->deleteJson("/api/categories/{$category->id}")
        ->assertStatus(409);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
    ]);
});

test('un supervisore non può eliminare una categoria', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $category = Category::factory()->for($company)->create();

    $this->actingAs($supervisor)
        ->deleteJson("/api/categories/{$category->id}")
        ->assertForbidden();
});