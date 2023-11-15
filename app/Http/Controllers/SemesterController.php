<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SemesterController extends Controller
{
    //
    public function lookup()
    {
        $semester = DB::table('semester')->get();
        return response()->json(
            [
                'success' => true,
                'data' => $semester
            ]
        );
    }
}
