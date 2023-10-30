<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    static public $debits = [
        ['id' => 1, 'type' => 'D', 'name' => 'groceries', 'avg' => 80, 'delta' => 79, 'random_start' => 420],
        ['id' => 2, 'type' => 'D', 'name' => 'restaurant', 'avg' => 30, 'delta' => 25, 'random_start' => 380],
        ['id' => 3, 'type' => 'D', 'name' => 'clothes', 'avg' => 60, 'delta' => 55, 'random_start' => 340],
        ['id' => 4, 'type' => 'D', 'name' => 'shoes', 'avg' => 40, 'delta' => 30, 'random_start' => 325],
        ['id' => 5, 'type' => 'D', 'name' => 'school', 'avg' => 100, 'delta' => 90, 'random_start' => 315],
        ['id' => 6, 'type' => 'D', 'name' => 'services', 'avg' => 60, 'delta' => 50, 'random_start' => 260],
        ['id' => 7, 'type' => 'D', 'name' => 'electricity', 'avg' => 50, 'delta' => 40, 'random_start' => 250],
        ['id' => 8, 'type' => 'D', 'name' => 'phone', 'avg' => 40, 'delta' => 30, 'random_start' => 240],
        ['id' => 9, 'type' => 'D', 'name' => 'fuel', 'avg' => 40, 'delta' => 30, 'random_start' => 210],
        ['id' => 10, 'type' => 'D', 'name' => 'mortgage payment', 'avg' => 200, 'delta' => 190, 'random_start' => 200],
        ['id' => 11, 'type' => 'D', 'name' => 'car payment', 'avg' => 200, 'delta' => 190, 'random_start' => 190],
        ['id' => 12, 'type' => 'D', 'name' => 'entertainment', 'avg' => 40, 'delta' => 35, 'random_start' => 160],
        ['id' => 13, 'type' => 'D', 'name' => 'gadget', 'avg' => 100, 'delta' => 95, 'random_start' => 150],
        ['id' => 14, 'type' => 'D', 'name' => 'computer', 'avg' => 300, 'delta' => 200, 'random_start' => 145],
        ['id' => 15, 'type' => 'D', 'name' => 'vacation', 'avg' => 600, 'delta' => 500, 'random_start' => 140],
        ['id' => 16, 'type' => 'D', 'name' => 'hobby', 'avg' => 100, 'delta' => 90, 'random_start' => 110],
        ['id' => 17, 'type' => 'D', 'name' => 'loan repayment', 'avg' => 500, 'delta' => 450, 'random_start' => 80], // User repays a loan
        ['id' => 18, 'type' => 'D', 'name' => 'loan', 'avg' => 500, 'delta' => 450, 'random_start' => 50],           // User gives a loan to someone
        ['id' => 19, 'type' => 'D', 'name' => 'other expense', 'avg' => 50, 'delta' => 49, 'random_start' => 0],
    ];

    static public $credits = [
        ['id' => 20, 'type' => 'C', 'name' => 'salary', 'avg' => 1000, 'delta' => 600, 'random_start' => 120],
        ['id' => 21, 'type' => 'C', 'name' => 'bonus', 'avg' => 1000, 'delta' => 900, 'random_start' => 115],
        ['id' => 22, 'type' => 'C', 'name' => 'royalties', 'avg' => 1000, 'delta' => 800, 'random_start' => 110],
        ['id' => 23, 'type' => 'C', 'name' => 'interests', 'avg' => 100, 'delta' => 80, 'random_start' => 105],
        ['id' => 24, 'type' => 'C', 'name' => 'gifts', 'avg' => 200, 'delta' => 180, 'random_start' => 95],
        ['id' => 25, 'type' => 'C', 'name' => 'dividends', 'avg' => 200, 'delta' => 190, 'random_start' => 90],
        ['id' => 26, 'type' => 'C', 'name' => 'sales', 'avg' => 80, 'delta' => 78, 'random_start' => 70],
        ['id' => 27, 'type' => 'C', 'name' => 'loan repayment', 'avg' => 500, 'delta' => 450, 'random_start' => 55], // User is repayed of a loan it gave previously
        ['id' => 28, 'type' => 'C', 'name' => 'loan', 'avg' => 500, 'delta' => 450, 'random_start' => 50],                // User receives a loan
        ['id' => 29, 'type' => 'C', 'name' => 'other income', 'avg' => 100, 'delta' => 90, 'random_start' => 0],
    ];

    static public function getRandomDebit()
    {
        $rnd = rand(1, 500);
        foreach (CategoriesTableSeeder::$debits as $item) {
            if ($item['random_start'] < $rnd) {
                return $item;
            }
        }
    }

    static public function getRandomCredit()
    {
        $rnd = rand(1, 150);
        foreach (CategoriesTableSeeder::$credits as $item) {
            if ($item['random_start'] < $rnd) {
                return $item;
            }
        }
    }

    // $racioDebitoCredito normal = 5 (5 movimentos debito para 1 de credito)
    static public function getRandomCategory($racioDebitoCredito = 5)
    {
        if (rand(1, $racioDebitoCredito * 10) < 10) {
            return CategoriesTableSeeder::getRandomCredit();
        } else {
            return CategoriesTableSeeder::getRandomDebit();
        }
    }

    private function reduceArrayToDB($array, $faker)
    {
        $arrayDB = [];
        foreach ($array as $row) {
//            $createdAt = $faker->dateTimeBetween('-2 years', '-3 months');
//            $updatedAt = $faker->dateTimeBetween($createdAt, '-1 months');
            $arrayDB[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'name' => $row['name'],
//                'created_at' => $createdAt,
//                'updated_at' => $updatedAt,
            ];
        }
        return $arrayDB;
    }

    public function run()
    {
        $faker = \Faker\Factory::create('pt_PT');

        DB::table('default_categories')->insert($this->reduceArrayToDB(CategoriesTableSeeder::$debits, $faker));

        DB::table('default_categories')->insert($this->reduceArrayToDB(CategoriesTableSeeder::$credits, $faker));
    }
}

