<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VCardsTableSeeder extends Seeder
{
    static private $sum_debit_weight;
    static private $sum_credit_weight;
    static private $paymentTypes = [
        ['code' => 'VCARD', 'debit_weight' => 100, 'credit_weight' => 500],
        ['code' => 'PAYPAL', 'debit_weight' => 50, 'credit_weight' => 50],
        ['code' => 'MBWAY', 'debit_weight' => 50, 'credit_weight' => 100],
        ['code' => 'IBAN','debit_weight' => 100, 'credit_weight' => 400],
        ['code' => 'MB',  'debit_weight' => 600, 'credit_weight' => 0],
        ['code' => 'VISA', 'debit_weight' => 100, 'credit_weight' => 20],
    ];


    public static $numberOfFixCards = 20;
    public static $vCardsCreated = [];

    private $numberOfDays = 61;
    private $freqOfOneCard = 172800;  // 1 movimento de N em N segundos
    // (86400 = 1 por dia; 43200 = 2 por dia; 172800 = 1 em cada 2 dias)
    // Os 3 próximos valores serão calculados a partir de $freqOfOneCard e do total de vcards presentes nesse dia
    private $avgFrequency = 600;   // Um movimento a cada X segundos (em média)
    private $deltaFreq    = 600;  // Variação da frequencia (em segundos) - calcula o random a partir da frequencia e delta
    // mas valor minimo é sempre 10 segundos
    private $newCardOnXTransactions = 20; // Check to create a new vCard from X transactions

    private $limiteMax    = 10000; // Limite máximo para uma conta
    private $transferRatio = 15;
    private $noCategoryRatio = 10;
    private $ratioDebitCredit = 5;


    static private function calculateSumWeight()
    {
        self::$sum_debit_weight = 0;
        self::$sum_credit_weight = 0;
        foreach (self::$paymentTypes as $t) {
            self::$sum_debit_weight += $t['debit_weight'];
            self::$sum_credit_weight += $t['credit_weight'];
        }
    }

    static public function getRandomCredit()
    {
        $rnd = rand(1, self::$sum_credit_weight);
        $total = 0;
        foreach (self::$paymentTypes as $item) {
            if ($rnd < ($item['credit_weight'] + $total)) {
                return $item['code'];
            }
            $total += $item['credit_weight'];
        }
        return self::$paymentTypes[0]['code'];
    }

    static public function getRandomDebit()
    {
        $rnd = rand(1, self::$sum_debit_weight);
        $total = 0;
        foreach (self::$paymentTypes as $item) {
            if ($rnd < ($item['debit_weight'] + $total)) {
                return $item['code'];
            }
            $total += $item['debit_weight'];
        }
        return self::$paymentTypes[0]['code'];
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


    private function newFakeVCard($faker, $fixedPhone = 0, $created_date = null)
    {
        $fullname = "";
        $email = "";
        $gender = "";
        UsersTableSeeder::randomName($faker, $gender, $fullname, $email);
        do {
            $phoneNumber = '900000000';
            if ($fixedPhone > 0) {
                $phoneNumber += $fixedPhone;
            } else {
                $phoneNumber = '9' . $faker->randomNumber($nbDigits = 8, $strict = true);
            }
        } while (array_key_exists($phoneNumber, VCardsTableSeeder::$vCardsCreated));
        $createdAt = $created_date ?? $faker->dateTimeBetween('-2 years', '-3 months');
        VCardsTableSeeder::$vCardsCreated[$phoneNumber] = [
            'phoneNumber' => $phoneNumber,
            'balance' => 0,
        ];
        $updatedAt = $faker->dateTimeBetween($createdAt);
        $deletedAt = rand(1, 20) == 2 ? $updatedAt : null;
        $blocked = rand(1, 20) == 2 ? '1' : '0';
        $max_debit = rand(1, 10) == 2 ? 0 : 5000;
        if ($max_debit == 0) {
            $max_debit = 5000 + rand(-40, 150) * 100;
        }
        return [
            'phone_number' => $phoneNumber,
            'name' => $fullname,
            'email' => $email,
            'photo_url' => $gender,
            'password' => bcrypt('123'),
            'confirmation_code' => bcrypt('123'),
            'blocked' => $blocked,
            'balance' => 0,
            'max_debit' => $max_debit,
            'custom_options' => null,
            'custom_data' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'deleted_at' => $deletedAt
        ];
    }
    private function insertOneVCard($vCard)
    {
        DB::table('vcards')->insert($vCard);
        DB::insert(
            //'insert into categories (type, name, vcard, created_at, updated_at, deleted_at) select type, name, ?, ?, ?, ? from default_categories order by type desc',
            'insert into categories (type, name, vcard, deleted_at) select type, name, ?, ? from default_categories order by type desc',
            [
                $vCard['phone_number'],
//                $vCard['created_at'],
//                $vCard['updated_at'],
                $vCard['deleted_at']
            ]
        );
    }

    private function buildFixedVCards($faker, $created_date)
    {
        $this->command->info("Building initial (fixed) " .  VCardsTableSeeder::$numberOfFixCards .  " vCards");
        for ($i = 1; $i <= VCardsTableSeeder::$numberOfFixCards; $i++) {
            $vCard = $this->newFakeVCard($faker, $i, $created_date);
            if ($i == 3) {
                $vCard['deleted_at'] = $vCard['updated_at'];
                $vCard['blocked'] = 0;
            } elseif ($i == 4) {
                $vCard['blocked'] = 1;
                $vCard['deleted_at'] = null;
            } elseif ($i == 5) {
                $vCard['blocked'] = 1;
                $vCard['deleted_at'] = $vCard['updated_at'];
            }
            $this->insertOneVCard($vCard);
        }
        $this->command->info("Initial " . VCardsTableSeeder::$numberOfFixCards . " vCards built!");
    }

    private function getRandomValue($category)
    {
        $avg = $category['avg'] * 100;
        $delta = intval($avg * 0.8);
        $valueInt = rand($avg - $delta, $avg + $delta);
        return round($valueInt / 100, 2);
    }

    private function getRandomPayment($faker, $originalVCardPhoneNumber, $type, &$paymentType, &$paymentRef)
    {
        $paymentType = $type == 'C' ? self::getRandomCredit() : self::getRandomDebit();
        $paymentRef =  self::getRandomRef($faker, $originalVCardPhoneNumber, $paymentType);
    }

    private function createPairTransaction($faker, &$transaction)
    {
        $pairVCard = $transaction['payment_reference'];
        $transaction['pair_vcard'] = $pairVCard;
        $saldoPair = VCardsTableSeeder::$vCardsCreated[$pairVCard]['balance'];
        // Se for para debitar no cartão par e este não tem saldo suficiente (s), emenda valores:
        if (($transaction['type'] == 'C') && ($saldoPair < $transaction['value'])) {
            $transaction['value'] = $saldoPair;
            $transaction['new_balance'] = $transaction['old_balance'] + $saldoPair;
        }
        $oldBalance = VCardsTableSeeder::$vCardsCreated[$pairVCard]['balance'];
        $newBalance = $transaction['type'] == 'C' ? $oldBalance - $transaction['value'] : $oldBalance + $transaction['value'];
        return [
            'vcard' => $pairVCard,
            'date' =>  $transaction['date'],
            'datetime' =>  $transaction['datetime'],
            'type' => $transaction['type'] == 'C' ? 'D' : 'C',
            'value' => $transaction['value'],
            'old_balance' =>  $oldBalance,
            'new_balance' => $newBalance,
            'payment_type' => $transaction['payment_type'],
            'payment_reference' => $transaction['vcard'],
            'pair_transaction' => null,
            'pair_vcard' => $transaction['vcard'],
            'category_id' => null,
            'description' => rand(0, 20) == 3 ? $faker->realText() : null,
            'custom_options' => null,
            'custom_data' => null,
            'created_at' => $transaction['created_at'],
            'updated_at' => $transaction['created_at'],
            'deleted_at' => null
        ];
    }

    private function createTransaction($faker, $d)
    {
        $vCard = $faker->randomElement(VCardsTableSeeder::$vCardsCreated);
        if ($vCard['balance'] < 5) {
            $category = CategoriesTableSeeder::getRandomCredit();
        } elseif ($vCard['balance'] > 10000) {
            $category = CategoriesTableSeeder::getRandomDebit();
        } else {
            $category = CategoriesTableSeeder::getRandomCategory($this->ratioDebitCredit);
        }
        $categoryId = $category['id'];
        $type = $category['type'];
        $value = $this->getRandomValue($category);

        $oldBalance = $vCard['balance'];
        $newBalance = $oldBalance;
        if ($type == 'D') {
            if ($value > 5000) {
                $value = 5000;
            }
            if ($value > $vCard['balance']) {
                $value = $vCard['balance'];
            }
            $newBalance = $vCard['balance'] - $value;
        } else {
            $newBalance = $vCard['balance'] + $value;
        }

        $paymentType = null;
        $paymentRef = null;
        $this->getRandomPayment($faker, $vCard['phoneNumber'], $type, $paymentType, $paymentRef);

        $datetime = $d->format('Y-m-d h:i:s');
        return [
            'vcard' => $vCard['phoneNumber'],
            'date' =>  $d->format('Y-m-d'),
            'datetime' =>  $datetime,
            'type' => $type,
            'value' => $value,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'payment_type' => $paymentType,
            'payment_reference' => $paymentRef,
            'pair_transaction' => null,
            'pair_vcard' => null,
            'category_id' => $categoryId, //rand(0, 10) < 4 ? $categoryId : null,
            'description' => rand(0, 20) == 3 ? $faker->realText() : null,
            'custom_options' => null,
            'custom_data' => null,
            'created_at' => $datetime,
            'updated_at' => $datetime,
            'deleted_at' => null
        ];
    }

    private function createTransactionToCloseAccount($faker, $vCardPhoneNumber, $d)
    {
        $vCard = VCardsTableSeeder::$vCardsCreated[$vCardPhoneNumber];
        if ($vCard['balance'] == 0) {
            return null;
        }
        $category = CategoriesTableSeeder::getRandomDebit();
        $categoryId = $category['id'];
        $type = $category['type'];
        $value =  $vCard['balance'];
        $oldBalance = $vCard['balance'];
        $newBalance = $vCard['balance'] - $value;

        $paymentType = 'IBAN';
        $paymentRef = 'PT' . $faker->randomNumber($nbDigits = 8, $strict = true) . $faker->randomNumber($nbDigits = 8, $strict = true) . $faker->randomNumber($nbDigits = 7, $strict = true);

        $datetime = $d->format('Y-m-d h:i:s');
        return [
            'vcard' => $vCard['phoneNumber'],
            'date' =>  $d->format('Y-m-d'),
            'datetime' =>  $datetime,
            'type' => $type,
            'value' => $value,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'payment_type' => $paymentType,
            'payment_reference' => $paymentRef,
            'pair_transaction' => null,
            'pair_vcard' => null,
            'category_id' => null,
            'description' => 'This transaction sets the vCard balance to 0 so it can be soft deleted',
            'custom_options' => null,
            'custom_data' => null,
            'created_at' => $datetime,
            'updated_at' => $datetime,
            'deleted_at' => null
        ];
    }

    private function saveTransactions($transactions)
    {
        DB::table('transactions')->insert($transactions);
    }

    private function calculateRandomAvgFrequency()
    {
        $totalVCards = count(VCardsTableSeeder::$vCardsCreated);
        $halfFreqOfOneCard = intval($this->freqOfOneCard / 2);
        $freqOneCard = $this->freqOfOneCard + rand(-1 * $halfFreqOfOneCard, $halfFreqOfOneCard);
        $this->avgFrequency = intval($freqOneCard / $totalVCards);
        $this->deltaFreq = $this->avgFrequency > 10 ? $this->avgFrequency : 10;
        // Verifica 1 vez por dia (e só de 3 em 3 é que cria um cartão)
        $this->newCardOnXTransactions = intval(86400 / $this->avgFrequency);
        $this->newCardOnXTransactions = $this->newCardOnXTransactions < 1 ? 1 : $this->newCardOnXTransactions;
    }

    private function fillPairTransactions()
    {
        $pairs = DB::select('select id from transactions where payment_type="VCARD"');
        $totalTransactions = count($pairs);
        for ($i = 1; $i < $totalTransactions; $i += 2) {
            DB::table('transactions')->where('id', $pairs[$i]->id)->update(['pair_transaction' => $pairs[$i - 1]->id]);
            DB::table('transactions')->where('id', $pairs[$i - 1]->id)->update(['pair_transaction' => $pairs[$i]->id]);
            if ($i % 101 == 0) {
                $this->command->info("Pair $i / $totalTransactions handled");
            }
        }
        $this->command->info("All pair transactions handled");
    }

    private function fillCategories()
    {
        $startIdCategory = DB::select('select vcard, min(id) as min_id from categories group by vcard');
        $totalCards = count($startIdCategory);
        $i = 0;
        foreach ($startIdCategory as $vcardCategory) {
            $i++;
            DB::update('update transactions set category_id = category_id + ? where category_id is not null and vcard = ?', [$vcardCategory->min_id - 1, $vcardCategory->vcard]);
            if ($i % 10 == 0) {
                $this->command->info("Categories of the transactions on vCard $i / $totalCards handled");
            }
        }
        $this->command->info("All categories of the transactions on vCard handled");
    }

    private function updateSoftDeletesTransactions($faker)
    {
        $d = \Carbon\Carbon::today()->subDays(1)->addHours(1);
        $transactions = [];
        $vcardsToClose = [];

        $this->command->info("Adding transactions to close soft deleted vCards");
        $softdeletedCards = DB::select('select phone_number from vcards where deleted_at is not null');
        foreach ($softdeletedCards as $vcard) {
            $newTransaction =  $this->createTransactionToCloseAccount($faker, $vcard->phone_number, $d);
            if ($newTransaction) {
                $transactions[] = $newTransaction;
                VCardsTableSeeder::$vCardsCreated[$newTransaction['vcard']]['balance'] = $newTransaction['new_balance'];
            }
            $vcardsToClose[] = [
                'phone_number' => $vcard->phone_number,
                'deleted_at' => $d->format('Y-m-d h:i:s')
            ];
            $d->addSeconds(rand(20, 60));
        }
        $this->saveTransactions($transactions);
        $total = count($transactions);
        $transactions = [];
        $i = 0;

        foreach ($vcardsToClose as $vcard) {
            $i++;
            $this->command->info("Updating softdeletes for vCards and its transactions and categories ($i / $total) ");
            DB::update(
                'update vcards set deleted_at = ?, updated_at=? where phone_number=?',
                [
                    $vcard['deleted_at'],
                    $vcard['deleted_at'],
                    $vcard['phone_number']
                ]
            );
            DB::update(
                'update transactions set deleted_at = ?, updated_at=? where vcard=?',
                [
                    $vcard['deleted_at'],
                    $vcard['deleted_at'],
                    $vcard['phone_number']
                ]
            );
            DB::update(
                'update categories set deleted_at = ? where vcard=?',
                [
                    $vcard['deleted_at'],
                    $vcard['phone_number']
                ]
            );
        }
    }

    private function updateBalaces()
    {
        $totalCards = count(VCardsTableSeeder::$vCardsCreated);
        $i = 0;
        foreach (VCardsTableSeeder::$vCardsCreated as $vcard) {
            $i++;
            DB::update('update vcards set balance = ? where phone_number = ?', [$vcard['balance'], $vcard['phoneNumber']]);
            if ($i % 10 == 0) {
                $this->command->info("Update balance of vCard $i / $totalCards");
            }
        }
        $this->command->info("All balances were updated");
    }

    public function run()
    {
        self::calculateSumWeight();

        if (DatabaseSeeder::$seedType == "full") {
            $this->numberOfDays = 2 * 365;   // 2 ANOS
        } else {
            $this->numberOfDays = 61;      // +- 2 meses
        }

        $this->command->info("vCards & Transactions seeder - Start");

        $faker = \Faker\Factory::create('pt_PT');

        $yestarday = \Carbon\Carbon::today()->subDays(1);
        $this->start_date = $yestarday->copy();
        $this->start_date->subDays($this->numberOfDays);
        $d = $this->start_date->copy();
        $i = 0;

        $this->buildFixedVCards($faker, $d);

        $this->command->info("Starting to create transactions");

        $transactions = [];
        $this->calculateRandomAvgFrequency();
        while ($d->lessThanOrEqualTo($yestarday)) {
            $newTransaction = $this->createTransaction($faker, $d);
            $pairTransaction = null;
            if ($newTransaction['payment_type'] == 'VCARD') {
                $pairTransaction = $this->createPairTransaction($faker, $newTransaction);
            }
            // Adiciona nova transacao
            $transactions[] = $newTransaction;
            VCardsTableSeeder::$vCardsCreated[$newTransaction['vcard']]['balance'] = $newTransaction['new_balance'];
            if ($pairTransaction) {
                // Se houver par, adiciona par
                $transactions[] = $pairTransaction;
                VCardsTableSeeder::$vCardsCreated[$pairTransaction['vcard']]['balance'] = $pairTransaction['new_balance'];
            }
            // if (rand(1, $this->transferRatio) == 1) {
            //     $transactionsPair = $this->createTransfer($faker, $d);
            //     $transactions[] = $transactionsPair[0];
            //     $transactions[] = $transactionsPair[1];
            // } else {
            //     $transactions[] = $this->createTransaction($faker, $d);
            // }

            $i++;
            if ($i % 2000 == 0) {  // de tempo a tempo, recalcula a frequencia de movimentos
                $this->calculateRandomAvgFrequency();
            }
            if ($i % 1000 == 0) {
                $this->saveTransactions($transactions);
                $transactions = [];
                $this->command->info("Created 1000 transactions (total so far= " . $i . ") at date " . $d->format('Y-m-d'));
            }

            if ($i % $this->newCardOnXTransactions == 0) {
                if (rand(0, 2) == 1) {
                    $vCard = $this->newFakeVCard($faker, 0, $d);
                    $this->insertOneVCard($vCard);
                    $this->command->info("Added a new vCards on " . $d->format('Y-m-d'));
                }
            }

            $deltaSegundos = $this->avgFrequency - intdiv($this->deltaFreq, 2) + rand(0, $this->deltaFreq);
            if ($d->month == 12) {
                $deltaSegundos = intval($deltaSegundos * 0.8);
            } else if ($d->month == 8) {
                $deltaSegundos = intval($deltaSegundos * 0.9);
            } else if ($d->month == 2) {
                $deltaSegundos = intval($deltaSegundos * 1.3);
            } else if ($d->month == 3) {
                $deltaSegundos = intval($deltaSegundos * 1.1);
            }

            if ($deltaSegundos < 1) {
                $deltaSegundos = 1;
            }
            $d->addSeconds($deltaSegundos);
        }
        if ($transactions) {
            $this->saveTransactions($transactions);
            $this->command->info("Created last " . count($transactions) . " transactions");
        }

        $this->command->info("Fill information about pair transactions");
        $this->fillPairTransactions();

        $this->command->info("Fill information about transactions categories");
        $this->fillCategories();

        $this->command->info("Softdelete transactions");
        $this->updateSoftDeletesTransactions($faker);

        $this->command->info("Update information about vCard balance");
        $this->updateBalaces();
    }
}

