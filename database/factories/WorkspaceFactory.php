<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'website' => $this->faker->url,
            'type' => $this->faker->unique()->randomElement(['Operation', 'Education', 'Marketing', 'Engineering-IT', 'Small Business', 'Other']),
            'description' => $this->faker->paragraph,
            'created_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ];
    }
}
