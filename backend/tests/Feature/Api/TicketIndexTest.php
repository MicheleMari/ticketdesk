<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un utente non autenticato non può visualizzare i ticket', function () {
    $this->getJson('/api/tickets')
        ->assertUnauthorized();
});

test('un amministratore visualizza soltanto i ticket della propria azienda', function () {
    $company = Company::factory()->create([
        'name' => 'Adriatica Logistica',
    ]);

    $otherCompany = Company::factory()->create([
        'name' => 'Puglia Servizi Digitali',
    ]);

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $otherUser = User::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $category = Category::factory()->for($company)->create();
    $otherCategory = Category::factory()->for($otherCompany)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create();

    $otherTicket = Ticket::factory()
        ->forCompany($otherCompany, $otherUser, $otherCategory)
        ->create();

    $this->actingAs($admin)
        ->getJson('/api/tickets')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $ticket->id)
        ->assertJsonMissing(['id' => $otherTicket->id]);
});

test('un supervisore visualizza tutti i ticket della propria azienda', function () {
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

    Ticket::factory()
        ->count(3)
        ->forCompany($company, $requester, $category)
        ->create();

    $this->actingAs($supervisor)
        ->getJson('/api/tickets')
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonCount(3, 'data');
});

test('un richiedente visualizza soltanto i ticket creati personalmente', function () {
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
        ->forCompany($company, $requester, $category)
        ->create();

    $otherTicket = Ticket::factory()
        ->forCompany($company, $otherRequester, $category)
        ->create();

    $this->actingAs($requester)
        ->getJson('/api/tickets')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $ticket->id)
        ->assertJsonMissing(['id' => $otherTicket->id]);
});

test('un amministratore può filtrare i ticket per stato e priorità', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $openTicket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'status' => 'open',
            'priority' => 'high',
        ]);

    Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

    Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'status' => 'closed',
            'priority' => 'low',
            'closed_at' => now(),
        ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?status=open&priority=high')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $openTicket->id);
});


test('la ricerca per titolo non distingue maiuscole e minuscole', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'title' => 'Problema Accesso Gestionale',
        ]);

    Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'title' => 'Richiesta sostituzione computer',
        ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?q=gestionale')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $ticket->id);

    $this->actingAs($admin)
        ->getJson('/api/tickets?q=GESTIONALE')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $ticket->id);
});


test('l elenco dei ticket supporta la paginazione', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    Ticket::factory()
        ->count(12)
        ->forCompany($company, $admin, $category)
        ->create();

    $this->actingAs($admin)
        ->getJson('/api/tickets?per_page=5&page=2')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 12);
});


test('i ticket possono essere ordinati per data di scadenza', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $category = Category::factory()->for($company)->create();

    $firstTicket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'due_date' => now()->addDays(2),
        ]);

    $secondTicket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'due_date' => now()->addDays(5),
        ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?sort=due_date&direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.id', $firstTicket->id)
        ->assertJsonPath('data.1.id', $secondTicket->id);
});

test('l ordinamento predefinito usa la data di creazione decrescente', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);
    $category = Category::factory()->for($company)->create();

    $oldest = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'created_at' => now()->subDays(3),
    ]);
    $newest = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets')
        ->assertOk()
        ->assertJsonPath('data.0.id', $newest->id)
        ->assertJsonPath('data.1.id', $oldest->id);
});

test('sort due_date usa desc come direzione predefinita', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);
    $category = Category::factory()->for($company)->create();

    $nearest = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'due_date' => now()->addDays(2),
    ]);
    $latest = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'due_date' => now()->addDays(5),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?sort=due_date')
        ->assertOk()
        ->assertJsonPath('data.0.id', $latest->id)
        ->assertJsonPath('data.1.id', $nearest->id);
});

test('direction asc usa created_at come sort predefinito', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);
    $category = Category::factory()->for($company)->create();

    $oldest = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'created_at' => now()->subDays(3),
    ]);
    $newest = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.id', $oldest->id)
        ->assertJsonPath('data.1.id', $newest->id);
});

test('la coda di lavoro ordina per priorità e anzianità', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);
    $category = Category::factory()->for($company)->create();

    $mediumOlder = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'priority' => 'medium',
        'created_at' => now()->subDays(4),
    ]);
    $highNewer = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'priority' => 'high',
        'created_at' => now()->subDay(),
    ]);
    $highOlder = Ticket::factory()->forCompany($company, $admin, $category)->create([
        'priority' => 'high',
        'created_at' => now()->subDays(3),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?sort=work_queue')
        ->assertOk()
        ->assertJsonPath('data.0.id', $highOlder->id)
        ->assertJsonPath('data.1.id', $highNewer->id)
        ->assertJsonPath('data.2.id', $mediumOlder->id);
});

test('un sort non consentito restituisce un errore di validazione', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?sort=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort']);
});


test('i ticket possono essere filtrati per categoria e assegnatario', function () {
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
    $otherCategory = Category::factory()->for($company)->create();

    $ticket = Ticket::factory()
        ->forCompany($company, $admin, $category)
        ->create([
            'assignee_id' => $supervisor->id,
        ]);

    Ticket::factory()
        ->forCompany($company, $admin, $otherCategory)
        ->create();

    $this->actingAs($admin)
        ->getJson("/api/tickets?category_id={$category->id}&assignee_id={$supervisor->id}")
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $ticket->id);
});


test('i parametri di paginazione non validi restituiscono un errore di validazione', function () {
    $company = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
    ]);

    $this->actingAs($admin)
        ->getJson('/api/tickets?per_page=100')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});
