<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => 'General'], [
            'description' => 'Categoria general para productos iniciales.',
            'is_active' => true,
        ]);

        Product::firstOrCreate(
            ['barcode' => '1000000000001'],
            [
                'name' => 'Producto demo',
                'category_id' => $category->id,
                'description' => 'Producto base para validar el modulo.',
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 5,
                'is_active' => true,
            ]
        );
    }
}
