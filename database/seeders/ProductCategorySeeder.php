<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Reagent Kimia',
                'description' => 'Bahan kimia untuk analisis laboratorium'
            ],
            [
                'name' => 'Alat Kesehatan',
                'description' => 'Peralatan medis dan kesehatan'
            ],
            [
                'name' => 'Consumables',
                'description' => 'Barang habis pakai laboratorium'
            ],
            [
                'name' => 'Instrumen Lab',
                'description' => 'Peralatan instrumen laboratorium'
            ]
        ];

        foreach ($categories as $category) {
            ProductCategory::create($category);
        }
    }
}
