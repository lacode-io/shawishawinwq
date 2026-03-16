<?php

namespace Database\Factories;

use App\Enums\NotePriority;
use App\Enums\NoteType;
use App\Models\AppNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppNote> */
class AppNoteFactory extends Factory
{
    protected $model = AppNote::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(NoteType::cases()),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraphs(2, true),
            'priority' => NotePriority::Normal,
            'created_by_user_id' => User::factory(),
            'updated_by_user_id' => User::factory(),
        ];
    }

    public function note(): static
    {
        return $this->state(fn (): array => ['type' => NoteType::Note]);
    }

    public function inventory(): static
    {
        return $this->state(fn (): array => ['type' => NoteType::Inventory]);
    }

    public function pinned(): static
    {
        return $this->state(fn (): array => ['pinned_at' => now()]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (): array => ['priority' => NotePriority::High]);
    }
}
