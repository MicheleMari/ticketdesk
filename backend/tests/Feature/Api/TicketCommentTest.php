<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può visualizzare i commenti', function () {
    $this->getJson('/api/tickets/1/comments')
        ->assertUnauthorized();
});

test('un utente autorizzato può aggiungere un commento', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create();

    $this->actingAs($requester)
        ->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'Il problema si verifica anche questa mattina.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Il problema si verifica anche questa mattina.')
        ->assertJsonPath('data.user.id', $requester->id);

    $this->assertDatabaseHas('comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $requester->id,
        'body' => 'Il problema si verifica anche questa mattina.',
    ]);
});

test('un ticket chiuso può ricevere nuovi commenti', function () {
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
        ->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'Confermo che il problema è stato risolto.',
        ])
        ->assertCreated();

    $this->assertDatabaseCount('comments', 1);
});

test('un richiedente non può commentare ticket creati da altri', function () {
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
        ->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'Tentativo di commento non autorizzato.',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('comments', 0);
});

test('un utente non può visualizzare i commenti di ticket di altre aziende', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $otherUser = User::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $category = Category::factory()->for($otherCompany)->create();

    $ticket = Ticket::factory()
        ->forCompany($otherCompany, $otherUser, $category)
        ->create();

    $this->actingAs($admin)
        ->getJson("/api/tickets/{$ticket->id}/comments")
        ->assertForbidden();
});

test('il contenuto del commento è obbligatorio', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create();

    $this->actingAs($requester)
        ->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);

    $this->assertDatabaseCount('comments', 0);
});

test('un utente autorizzato può visualizzare i commenti di un ticket', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $requester, $category)
        ->create();

    $comment = $ticket->comments()->make([
        'body' => 'Richiesta ricevuta e presa in carico.',
    ]);

    $comment->user_id = $requester->id;
    $comment->save();

    $this->actingAs($requester)
        ->getJson("/api/tickets/{$ticket->id}/comments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $comment->id)
        ->assertJsonPath('data.0.user.id', $requester->id);
});