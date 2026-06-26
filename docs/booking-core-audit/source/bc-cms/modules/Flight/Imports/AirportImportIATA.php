<?php


namespace Modules\Flight\Imports;


use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Modules\Flight\Models\Airport;

class AirportImportIATA implements WithChunkReading,ToModel,ShouldQueue,WithHeadingRow,WithBatchInserts,WithUpserts,WithUpsertColumns
{
    use Importable;

    public function model(array $row)
    {
        $data = [
            'name' => $this->fixAirportText((string) $row['name']),
            'code' => $row['iata_code'],
            'map_lat' => $row['latitude_deg'],
            'map_lng' => $row['longitude_deg'],
            'address' => $this->fixAirportText((string) $row['municipality']),
            'country'=>$row['iso_country'],
            'status'=>'publish'
        ];
        $a =  new Airport();
        $a->fillByAttr(array_keys($data),$data);
        return $a;
    }
    /**
     * @return array
     */
    public function upsertColumns()
    {
        return ['name', 'map_lat','map_lng','address','country'];
    }

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return 'code';
    }

    protected function fixAirportText(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        if (!preg_match('/[Γ├┬]/u', $value)) {
            return $value;
        }

        $fixed = @iconv('UTF-8', 'CP437//IGNORE', $value);

        if (is_string($fixed) && $fixed !== '' && mb_check_encoding($fixed, 'UTF-8')) {
            return $fixed;
        }

        return $value;
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
