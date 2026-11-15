<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può eliminare un ticket', function () {
    $this->deleteJson('/api/tickets/1')
        ->assertUnauthorized();
});

test('un amministratore può eliminare un ticket della propria azienda', function () {
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
        ->deleteJson("/api/tickets/{$ticket->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('tickets', [
        'id' => $ticket->id,
    ]);
});

test('un supervisore non può eliminare un ticket', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $supervisor, $category)
        ->create();

    $this->actingAs($supervisor)
        ->deleteJson("/api/tickets/{$ticket->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
    ]);
});

test('un richiedente non può eliminare un ticket', function () {
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
        ->deleteJson("/api/tickets/{$ticket->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
    ]);
});

test('un amministratore non può eliminare un ticket di un altra azienda', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $otherUser = User::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $otherCategory = Category::factory()
        ->for($otherCompany)
        ->create();

    $ticket = Ticket::factory()
        ->forCompany($otherCompany, $otherUser, $otherCategory)
        ->create();

    $this->actingAs($admin)
        ->deleteJson("/api/tickets/{$ticket->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
    ]);
});

test('un ticket inesistente restituisce 404', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->deleteJson('/api/tickets/999999')
        ->assertNotFound();
});

test('un amministratore può eliminare anche un ticket chiuso', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

    $this->actingAs($admin)
        ->deleteJson("/api/tickets/{$ticket->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('tickets', [
        'id' => $ticket->id,
    ]);
});