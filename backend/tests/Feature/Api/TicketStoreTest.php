<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può creare ticket', function () {
    $this->postJson('/api/tickets', [])
        ->assertUnauthorized();
});

test('un richiedente può creare un ticket nella propria azienda', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_REQUESTER,
    ]);

    $category = Category::factory()->for($company)->create();

    $data = [
        'title' => 'Problema accesso gestionale',
        'description' => 'Non riesco ad accedere al gestionale aziendale.',
        'priority' => 'high',
        'category_id' => $category->id,
        'due_date' => now()->addDays(3)->toDateString(),
    ];

    $this->actingAs($requester)
        ->postJson('/api/tickets', $data)
        ->assertCreated()
        ->assertJsonPath('data.title', $data['title'])
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.assignee', null);

    $this->assertDatabaseHas('tickets', [
        'title' => $data['title'],
        'company_id' => $company->id,
        'creator_id' => $requester->id,
        'category_id' => $category->id,
        'status' => 'open',
        'assignee_id' => null,
    ]);
});

test('non è possibile creare un ticket con una categoria di un altra azienda', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $otherCategory = Category::factory()->for($otherCompany)->create();

    $this->actingAs($requester)
        ->postJson('/api/tickets', [
            'title' => 'Problema accesso gestionale',
            'description' => 'Non riesco ad accedere al gestionale aziendale.',
            'priority' => 'high',
            'category_id' => $otherCategory->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);

    $this->assertDatabaseCount('tickets', 0);
});

test('il client non può impostare i campi riservati del ticket', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $otherUser = User::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $category = Category::factory()->for($company)->create();

    $this->actingAs($requester)
        ->postJson('/api/tickets', [
            'title' => 'Problema accesso gestionale',
            'description' => 'Non riesco ad accedere al gestionale aziendale.',
            'priority' => 'high',
            'category_id' => $category->id,
            'company_id' => $otherCompany->id,
            'creator_id' => $otherUser->id,
            'assignee_id' => $otherUser->id,
            'status' => 'closed',
            'closed_at' => now()->toIso8601String(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.assignee', null);

    $ticket = Ticket::firstOrFail();

    expect($ticket->company_id)->toBe($company->id)
        ->and($ticket->creator_id)->toBe($requester->id)
        ->and($ticket->assignee_id)->toBeNull()
        ->and($ticket->status)->toBe('open')
        ->and($ticket->closed_at)->toBeNull();
});

test('i dati non validi impediscono la creazione del ticket', function () {
    $company = Company::factory()->create();

    $requester = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAs($requester)
        ->postJson('/api/tickets', [
            'title' => 'ABC',
            'description' => 'Breve',
            'priority' => 'urgent',
            'category_id' => 999,
            'due_date' => now()->subDay()->toDateString(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title',
            'description',
            'priority',
            'category_id',
            'due_date',
        ]);

    $this->assertDatabaseCount('tickets', 0);
});