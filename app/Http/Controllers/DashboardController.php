<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    //
    public function profileOverview()
    {
        $student_id = auth()->user()->ref_id;
        $dashboard = [];
        $dashboard['skripsi_status'] = DB::table('skripsi')->where('student_id', $student_id)->where('verification_status', '02')->count() > 0 ? 'graduate' : 'not_graduate';
        $dashboard['pkl_status'] = DB::table('pkl')->where('student_id', $student_id)->where('verification_status', '02')->count() > 0 ? 'graduate' : 'not_graduate';
        $last_khs = DB::table('khs')->where('student_id', $student_id)->orderBy('id', 'desc')->first();
        $dashboard['sks_kumulatif'] = $last_khs->sks_kumulatif;
        $dashboard['ip_kumulatif'] = $last_khs->ip_kumulatif;
        $dashboard['ip'] = $last_khs->ip;
        $dashboard['sks'] = $last_khs->sks;
        $student = DB::table('students')->join('lecture', 'lecture.id', '=', 'students.lecture_id')
            ->select('students.*', 'lecture.name as lecture_name')
            ->where('students.id', $student_id)->first();

        $dashboard['lecture_name'] = $student->lecture_name;
        $dashboard['student_status'] = $student->status;
        return response()->json(
            [
                'success' => true,
                'data' => $dashboard
            ]
        );
    }
    public function studentOverview(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_education_year' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            $dashboard = DB::selectOne("SELECT 
                            count(*) as total_student,
                            count(case when sk.verification_status = '02' then 1 end) as total_graduate,
                            round(avg(case when sk.verification_status = '02' then k.semester_value end),2) as average_semester_graduate,
                            count(case when sk.verification_status = '02' AND k.ip >= 3.51 then 1 end) as total_graduate_cumlaude,
                            round(avg(case when sk.verification_status = '02' then k.ip end),2) as average_ip_graduate,
                            round(avg(case when sk.verification_status = '02' then k.sks end),0) as average_sks_graduate,
                            round(avg(case when sk.verification_status = '02' then k.ip_kumulatif end),2) as average_ip_kumulatif_graduate,
                            round(avg(case when sk.verification_status = '02' then k.sks_kumulatif end),0) as average_sks_kumulatif_graduate
                            FROM students s
                            INNER JOIN khs k
                            ON k.student_id = s.id
                            LEFT JOIN skripsi sk
                            ON sk.student_id = s.id AND sk.verification_status = '02' AND k.semester_value = sk.semester_value
                            WHERE s.start_education_year = " . $request->start_education_year . "
                            ");
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            );
        }
        return response()->json(
            [
                'success' => true,
                'data' => $dashboard
            ]
        );
    }

    public function studentIrs(Request $request)
    {
        try {
            // validate incoming request
            $validator = Validator::make($request->all(), [
                'start_education_year' => 'required|integer',
            ]);
            // if error
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            $list_irs = DB::select("SELECT 
                            k.semester_value,
                            round(avg(case when k.ip is not null then ip else 0 end),2) as average_ip
                            FROM semester se
                            LEFT JOIN khs k
                            ON k.semester_value = se.value
                            LEFT JOIN students s
                            ON s.id = k.student_id
                            WHERE s.start_education_year = " . $request->start_education_year . "
                            GROUP BY k.semester_value
                            ORDER BY k.semester_value ASC

                            ");
            return response()->json(
                [
                    'success' => true,
                    'data' => $list_irs
                ]
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            );
        }
    }
}
