<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può assegnare un ticket', function () {
    $this->patchJson('/api/tickets/1/assign', [
        'assignee_id' => 1,
    ])->assertUnauthorized();
});

test('un amministratore può assegnare un ticket a un utente della propria azienda', function () {
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
        ->create();

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $supervisor->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $supervisor->id);

    expect($ticket->fresh()->assignee_id)->toBe($supervisor->id);
});

test('un amministratore può riassegnare un ticket', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $firstSupervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $secondSupervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'assignee_id' => $firstSupervisor->id,
        ]);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $secondSupervisor->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $secondSupervisor->id);

    expect($ticket->fresh()->assignee_id)->toBe($secondSupervisor->id);
});

test('un amministratore può rimuovere l assegnazione di un ticket', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.assignee', null);

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

test('un supervisore può assegnare a sé stesso un ticket libero', function () {
    $company = Company::factory()->create();

    $supervisor = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_SUPERVISOR,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $supervisor, $category)
        ->create([
            'assignee_id' => null,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $supervisor->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $supervisor->id);

    expect($ticket->fresh()->assignee_id)->toBe($supervisor->id);
});

test('un supervisore non può assegnare un ticket a un altro utente', function () {
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
        ->create();

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $otherSupervisor->id,
        ])
        ->assertForbidden();

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

test('un supervisore non può riassegnare un ticket già assegnato', function () {
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
            'assignee_id' => $otherSupervisor->id,
        ]);

    $this->actingAs($supervisor)
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $supervisor->id,
        ])
        ->assertForbidden();

    expect($ticket->fresh()->assignee_id)->toBe($otherSupervisor->id);
});

test('un richiedente non può assegnare ticket', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $requester->id,
        ])
        ->assertForbidden();
});

test('non è possibile assegnare un ticket a un utente di un altra azienda', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $otherUser->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assignee_id']);

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

test('un ticket chiuso non può essere assegnato', function () {
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
            'status' => 'closed',
            'closed_at' => now(),
        ]);

    $this->actingAs($admin)
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $supervisor->id,
        ])
        ->assertForbidden();

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

test('un amministratore non può assegnare ticket di altre aziende', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $admin->id,
        ])
        ->assertForbidden();

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

test('l assegnatario è obbligatorio anche quando il valore è nullo', function () {
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
        ->patchJson("/api/tickets/{$ticket->id}/assign", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assignee_id']);
});