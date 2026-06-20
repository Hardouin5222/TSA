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

            $query->orderByRaw("
                CASE
                    WHEN code = ? THEN 0
                    WHEN code LIKE ? THEN 1
                    WHEN name LIKE ? THEN 2
                    WHEN address LIKE ? THEN 3
                    WHEN country LIKE ? THEN 4
                    ELSE 5
                END ASC
            ", [strtoupper($s), strtoupper($s) . '%', $like, $like, $like]);
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
