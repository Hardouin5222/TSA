<?php


    namespace Modules\Flight\Controllers;


    use Auth;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Validation\Rule;
    use Maatwebsite\Excel\Facades\Excel;
    use Modules\AdminController;
    use Modules\Flight\Imports\AirportImportIATA;
    use Modules\Flight\Models\Airport;
    use Modules\Flight\Models\Flight;
    use Modules\Flight\Models\SeatType;
    use Modules\Flight\Resources\AirportResource;
    use Modules\Location\Models\Location;

    class AirportController extends AdminController
    {
        /**
         * @var string
         */
        private $airport;
        /**
         * @var string
         */
        private $location;

        /**
         * @var string
         */

        public function __construct()
        {
            $this->location = Location::class;
            $this->airport = Airport::class;
        }

        public function search(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        $formatAirport = function ($airport) {
            $code = strtoupper((string) $airport->code);
            $address = $airport->address ?: $airport->country;

            return [
                'id' => $code ?: (string) $airport->id,
                'title' => trim(($code ? $code . ' - ' : '') . $airport->name),
                'text' => trim(($code ? $code . ' - ' : '') . $airport->name),
                'name' => $airport->name,
                'code' => $code,
                'address' => $address,
                'country' => $airport->country,
                'desc' => trim(($code ? $code . ' - ' : '') . $address),
            ];
        };

        if ($pre_selected && $selected) {
            $airport = $this->airport::where('code', strtoupper($selected))
                ->orWhere('id', $selected)
                ->first();

            $items = $airport ? [$formatAirport($airport)] : [];

            return response()->json([
                'status' => 1,
                'data' => $items,
                'results' => $items,
            ]);
        }

        $s = trim((string) (
            $request->query('search')
            ?? $request->query('q')
            ?? $request->query('term')
            ?? $request->query('s')
            ?? ''
        ));

        $query = $this->airport::select('id', 'name', 'code', 'address', 'country', 'status');

        if ($s !== '') {
            $like = '%' . $s . '%';

            $query->where(function ($query) use ($s, $like) {
                $query->where('code', strtoupper($s))
                    ->orWhere('code', 'LIKE', strtoupper($s) . '%')
                    ->orWhere('name', 'LIKE', $like)
                    ->orWhere('address', 'LIKE', $like)
                    ->orWhere('country', 'LIKE', $like);
            });

            $majorCodes = [
                'IST','SAW','ADB','ESB','AYT','LHR','JFK','CDG','ORY','DXB','FRA','AMS','DOH','MAD','BCN',
                'FCO','MXP','MUC','ZRH','VIE','BRU','CPH','ARN','OSL','HEL','ATH','SKG','BEG','SOF','OTP',
                'WAW','PRG','BUD','SVO','DME','LED','CAI','BEY','TLV','JED','RUH','AUH','SHJ','BKK','SIN',
                'HKG','NRT','HND','ICN','PEK','PVG','SYD','MEL','YYZ','YUL','LAX','SFO','ORD','MIA','ATL',
                'BOS','IAD','EWR'
            ];

            $majorCodesSql = implode(',', array_map(function ($code) {
                return DB::getPdo()->quote($code);
            }, $majorCodes));

            // TSA major airport ranking: exact IATA > city/address exact > name starts > contains.
            $query->orderByRaw("
                CASE
                    WHEN code = ? THEN 0
                    WHEN code LIKE ? THEN 1
                    WHEN address = ? OR address LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    WHEN address LIKE ? THEN 4
                    WHEN name LIKE ? THEN 5
                    WHEN country LIKE ? THEN 6
                    ELSE 7
                END ASC
            ", [
                strtoupper($s),
                strtoupper($s) . '%',
                $s,
                $s . ',%',
                $s . '%',
                $like,
                $like,
                $like
            ]);

            $query->orderByRaw("
                CASE
                    WHEN name LIKE '%International Airport%' THEN 0
                    WHEN name LIKE '%Airport%' THEN 1
                    WHEN name LIKE '%Heliport%' THEN 7
                    WHEN name LIKE '%Air Base%' THEN 8
                    WHEN name LIKE '%SPB%' THEN 9
                    ELSE 3
                END ASC
            ");

            $query->orderByRaw("CASE WHEN FIELD(code, {$majorCodesSql}) = 0 THEN 9999 ELSE FIELD(code, {$majorCodesSql}) END ASC");
        }

        $items = $query
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'publish');
            })
            ->orderBy('code')
            ->limit(20)
            ->get()
            ->map($formatAirport)
            ->values()
            ->all();

        return response()->json([
            'status' => 1,
            'data' => $items,
            'results' => $items,
        ]);
    }


}
