<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CitiesController extends Controller
{
    //
    public function lookup(Request $request)
    {
        $cities = DB::table('cities');
        if (isset($request->province_id)) {
            $cities = $cities->where('province_id', $request->province_id);
        }

        $cities = $cities->get();


        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }
}
