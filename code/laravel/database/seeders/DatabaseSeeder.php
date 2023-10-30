<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public static $seedType = "small";

    public function run()
    {
        $this->command->info("-----------------------------------------------");
        $this->command->info("START of database seeder");
        $this->command->info("-----------------------------------------------");

        DatabaseSeeder::$seedType = $this->command->choice('What is the size of seed data (choose "full" for publishing)?', ['small', 'full'], 0);

        DB::statement("SET foreign_key_checks=0");

        DB::table('users')->delete();
        DB::table('default_categories')->delete();
        DB::table('categories')->delete();
        DB::table('vcards')->delete();
        DB::table('transactions')->delete();

        DB::statement('ALTER TABLE users AUTO_INCREMENT = 0');
        DB::statement('ALTER TABLE default_categories AUTO_INCREMENT = 0');
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 0');
        DB::statement('ALTER TABLE transactions AUTO_INCREMENT = 0');

        DB::statement("SET foreign_key_checks=1");


        $this->call(CategoriesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(VCardsTableSeeder::class);
        $this->call(VCardsPhotoSeeder::class);

        $this->command->info("-----------------------------------------------");
        $this->command->info("END of database seeder");
        $this->command->info("-----------------------------------------------");
    }
}
