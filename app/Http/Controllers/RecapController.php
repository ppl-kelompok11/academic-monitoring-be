<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecapController extends Controller
{
    //
    public function recapPkl()
    {
        $filter = "";
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
            $filter = "WHERE lecture_id = $lecture_id";
        }
        $recap_skripsi = DB::select("SELECT 
                        start_education_year,
                        count(*) FILTER (WHERE pkl.verification_status = '02') AS graduate,
                        count(*) FILTER (WHERE pkl.verification_status != '02' OR pkl.verification_status is null) AS not_graduate
                        FROM students s
                        LEFT JOIN pkl
                        ON pkl.student_id = s.id
                        $filter 
                        GROUP BY start_education_year
                        ORDER BY s.start_education_year DESC
                        LIMIT 7
                        ");
        return response()->json(
            [
                'success' => true,
                'data' => $recap_skripsi
            ]
        );
    }
    public function recapSkripsi()
    {
        $filter = "";
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
            $filter = "WHERE lecture_id = $lecture_id";
        }
        $recap_skripsi = DB::select("SELECT 
                        start_education_year,
                        count(*) FILTER (WHERE skripsi.verification_status = '02') AS graduate,
                        count(*) FILTER (WHERE skripsi.verification_status != '02' OR skripsi.verification_status is null ) AS not_graduate
                        FROM students s
                        LEFT JOIN skripsi
                        ON skripsi.student_id = s.id 
                        $filter
                        GROUP BY start_education_year
                        ORDER BY s.start_education_year DESC
                        LIMIT 7
                        ");
        return response()->json(
            [
                'success' => true,
                'data' => $recap_skripsi
            ]
        );
    }
    public function recapStatus()
    {
        $filter = "";
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
            $filter = "WHERE lecture_id = $lecture_id";
        }
        $recap_skripsi = DB::select("SELECT 
                        start_education_year,
                        count(*) FILTER (WHERE s.status = '00') AS active,
                        count(*) FILTER (WHERE s.status = '01') AS holiday,
                        count(*) FILTER (WHERE s.status = '02') AS absent,
                        count(*) FILTER (WHERE s.status = '03') AS drop_out,
                        count(*) FILTER (WHERE s.status = '04') AS resign,
                        count(*) FILTER (WHERE s.status = '05') AS graduate,
                        count(*) FILTER (WHERE s.status = '06') AS die
                        FROM students s
                        LEFT JOIN skripsi
                        ON skripsi.student_id = s.id 
                        $filter
                        GROUP BY start_education_year
                        ORDER BY s.start_education_year DESC
                        LIMIT 7
                        ");
        return response()->json(
            [
                'success' => true,
                'data' => $recap_skripsi
            ]
        );
    }
}
