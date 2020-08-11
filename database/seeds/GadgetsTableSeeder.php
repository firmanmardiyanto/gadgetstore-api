<?php

use Illuminate\Database\Seeder;

class GadgetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $gadgets = [];
        $faker = Faker\Factory::create();
        $image_categories = ['abstract', 'animals', 'business', 'cats', 'city', 'food', 'nature', 'technics', 'transport'];
        for ($i = 0; $i < 25; $i++) {
            $merk = $faker->sentence(mt_rand(3, 6));
            $merk = str_replace('.', '', $merk);
            $slug = str_replace(' ', '-', strtolower($merk));
            $category = $image_categories[mt_rand(0, 8)];
            $cover_path = '/xampp/htdocs/gadgetstore-api/public/images/gadgets';
            $cover_fullpath = $faker->image($cover_path, 300, 500, $category, true, true, $category);
            $cover = str_replace($cover_path . '/', '', $cover_fullpath);
            $gadgets[$i] = [
                'merk' => $merk, 
                'slug' => $slug, 
                'description' => $faker->text(255), 
                'author' => $faker->name, 
                'publisher' => $faker->company, 
                'cover' => $cover, 
                'price' => mt_rand(1, 10) * 50000, 
                'weight' => 0.5, 
                'status' => 'PUBLISH', 
                'created_at' => Carbon\Carbon::now(),
            ];
        }
        DB::table('gadgets')->insert($gadgets);
    }
}
