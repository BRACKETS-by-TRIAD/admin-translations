<?php

namespace Brackets\AdminTranslations\Imports;

use Brackets\AdminTranslations\Translation;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\Log;

class TranslationsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public function collection(Collection $collection) {

        // we don't want to store anything yet, so we leave this method empty

    }

}


