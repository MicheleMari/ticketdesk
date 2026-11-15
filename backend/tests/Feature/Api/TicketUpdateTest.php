
<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può modificare un ticket', function () {
    $this->patchJson('/api/tickets/1', [])
        ->assertUnauthorized();
});

test('un amministratore può modificare un ticket della propria azienda', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create();

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'title' => 'Problema accesso gestionale aggiornato',
            'priority' => 'high',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Problema accesso gestionale aggiornato')
        ->assertJsonPath('data.priority', 'high');

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'title' => 'Problema accesso gestionale aggiornato',
        'priority' => 'high',
    ]);
});

test('un supervisore può modificare un ticket assegnato a sé stesso', function () {
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
            'assignee_id' => $supervisor->id,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'high',
        ])
        ->assertOk()
        ->assertJsonPath('data.priority', 'high');
});

test('un supervisore può modificare un ticket non assegnato', function () {
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
            'assignee_id' => null,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'low',
        ])
        ->assertOk()
        ->assertJsonPath('data.priority', 'low');
});

test('un supervisore non può modificare un ticket assegnato ad altri', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $otherSupervisor = User::factory()->create([
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
            'assignee_id' => $otherSupervisor->id,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'high',
        ])
        ->assertForbidden();
});

test('un richiedente può modificare un ticket creato personalmente', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create();

    $this->actingAs($requester)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'title' => 'Richiesta assistenza aggiornata',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Richiesta assistenza aggiornata');
});

test('un richiedente non può modificare ticket creati da altri utenti', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $otherRequester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $otherRequester, $category)
        ->create();

    $this->actingAs($requester)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'high',
        ])
        ->assertForbidden();
});

test('un amministratore non può modificare ticket di un altra azienda', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'high',
        ])
        ->assertForbidden();
});

test('un ticket chiuso non può essere modificato', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}", [
            'title' => 'Tentativo di modifica ticket chiuso',
        ])
        ->assertForbidden();
});

test('la scadenza può essere nel passato durante una modifica', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create();

    $dueDate = now()->subDays(2)->toDateString();

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'due_date' => $dueDate,
        ])
        ->assertOk()
        ->assertJsonPath('data.due_date', $dueDate);
});

test('non è possibile modificare un ticket usando una categoria di un altra azienda', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();
    $otherCategory = Category::factory()->for($otherCompany)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create();

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'category_id' => $otherCategory->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

test('il client non può modificare i campi riservati di un ticket', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $otherUser = User::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create();

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'high',
            'company_id' => $otherCompany->id,
            'creator_id' => $otherUser->id,
            'assignee_id' => $otherUser->id,
            'status' => 'closed',
            'closed_at' => now()->toIso8601String(),
        ])
        ->assertOk()
        ->assertJsonPath('data.priority', 'high');

    $ticket->refresh();

    expect($ticket->company_id)->toBe($company->id)
        ->and($ticket->creator_id)->toBe($admin->id)
        ->and($ticket->assignee_id)->toBeNull()
        ->and($ticket->status)->toBe('open')
        ->and($ticket->closed_at)->toBeNull();
});