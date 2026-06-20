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

        if ($pre_selected && $selected) {
            $items = $this->airport::find($selected);

            return [
                'results' => $items
            ];
        }

        $s = trim((string) (
            $request->query('search')
            ?? $request->query('q')
            ?? $request->query('term')
            ?? ''
        ));

        $query = $this->airport::select('id', 'name', 'code', 'address', 'country');

        if ($s !== '') {
            $code = strtoupper($s);
            $like = '%' . $s . '%';

            $query->where(function ($query) use ($s, $code, $like) {
                $query->where('code', $code)
                    ->orWhere('code', $s)
                    ->orWhere('name', 'LIKE', $like)
                    ->orWhere('address', 'LIKE', $like)
                    ->orWhere('country', $s)
                    ->orWhere('country', $code);
            });

            $query->orderByRaw("
                CASE
                    WHEN code = ? THEN 1
                    WHEN code = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    WHEN address LIKE ? THEN 3
                    WHEN country = ? THEN 4
                    WHEN country = ? THEN 4
                    ELSE 5
                END ASC
            ", [$code, $s, $like, $like, $s, $code]);
        }

        $res = $query->orderBy('id', 'desc')->limit(20)->get();

        return [
            'status' => 1,
            'data' => AirportResource::collection($res)
        ];
    }

    }
