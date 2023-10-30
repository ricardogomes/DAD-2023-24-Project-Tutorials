<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VCardsPhotoSeeder extends Seeder
{
    private $photoPath = 'public/fotos';
    private $photoSeedPath = '';
    private $files_M = [];
    private $files_F = [];

    private function limparFicheirosFotos()
    {
        Storage::deleteDirectory($this->photoPath);
        Storage::makeDirectory($this->photoPath);
    }

    private function preencherNomesFicheirosFotos()
    {
        // Preencher files_M com fotos de Homens e files_F com fotos de mulheres
        $allFiles = collect(File::files($this->photoSeedPath));
        foreach ($allFiles as $f) {
            if (strpos($f->getPathname(), 'M_')) {
                $this->files_M[] = $f->getPathname();
            } else {
                $this->files_F[] = $f->getPathname();
            }
        }
        shuffle($this->files_M);
        shuffle($this->files_F);
    }

    private function newMapRow($card)
    {
        if ($card->photo_url == 'M') {
            if (count($this->files_M) == 0) {
                return null;
            }
            $file = $this->files_M[0];
            array_shift($this->files_M);
        } else {
            if (count($this->files_F) == 0) {
                return null;
            }
            $file = $this->files_F[0];
            array_shift($this->files_F);
        }
        return [
            'phone_number' => $card->phone_number,
            'filename' => $file,
        ];
    }

    private function savePhoto($phone_number, $file, $targetDir)
    {
        $newfilename = $phone_number . "_" . Str::random(6) . '.jpg';
        File::copy($file, $targetDir . '/' . $newfilename);
        DB::table('vcards')->where('phone_number', $phone_number)->update(['photo_url' => $newfilename]);
    }

    private function updatePhotos()
    {
        $mapCards = [];
        $cards = DB::select('select phone_number, photo_url from vcards order by phone_number asc');
        for ($i = 0; $i < VCardsTableSeeder::$numberOfFixCards; $i++) {
            $newMapRow = $this->newMapRow($cards[0]);
            if ($newMapRow) {
                $mapCards[] = $newMapRow;
            }
            array_shift($cards);
        }
        shuffle($cards);
        foreach ($cards as $card) {
            $newMapRow = $this->newMapRow($card);
            if ($newMapRow) {
                $mapCards[] = $newMapRow;
            } else {
                if ((count($this->files_M) == 0) && (count($this->files_F) == 0)) {
                    break;
                }
            }
        }
        DB::update('update vcards set photo_url = null');
        $i = 0;
        $total = count($mapCards);
        $targetDir = storage_path('app/' . $this->photoPath);
        foreach ($mapCards as $mapCard) {
            $i++;
            $this->savePhoto($mapCard['phone_number'], $mapCard['filename'], $targetDir);
            if ($i % 20 == 0) {
                $this->command->info("Updated Photo of vCard $i / $total");
            }
        }
    }


    public function run()
    {
        $this->command->info("Updating Photos of vCards");
        $this->photoSeedPath = database_path('seeders/fotos');
        $this->limparFicheirosFotos();
        $this->preencherNomesFicheirosFotos();
        $this->updatePhotos();
    }
}
