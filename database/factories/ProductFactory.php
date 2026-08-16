<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $category = fake()->randomElement(array_keys(Product::CATEGORIES));

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 99999),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(5, 150) * 1000,
            'stock' => fake()->numberBetween(0, 50),
            'category' => $category,
            'custom_category' => $category === 'lainnya' ? fake()->randomElement(['Perlengkapan Harian', 'Kebutuhan Kelas']) : null,
            'is_active' => true,
        ];
    }
}
