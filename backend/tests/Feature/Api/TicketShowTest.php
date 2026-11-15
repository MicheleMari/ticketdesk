<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può visualizzare un ticket', function () {
    $this->getJson('/api/tickets/1')
        ->assertUnauthorized();
});

test('un amministratore può visualizzare un ticket della propria azienda', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create();

    $this->actingAs($admin)
        ->getJson("/api/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id)
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.assignee', null);
});

test('il dettaglio include i dati dell assegnatario', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'assignee_id' => $supervisor->id,
        ]);

    $this->actingAs($admin)
        ->getJson("/api/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $supervisor->id)
        ->assertJsonPath('data.assignee.name', $supervisor->name);
});

test('un supervisore può visualizzare i ticket della propria azienda', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $requester = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_REQUESTER,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create();

    $this->actingAs($supervisor)
        ->getJson("/api/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id);
});

test('un richiedente può visualizzare un ticket creato personalmente', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_REQUESTER,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create();

    $this->actingAs($requester)
        ->getJson("/api/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id);
});

test('un richiedente non può visualizzare i ticket di altri utenti', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_REQUESTER,
    ]);

    $otherRequester = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_REQUESTER,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $otherRequester, $category)
        ->create();

    $this->actingAs($requester)
        ->getJson("/api/tickets/{$ticket->id}")
        ->assertForbidden();
});

test('un amministratore non può visualizzare i ticket di un altra azienda', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $otherUser = User::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $otherCategory = Category::factory()->for($otherCompany)->create();

    $ticket = Ticket::factory()
        ->forCompany($otherCompany, $otherUser, $otherCategory)
        ->create();

    $this->actingAs($admin)
        ->getJson("/api/tickets/{$ticket->id}")
        ->assertForbidden();
});

test('un ticket inesistente restituisce 404', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets/999999')
        ->assertNotFound();
});