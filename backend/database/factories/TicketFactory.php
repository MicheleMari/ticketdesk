<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'creator_id' => User::factory(),
            'category_id' => Category::factory(),
            'assignee_id' => null,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'status' => 'open',
            'priority' => fake()->randomElement([
                'low',
                'medium',
                'high',
            ]),
            'due_date' => null,
            'closed_at' => null,
        ];
    }

    public function forCompany(
        Company $company,
        User $creator,
        Category $category
    ): static {
        return $this->state(fn () => [
            'company_id' => $company->id,
            'creator_id' => $creator->id,
            'category_id' => $category->id,
        ]);
    }
}