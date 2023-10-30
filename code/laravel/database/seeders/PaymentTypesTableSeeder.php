<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentTypesTableSeeder extends Seeder
{
    static private $sum_debit_weight;
    static private $sum_credit_weight;
    static public $paymentTypes = [
        ['code' => 'VCARD', 'name' => 'vCard', 'description' => 'vCard phone number', 'deleted' => false, 'debit_weight' => 100, 'credit_weight' => 100],
        ['code' => 'PAYPAL', 'name' => 'PayPal', 'description' => 'PayPal email account', 'deleted' => false, 'debit_weight' => 50, 'credit_weight' => 50],
        ['code' => 'MBWAY', 'name' => 'MB WAY', 'description' => 'MB WAY phone number', 'deleted' => false, 'debit_weight' => 50, 'credit_weight' => 100],
        ['code' => 'IBAN', 'name' => 'Bank Transfer', 'description' => 'Bank Transfer - IBAN account code (2 letters and 23 digits)', 'deleted' => false, 'debit_weight' => 100, 'credit_weight' => 400],
        ['code' => 'MB', 'name' => 'Multibanco', 'description' => 'Multibanco payment - Entity (5 digits) + Reference (9 digits)', 'deleted' => false, 'debit_weight' => 400, 'credit_weight' => 0],
        ['code' => 'VISA', 'name' => 'Visa Card', 'description' => 'Visa Card number (16 digits)', 'deleted' => false, 'debit_weight' => 100, 'credit_weight' => 20],
        ['code' => 'MASTERCARD', 'name' => 'Mastercard', 'description' => 'Mastercard number  (16 digits)', 'deleted' => true, 'debit_weight' => 50, 'credit_weight' => 20],
    ];

    static private function calculateSumWeight()
    {
        PaymentTypesTableSeeder::$sum_debit_weight = 0;
        PaymentTypesTableSeeder::$sum_credit_weight = 0;
        foreach (PaymentTypesTableSeeder::$paymentTypes as $t) {
            PaymentTypesTableSeeder::$sum_debit_weight += $t['debit_weight'];
            PaymentTypesTableSeeder::$sum_credit_weight += $t['credit_weight'];
        }
    }

    static public function getRandomDebit()
    {
        $rnd = rand(1, PaymentTypesTableSeeder::$sum_debit_weight);
        $total = 0;
        foreach (PaymentTypesTableSeeder::$paymentTypes as $item) {
            if ($rnd < ($item['debit_weight'] + $total)) {
                return $item['code'];
            }
            $total += $item['debit_weight'];
        }
        return PaymentTypesTableSeeder::$paymentTypes[0]['code'];
    }

    static public function getRandomCredit()
    {
        $rnd = rand(1, PaymentTypesTableSeeder::$sum_credit_weight);
        $total = 0;
        foreach (PaymentTypesTableSeeder::$paymentTypes as $item) {
            if ($rnd < ($item['credit_weight'] + $total)) {
                return $item['code'];
            }
            $total += $item['credit_weight'];
        }
        return PaymentTypesTableSeeder::$paymentTypes[0]['code'];
    }

    static public function getRandomRef($faker, $originalVCardPhoneNumber, &$paymentType)
    {
        if ($paymentType == 'VCARD') {
            $pairCard = $faker->randomElement(VCardsTableSeeder::$vCardsCreated)['phoneNumber'];
            $i = 0;
            // Só entra numa transacao par se vCard for diferent e se tem saldo > 1
            while (($i < 10) && (($pairCard == $originalVCardPhoneNumber) || (VCardsTableSeeder::$vCardsCreated[$pairCard]['balance'] <= 1))) {
                $i++;
                $pairCard = $faker->randomElement(VCardsTableSeeder::$vCardsCreated)['phoneNumber'];
            }
            // Se não encontrou um pair vCard, muda de tipo de pagamento
            if ($i >= 10) {
                $paymentType = 'IBAN';
            } else {
                return $pairCard;
            }
        }
        switch ($paymentType) {
            case 'PAYPAL':
                return $faker->email();
            case 'MBWAY':
                return '9' . $faker->randomNumber($nbDigits = 8, $strict = true);
            case 'IBAN':
                return 'PT' . $faker->randomNumber($nbDigits = 8, $strict = true) . $faker->randomNumber($nbDigits = 8, $strict = true) . $faker->randomNumber($nbDigits = 7, $strict = true);
            case 'MB':
                return $faker->randomNumber($nbDigits = 5, $strict = true) . '-' . $faker->randomNumber($nbDigits = 9, $strict = true);
            case 'VISA':
            case 'MASTERCARD':
                return $faker->randomNumber($nbDigits = 8, $strict = true) . $faker->randomNumber($nbDigits = 8, $strict = true);
            default:
                return '##########';
        }
    }

    static private function removeDeleted()
    {
        foreach (PaymentTypesTableSeeder::$paymentTypes as $key => $t) {
            if ($t['deleted']) {
                unset(PaymentTypesTableSeeder::$paymentTypes[$key]);
            }
        }
    }

    private function reduceArrayToDB($faker, $array)
    {
        $arrayDB = [];
        foreach ($array as $row) {
            $createdAt = $faker->dateTimeBetween('-2 years', '-3 months');
            $updatedAt = $faker->dateTimeBetween($createdAt, '-1 months');
            $arrayDB[] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
                'deleted_at' => $row['deleted'] ? $updatedAt : null,
            ];
        }
        return $arrayDB;
    }

    public function run()
    {
        $faker = \Faker\Factory::create('pt_PT');
        DB::table('payment_types')->insert($this->reduceArrayToDB($faker, PaymentTypesTableSeeder::$paymentTypes));
        PaymentTypesTableSeeder::removeDeleted();
        PaymentTypesTableSeeder::calculateSumWeight();
    }
}
