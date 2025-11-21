<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
	public function run(): void
	{
		$now = now();
		$products = [
			['id' => 1, 'sku' => 'DR-0001', 'name' => 'Blue Summer Dress', 'description' => 'Lightweight summer dress with a flattering A-line cut and breathable fabric.', 'price' => 89.50,  'image_url' => 'dress-blue.jpg',    'size' => 'S,M,L', 'color' => 'blue', 'stock_quantity' => 20, 'created_at' => $now, 'updated_at' => $now],
			['id' => 2, 'sku' => 'DR-0002', 'name' => 'Dark Purple Evening Gown', 'description' => 'Elegant floor-length evening gown with a subtle shimmer and fitted waist.', 'price' => 159.99, 'image_url' => 'dress-darkpurple.jpg','size' => 'XS,S,M','color' => 'dark purple', 'stock_quantity' => 6, 'created_at' => $now, 'updated_at' => $now],
			['id' => 3, 'sku' => 'DR-0003', 'name' => 'Green Cocktail Dress', 'description' => 'Chic mid-length cocktail dress featuring tailored seams and soft fabric.', 'price' => 129.00, 'image_url' => 'dress-green.jpg',   'size' => 'S,M,L', 'color' => 'green', 'stock_quantity' => 8, 'created_at' => $now, 'updated_at' => $now],
			['id' => 4, 'sku' => 'DR-0004', 'name' => 'Lilac Floral Dress', 'description' => 'Feminine lilac dress with delicate floral print and ruffle details.', 'price' => 99.00,  'image_url' => 'dress-lilac.jpg',   'size' => 'S,M,L', 'color' => 'lilac', 'stock_quantity' => 14, 'created_at' => $now, 'updated_at' => $now],
			['id' => 5, 'sku' => 'DR-0005', 'name' => 'Red Evening Dress', 'description' => 'Classic red dress designed for memorable nights out; fitted and elegant.', 'price' => 149.99, 'image_url' => 'dress-red.jpg',    'size' => 'S,M,L', 'color' => 'red', 'stock_quantity' => 12, 'created_at' => $now, 'updated_at' => $now],
			['id' => 6, 'sku' => 'DR-0006', 'name' => 'White Lace Dress', 'description' => 'Timeless white lace dress with a delicate bodice and soft skirt.', 'price' => 119.00, 'image_url' => 'dress-white.jpg',  'size' => 'XS,S,M','color' => 'white', 'stock_quantity' => 10, 'created_at' => $now, 'updated_at' => $now],
			['id' => 7, 'sku' => 'DR-0007', 'name' => 'Black Cocktail Dress', 'description' => 'Versatile little black dress suitable for cocktail parties and dinners.', 'price' => 139.00, 'image_url' => 'dress-black.jpg',  'size' => 'S,M,L', 'color' => 'black', 'stock_quantity' => 9, 'created_at' => $now, 'updated_at' => $now],
			['id' => 8, 'sku' => 'DR-0008', 'name' => 'Yellow Sundress', 'description' => 'Bright sundress with breezy fabric and playful silhouette.', 'price' => 74.50,  'image_url' => 'dress-yellow.jpg',  'size' => 'S,M,L', 'color' => 'yellow', 'stock_quantity' => 18, 'created_at' => $now, 'updated_at' => $now],
			['id' => 9, 'sku' => 'DR-0009', 'name' => 'Pink Party Dress', 'description' => 'Playful pink dress with a flared skirt and seamed bodice.', 'price' => 95.00,  'image_url' => 'dress-pink.jpg',    'size' => 'S,M,L', 'color' => 'pink', 'stock_quantity' => 11, 'created_at' => $now, 'updated_at' => $now],
			['id' => 10,'sku' => 'DR-0010', 'name' => 'Patterned Wrap Dress', 'description' => 'Wrap-style dress with a unique pattern and comfortable fit.', 'price' => 109.00, 'image_url' => 'dress-pattern.jpg', 'size' => 'S,M,L', 'color' => 'multi', 'stock_quantity' => 7, 'created_at' => $now, 'updated_at' => $now],
		];

		DB::table('products')->insertOrIgnore($products);
	}
}

