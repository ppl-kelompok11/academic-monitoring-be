<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProvinceController extends Controller
{
    //
    public function lookup()
    {
        $provinces = DB::table('provinces')->get();
        return response()->json(
            [
                'success' => true,
                'data' => $provinces
            ]
        );
    }
}
