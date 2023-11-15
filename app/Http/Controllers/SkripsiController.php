<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SkripsiController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('jwtmiddleware');
    }
    public function index(Request $request)
    {
        if (Auth::user()->role_id == 2) {
            $student_id = Auth::user()->ref_id;
        }
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
        }

        $skripsi = DB::table('skripsi')->select("skripsi.*", "students.name", "students.nim")->leftJoin("students", "skripsi.student_id", "=", "students.id");

        if (Auth::user()->role_id == 2) {
            $skripsi = $skripsi->where('skripsi.student_id', $student_id);
        }
        if (Auth::user()->role_id == 3) {
            $skripsi = $skripsi->where('students.lecture_id', $lecture_id)->where('skripsi.verification_status', '01');
        }

        $search = $request->search;
        if (isset($search)) {
            $skripsi = $skripsi->where(function ($query) use ($search) {
                $query->whereRaw("UPPER(students.name) LIKE '" . strtoupper($search) . "%'")
                    ->orwhereRaw("UPPER(students.nim) LIKE '" . strtoupper($search) . "%'");
            });
        }

        $page = 1;
        if (isset($request->page)) {
            $page = $request->page;
        }


        $limit = 10;
        if (isset($request->limit)) {
            $limit = $request->limit;
        }

        $skripsi = $skripsi->paginate($limit, ['page' => $page]);

        $field_uploads = ["scan_skripsi"];
        // add url to scan_irs
        foreach ($skripsi->items() as $item) {
            foreach ($field_uploads as $field_upload) {
                if ($item->$field_upload) {
                    $item->$field_upload = [
                        "url" => env('APP_URL') . "/api/file/" . $item->$field_upload,
                        "path" => $item->$field_upload,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $skripsi->items(),
            'meta' => [
                'current_page' => $skripsi->currentPage(),
                'per_page' => $skripsi->perPage(),
                'last_page' => $skripsi->lastPage(),
                'total' => $skripsi->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $student_id = Auth::user()->ref_id;

        $skripsi = DB::table('skripsi')
            ->where('id', $id)
            ->where('student_id', $student_id)
            ->first();

        if (!$skripsi) return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);

        $skripsi->scan_skripsi = [
            "filename" => substr($skripsi->scan_skripsi, strrpos($skripsi->scan_skripsi, '/') + 1),
            "url" => env('APP_URL') . "/api/file/" . $skripsi->scan_skripsi,
            "path" => $skripsi->scan_skripsi,
        ];

        return response()->json($skripsi);
    }

    public function store(Request $request)
    {
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $validator = Validator::make($request->all(),  [
            'grade' => 'requiredif:skripsi_status,passed|string',
            'scan_skripsi' => 'requiredif:skripsi_status,passed|string',
            "semester_value" => 'required|integer',
        ]);

        // if error
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $active_college_year = DB::table('college_years')
            ->where('active', true)
            ->first();


        $field_uploads = ["scan_skripsi"];
        // check file scan irs is exist
        foreach ($field_uploads as $field_upload) {
            $file = $request->$field_upload;
            if ($file) {
                if (!Storage::exists($file)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File ' . $field_upload . ' not found'
                    ], 422);
                }

                // move file from tmp to storage
                $original_name = pathinfo(storage_path($file), PATHINFO_FILENAME);
                $ext = pathinfo(storage_path($file), PATHINFO_EXTENSION);
                $new_path = 'skripsi' . '/' . $original_name . '.' . $ext;
                Storage::move($file, $new_path);
                $request->$field_upload = $new_path;
            }
        }

        $skripsi = [
            "semester_value" => $request->semester_value,
            "grade" => $request->grade,
            "scan_skripsi" => $request->scan_skripsi,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "01",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];

        $skripsi_id = DB::table("skripsi")->insertGetId($skripsi);
        $skripsi["id"] = $skripsi_id;

        return response()->json([
            'success' => true,
            'data' => $skripsi,
            'message' => 'Data Berhasil Dibuat'
        ], 200);
    }
    public function update(Request $request)
    {
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $validator = Validator::make($request->all(),  [
            'grade' => 'requiredif:skripsi_status,passed|nullable|integer',
            'scan_skripsi' => 'requiredif:skripsi_status,passed|nullable|integer',
            "semester_value" => 'required|integer',
        ]);

        // if error
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $active_college_year = DB::table('college_years')
            ->where('active', true)
            ->first();


        $field_uploads = ["scan_skripsi"];
        // check file scan irs is exist
        foreach ($field_uploads as $field_upload) {
            $file = $request->$field_upload;
            if ($file) {
                if (!Storage::exists($file)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File ' . $field_upload . ' not found'
                    ], 422);
                }

                // move file from tmp to storage
                $original_name = pathinfo(storage_path($file), PATHINFO_FILENAME);
                $ext = pathinfo(storage_path($file), PATHINFO_EXTENSION);
                $new_path = 'skripsi' . '/' . $original_name . '.' . $ext;
                Storage::move($file, $new_path);
                $request->$field_upload = $new_path;
            }
        }

        $skripsi = [
            "semester_value" => $request->semester_value,
            "grade" => $request->grade,
            "scan_skripsi" => $request->scan_skripsi,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "01",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];

        $skripsi_id = DB::table("skripsi")->insertGetId($skripsi);
        $skripsi["id"] = $skripsi_id;

        return response()->json([
            'success' => true,
            'data' => $skripsi,
            'message' => 'Data Berhasil Diperbarui'
        ], 200);
    }
    public function validation(Request $request)
    {
        // validate incoming request
        $this->validate($request, [
            'id' => 'required|integer',
            'verification_status' => 'required|string|in:00,01,02',
        ]);

        $skripsi = [
            "verification_status" => $request->verification_status,
            "updated_by" => Auth::user()->id,
        ];

        DB::table("skripsi")->where('id', $request->id)->update($skripsi);

        return response()->json([
            'success' => true,
            'data' => $skripsi,
            'message' => 'Status Berhasil Diperbarui'
        ], 200);
    }
}
