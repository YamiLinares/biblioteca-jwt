<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->numerify('978##########'),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(Book::CATEGORIES),
            'publication_year' => fake()->numberBetween(1960, now()->year),
            'stock' => fake()->numberBetween(0, 15),
        ];
    }
}
