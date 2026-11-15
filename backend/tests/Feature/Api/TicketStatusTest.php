<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può cambiare lo stato di un ticket', function () {
    $this->patchJson('/api/tickets/1/status', [
        'status' => 'closed',
    ])->assertUnauthorized();
});

test('un amministratore può avviare la lavorazione di un ticket aperto', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create(['status' => 'open']);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'in_progress',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.closed_at', null);

    $ticket->refresh();

    expect($ticket->status)->toBe('in_progress')
        ->and($ticket->closed_at)->toBeNull();
});

test('un amministratore può chiudere direttamente un ticket aperto', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create(['status' => 'open']);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'closed',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');

    $ticket->refresh();

    expect($ticket->status)->toBe('closed')
        ->and($ticket->closed_at)->not->toBeNull();
});

test('un supervisore può chiudere un ticket assegnato in lavorazione', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create([
            'status' => 'in_progress',
            'assignee_id' => $supervisor->id,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'closed',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');

    $ticket->refresh();

    expect($ticket->closed_at)->not->toBeNull();
});

test('un amministratore può riaprire un ticket chiuso', function () {
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
            'closed_at' => now()->subDay(),
        ]);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'open',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.closed_at', null);

    $ticket->refresh();

    expect($ticket->status)->toBe('open')
        ->and($ticket->closed_at)->toBeNull();
});

test('un supervisore non può riaprire un ticket chiuso', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $supervisor, $category)
        ->create([
            'status' => 'closed',
            'closed_at' => now(),
            'assignee_id' => $supervisor->id,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'open',
        ])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe('closed');
});

test('un supervisore non può cambiare lo stato di un ticket assegnato ad altri', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $otherSupervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $supervisor, $category)
        ->create([
            'status' => 'open',
            'assignee_id' => $otherSupervisor->id,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'in_progress',
        ])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe('open');
});

test('una transizione di stato non consentita restituisce 409', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create(['status' => 'in_progress']);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'open',
        ])
        ->assertStatus(409);

    expect($ticket->fresh()->status)->toBe('in_progress');
});

test('non è possibile ripetere la transizione verso lo stato corrente', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create(['status' => 'open']);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'open',
        ])
        ->assertStatus(409);
});

test('un amministratore non può cambiare lo stato dei ticket di altre aziende', function () {
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
        ->create(['status' => 'open']);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'closed',
        ])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe('open');
});

test('uno stato non valido restituisce un errore di validazione', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'suspended',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    expect($ticket->fresh()->status)->toBe('open');
});