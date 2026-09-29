<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LocationPackageImporter
{
    private const MAX_ROWS = 2000;

    public function import(Restaurant $restaurant, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('The CSV file could not be opened.');
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false) {
                throw new RuntimeException('The CSV file is empty.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $header = array_map(static fn ($value) => strtolower(trim((string) $value)), $header);
            $required = ['number', 'type', 'name', 'is_active'];
            if ($header !== $required) {
                throw new RuntimeException('CSV columns must be exactly: number,type,name,is_active (in that order).');
            }

            $rows = [];
            $seenNumbers = [];
            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) {
                    continue;
                }
                if (count($values) !== 4) {
                    throw new RuntimeException("Row {$line} must have exactly four columns.");
                }
                [$number, $type, $name, $active] = array_map(static fn ($value) => trim((string) $value), $values);
                $type = strtolower($type);
                $active = strtolower($active);

                if ($number === '' || mb_strlen($number) > 50 || preg_match('/[\x00-\x1F]/', $number)) {
                    throw new RuntimeException("Row {$line} has an invalid number (1–50 characters, no control characters).");
                }
                if (! in_array($type, ['room', 'table'], true)) {
                    throw new RuntimeException("Row {$line} type must be room or table.");
                }
                if (mb_strlen($name) > 255) {
                    throw new RuntimeException("Row {$line} name must be 255 characters or fewer.");
                }
                if (! in_array($active, ['1', '0', 'true', 'false', 'yes', 'no'], true)) {
                    throw new RuntimeException("Row {$line} is_active must be 1/0, true/false, or yes/no.");
                }
                $numberKey = mb_strtolower($number);
                if (isset($seenNumbers[$numberKey])) {
                    throw new RuntimeException("The number '{$number}' appears more than once in the CSV.");
                }
                $seenNumbers[$numberKey] = true;
                $rows[] = [
                    'number' => $number,
                    'type' => $type,
                    'name' => $name !== '' ? $name : null,
                    'active' => in_array($active, ['1', 'true', 'yes'], true),
                ];
                if (count($rows) > self::MAX_ROWS) {
                    throw new RuntimeException('The CSV may contain at most '.self::MAX_ROWS.' locations.');
                }
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw new RuntimeException('Add at least one room or table row to the CSV.');
        }

        $added = 0;
        $updated = 0;
        DB::transaction(function () use ($restaurant, $rows, &$added, &$updated): void {
            foreach ($rows as $row) {
                $existing = DB::table('restaurant_tables')
                    ->where('restaurant_id', $restaurant->id)
                    ->where('table_number', $row['number'])
                    ->first();
                $values = [
                    'location_type' => $row['type'],
                    'table_name' => $row['name'],
                    'is_active' => $row['active'],
                    'updated_at' => now(),
                ];
                if ($existing) {
                    DB::table('restaurant_tables')->where('id', $existing->id)->update($values);
                    $updated++;
                } else {
                    DB::table('restaurant_tables')->insert($values + [
                        'restaurant_id' => $restaurant->id,
                        'table_number' => $row['number'],
                        'qr_code_path' => null,
                        'created_at' => now(),
                    ]);
                    $added++;
                }
            }
        });

        return ['added' => $added, 'updated' => $updated, 'total' => count($rows)];
    }
}
