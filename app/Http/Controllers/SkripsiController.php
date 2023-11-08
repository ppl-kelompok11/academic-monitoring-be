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
    public function index()
    {
        $student_id = Auth::user()->ref_id;

        $skripsi = DB::table('skripsi')
            ->where('student_id', $student_id)
            ->paginate(10);
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
            'skripsi_status' => 'required|string|in:not_taken,ongoing,passed',
            'grade' => 'requiredif:skripsi_status,passed|string',
            'scan_skripsi' => 'requiredif:skripsi_status,passed|string',
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
            "skripsi_status" => $request->skripsi_status,
            "grade" => $request->grade,
            "scan_skripsi" => $request->scan_skripsi,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "pending",
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
            'skripsi_status' => 'required|string|in:not_taken,ongoing,passed',
            'grade' => 'requiredif:skripsi_status,passed|nullable|integer',
            'scan_skripsi' => 'requiredif:skripsi_status,passed|nullable|integer',
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
            "skripsi_status" => $request->skripsi_status,
            "grade" => $request->grade,
            "scan_skripsi" => $request->scan_skripsi,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "pending",
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
}
