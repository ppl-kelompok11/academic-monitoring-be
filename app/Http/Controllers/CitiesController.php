<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CitiesController extends Controller
{
    //
    public function lookup()
    {
        $filter = ["province_id"];
        $filterValue = [];
        if (request()->query('province_id')) {
            $filterValue[] = request()->query('province_id');
        }

        $filterBuilder = "";
        if (count($filter) > 0) {
            foreach ($filter as $value) {
                if (!empty(request()->query($value))) {
                    $filterValue[] = request()->query($value);
                }
            }
        }
        $cities = DB::select("SELECT * FROM cities WHERE true" .  $filterBuilder, $filterValue);
        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }
}
