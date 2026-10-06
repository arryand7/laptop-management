<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Pembaca file import siswa. Hanya membaca baris (tidak menyentuh database)
 * dan memaksa semua sel dibaca sebagai teks agar NIS/NISN/No. HP tidak rusak.
 */
class StudentsSheetImport extends StringValueBinder implements ToArray, WithHeadingRow, WithCustomValueBinder
{
    /**
     * @param array<int, array<string, mixed>> $array
     */
    public function array(array $array): void
    {
        // Data diambil lewat Excel::toArray(); tidak ada proses tambahan.
    }
}
