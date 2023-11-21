<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecapController extends Controller
{
    //
    public function studentDashboard()
    {
      $student_id = auth()->user()->ref_id;
        $dashboard = [];
        $dashboard['skripsi_status'] = DB::table('skripsi')->where('student_id', $student_id)->where('verification_status', '02')->count() > 0 ? 'graduate' : 'not_graduate';
        $dashboard['pkl_status'] = DB::table('pkl')->where('student_id', $student_id)->where('verification_status', '02')->count() > 0 ? 'graduate' : 'not_graduate';
        $last_khs = DB::table('khs')->where('student_id', $student_id)->orderBy('id', 'desc')->first();
        $dashboard['sks_kumulatif'] = $last_khs->sks_kumulatif;
        $dashboard['ip_kumulatif'] = $last_khs->ip_kumulatif;
        $dashboard['ip_semester'] = $last_khs->ip_semester;
        $dashboard['sks_semester'] = $last_khs->sks_semester;
        $student = DB::table('students')->where('id', $student_id)->first();
        $dashboard['lecture_name'] = $student->lecture_name;
        $dashboard['status'] = $student->status_name;
        return response()->json(
            [
                'success' => true,
                'data' => $dashboard
            ]
        );  
    }
    // public function recapSkripsi()
    // {
    //     $recap_skripsi = DB::select("SELECT 
    //                     start_education_year,
    //                     count(*) FILTER (WHERE skripsi.verification_status = '02') AS graduate,
    //                     count(*) FILTER (WHERE skripsi.verification_status != '02' OR skripsi.verification_status is null ) AS not_graduate
    //                     FROM students s
    //                     LEFT JOIN skripsi
    //                     ON skripsi.student_id = s.id 
    //                     GROUP BY start_education_year
    //                     ORDER BY s.start_education_year DESC
    //                     LIMIT 7
    //                     ");
    //     return response()->json(
    //         [
    //             'success' => true,
    //             'data' => $recap_skripsi
    //         ]
    //     );
    // }
    // public function recapStatus()
    // {
    //     $recap_skripsi = DB::select("SELECT 
    //                     start_education_year,
    //                     count(*) FILTER (WHERE s.status = '00') AS active,
    //                     count(*) FILTER (WHERE s.status = '01') AS holiday,
    //                     count(*) FILTER (WHERE s.status = '02') AS absent,
    //                     count(*) FILTER (WHERE s.status = '03') AS drop_out,
    //                     count(*) FILTER (WHERE s.status = '04') AS resign,
    //                     count(*) FILTER (WHERE s.status = '05') AS graduate,
    //                     count(*) FILTER (WHERE s.status = '06') AS die
    //                     FROM students s
    //                     LEFT JOIN skripsi
    //                     ON skripsi.student_id = s.id 
    //                     GROUP BY start_education_year
    //                     ORDER BY s.start_education_year DESC
    //                     LIMIT 7
    //                     ");
    //     return response()->json(
    //         [
    //             'success' => true,
    //             'data' => $recap_skripsi
    //         ]
    //     );
    // }
}
