<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    private $numberOfFixAdmin = 5;
    private $numberOfAdmin = 10;     // primeiros 2 fixos
    public static $used_emails = [];


    private static function stripAccents($stripAccents)
    {
        $from = 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ';
        $to =   'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY';
        $keys = array();
        $values = array();
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        $mapping = array_combine($keys[0], $values[0]);
        return strtr($stripAccents, $mapping);
    }

    public static function randomName($faker, &$gender, &$fullname, &$email)
    {
        $gender = $faker->randomElement(['male', 'female']);
        $firstname = $faker->firstName($gender);
        $lastname = $faker->lastName();
        $secondname = $faker->numberBetween(1, 3) == 2 ? "" : " " . $faker->firstName($gender);
        $number_middlenames = $faker->numberBetween(1, 6);
        $number_middlenames = $number_middlenames == 1 ? 0 : ($number_middlenames >= 5 ? $number_middlenames - 3 : 1);
        $middlenames = "";
        for ($i = 0; $i < $number_middlenames; $i++) {
            $middlenames .= " " . $faker->lastName();
        }
        $fullname = $firstname . $secondname . $middlenames . " " . $lastname;
        $email = strtolower(UsersTableSeeder::stripAccents($firstname) . "." . UsersTableSeeder::stripAccents($lastname) . "@mail.pt");
        $i = 2;
        while (in_array($email, UsersTableSeeder::$used_emails)) {
            $email = strtolower(UsersTableSeeder::stripAccents($firstname) . "." . UsersTableSeeder::stripAccents($lastname) . "." . $i . "@mail.pt");
            $i++;
        }
        UsersTableSeeder::$used_emails[] = $email;
        $gender = $gender == 'male' ? 'M' : 'F';
    }

    private function newFakeAdmin($faker, $fixedUser = 0)
    {
        $fullname = "";
        $email = "";
        $gender = "";
        if ($fixedUser > 0) {
            $fullname = "Administrator " . $fixedUser;
            $email = "a" . $fixedUser . "@mail.pt";
        } else {
            UsersTableSeeder::randomName($faker, $gender, $fullname, $email);
        }
        $createdAt = $faker->dateTimeBetween('-2 years', '-3 months');
        $email_verified_at = $faker->dateTimeBetween($createdAt, '-2 months');
        $updatedAt = $faker->dateTimeBetween($email_verified_at, '-1 months');
        return [
            'name' => $fullname,
            'email' => $email,
            'email_verified_at' => $email_verified_at,
            'password' => bcrypt('123'),
            'remember_token' => Str::random(10),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt
        ];
    }

    public function run()
    {
        $faker = \Faker\Factory::create('pt_PT');
        for ($i = 1; $i <= $this->numberOfAdmin; $i++) {
            $user = $this->newFakeAdmin($faker, $i <= $this->numberOfFixAdmin ? $i : 0);
            DB::table('users')->insert($user);
        }
    }
}
