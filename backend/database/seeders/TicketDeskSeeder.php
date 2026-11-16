<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketDeskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Adriatica Logistica S.r.l.',
                'email_domain' => 'azienda1.test',
                'users' => [
                    ['name' => 'Marco Rossi', 'role' => User::ROLE_ADMIN],
                    ['name' => 'Luca Bianchi', 'role' => User::ROLE_SUPERVISOR],
                    ['name' => 'Giulia Moretti', 'role' => User::ROLE_REQUESTER],
                ],
            ],
            [
                'name' => 'Puglia Servizi Digitali S.r.l.',
                'email_domain' => 'azienda2.test',
                'users' => [
                    ['name' => 'Andrea Romano', 'role' => User::ROLE_ADMIN],
                    ['name' => 'Francesca Greco', 'role' => User::ROLE_SUPERVISOR],
                    ['name' => 'Davide Conti', 'role' => User::ROLE_REQUESTER],
                ],
            ],
        ];

        $categories = [
            ['name' => 'Assistenza informatica', 'color' => '#2563EB'],
            ['name' => 'Gestione account', 'color' => '#16A34A'],
            ['name' => 'Problemi software', 'color' => '#EA580C'],
        ];

        $tickets = [
            [
                'title' => 'Impossibile accedere al gestionale aziendale',
                'description' => 'Durante il tentativo di accesso al gestionale viene visualizzato un messaggio di errore che impedisce di utilizzare il sistema.',
                'status' => 'open',
                'priority' => 'high',
                'category' => 'Assistenza informatica',
            ],
            [
                'title' => 'Richiesta aggiornamento permessi utente',
                'description' => 'È necessario aggiornare i permessi di accesso per consentire a un dipendente di utilizzare alcune funzionalità aziendali.',
                'status' => 'in_progress',
                'priority' => 'medium',
                'category' => 'Gestione account',
            ],
            [
                'title' => 'Errore nella visualizzazione dei report',
                'description' => 'I report mensili non vengono visualizzati correttamente nella dashboard del software gestionale.',
                'status' => 'closed',
                'priority' => 'low',
                'category' => 'Problemi software',
            ],
        ];

        foreach ($companies as $companyData) {
            $company = Company::firstOrCreate([
                'name' => $companyData['name'],
            ]);

            $users = [];

            foreach ($companyData['users'] as $userData) {
                $email = strtolower(
                    str_replace(' ', '.', $userData['name'])
                ) . '@' . $companyData['email_domain'];

                $user = User::firstOrNew([
                    'email' => $email,
                ]);

                $user->name = $userData['name'];
                $user->company_id = $company->id;
                $user->role = $userData['role'];

                if (!$user->exists) {
                    $user->password = 'password';
                }

                $user->save();

                $users[$userData['role']] = $user;
            }

            $companyCategories = [];

            foreach ($categories as $categoryData) {
                $category = Category::firstOrNew([
                    'company_id' => $company->id,
                    'name' => $categoryData['name'],
                ]);

                $category->company_id = $company->id;
                $category->name = $categoryData['name'];
                $category->color = $categoryData['color'];

                $category->save();

                $companyCategories[$categoryData['name']] = $category;
            }

            foreach ($tickets as $ticketData) {
                $ticket = Ticket::firstOrNew([
                    'company_id' => $company->id,
                    'title' => $ticketData['title'],
                ]);

                $ticket->company_id = $company->id;
                $ticket->creator_id = $users[User::ROLE_REQUESTER]->id;
                $ticket->assignee_id = $ticketData['status'] === 'open'
                    ? null
                    : $users[User::ROLE_SUPERVISOR]->id;

                $ticket->category_id = $companyCategories[$ticketData['category']]->id;
                $ticket->title = $ticketData['title'];
                $ticket->description = $ticketData['description'];
                $ticket->status = $ticketData['status'];
                $ticket->priority = $ticketData['priority'];

                $ticket->closed_at = $ticketData['status'] === 'closed'
                    ? ($ticket->closed_at ?? now())
                    : null;

                $ticket->save();
            }
        }
    }
}