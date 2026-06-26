<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TsaGlobalAirportSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('modules/Flight/Resources/airports.xlsx');

        if (!file_exists($file)) {
            throw new \RuntimeException("Airport resource file not found: {$file}");
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            throw new \RuntimeException('Airport resource file is empty.');
        }

        $headerRow = array_shift($rows);
        $headers = [];

        foreach ($headerRow as $column => $heading) {
            $key = strtolower(trim((string) $heading));
            $key = str_replace([' ', '-'], '_', $key);
            $headers[$column] = $key;
        }

        $now = now();
        $batch = [];
        $batchSize = 500;

        $seen = [];
        $imported = 0;
        $skipped = 0;
        $duplicates = 0;

        foreach ($rows as $cells) {
            $row = [];

            foreach ($headers as $column => $key) {
                if ($key === '') {
                    continue;
                }

                $row[$key] = $cells[$column] ?? null;
            }

            $code = strtoupper(trim((string) ($row['iata_code'] ?? $row['iata'] ?? '')));

            if (!preg_match('/^[A-Z]{3}$/', $code)) {
                $skipped++;
                continue;
            }

            if (isset($seen[$code])) {
                $duplicates++;
                continue;
            }

            $seen[$code] = true;

            $type = strtolower(trim((string) ($row['type'] ?? '')));
            if ($type === 'closed') {
                $skipped++;
                continue;
            }

            $name = $this->fixAirportText(trim((string) ($row['name'] ?? '')));
            if ($name === '') {
                $name = $code;
            }

            $municipality = $this->fixAirportText(trim((string) ($row['municipality'] ?? '')));
            $country = strtoupper(trim((string) ($row['iso_country'] ?? $row['country'] ?? '')));

            $addressParts = array_values(array_filter([$municipality, $country]));
            $address = implode(', ', $addressParts);

            $lat = trim((string) ($row['latitude_deg'] ?? $row['latitude'] ?? ''));
            $lng = trim((string) ($row['longitude_deg'] ?? $row['longitude'] ?? ''));

            $batch[] = [
                'name' => mb_substr($name, 0, 191),
                'code' => $code,
                'address' => mb_substr($address ?: $municipality ?: $country, 0, 191),
                'country' => mb_substr($country, 0, 20),
                'map_lat' => mb_substr($lat, 0, 20),
                'map_lng' => mb_substr($lng, 0, 20),
                'status' => 'publish',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                $this->upsertBatch($batch);
                $imported += count($batch);
                $batch = [];
            }
        }

        if ($batch) {
            $this->upsertBatch($batch);
            $imported += count($batch);
        }

        $this->command?->info("TSA global airport import completed.");
        $this->command?->info("Imported or updated: {$imported}");
        $this->command?->info("Skipped: {$skipped}");
        $this->command?->info("Duplicate IATA rows skipped: {$duplicates}");
    }

    protected function fixAirportText(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        // Some bundled airport resources contain UTF-8 bytes displayed/stored as CP437 mojibake
        // such as "ΓÇô" instead of "–" or "├í" instead of "á".
        if (!preg_match('/[Γ├┬]/u', $value)) {
            return $value;
        }

        $fixed = @iconv('UTF-8', 'CP437//IGNORE', $value);

        if (is_string($fixed) && $fixed !== '' && mb_check_encoding($fixed, 'UTF-8')) {
            return $fixed;
        }

        return $value;
    }

    protected function upsertBatch(array $batch): void
    {
        DB::table('bc_airport')->upsert(
            $batch,
            ['code'],
            ['name', 'address', 'country', 'map_lat', 'map_lng', 'status', 'updated_at']
        );
    }
}
