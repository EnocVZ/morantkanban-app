<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->word,
            'description' => $this->faker->paragraph,
            'created_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ];
    }
}
