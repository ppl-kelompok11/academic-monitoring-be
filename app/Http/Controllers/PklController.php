<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PklController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('jwtmiddleware');
    }
    public function index()
    {
        $student_id = Auth::user()->ref_id;

        $pkl = DB::table('pkl')
            ->where('student_id', $student_id)
            ->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $pkl->items(),
            'meta' => [
                'current_page' => $pkl->currentPage(),
                'per_page' => $pkl->perPage(),
                'last_page' => $pkl->lastPage(),
                'total' => $pkl->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $student_id = Auth::user()->ref_id;

        $pkl = DB::table('pkl')
            ->where('id', $id)
            ->where('student_id', $student_id)
            ->first();

        if (!$pkl) return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);

        $pkl->scan_pkl = [
            "url" => env('APP_URL') . "/api/file/" . $pkl->scan_pkl,
            "path" => $pkl->scan_pkl,
        ];

        return response()->json($pkl);
    }

    public function store(Request $request)
    {
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $validator = Validator::make($request->all(),  [
            'pkl_status' => 'required|string|in:not_taken,ongoing,passed',
            'grade' => 'requiredif:pkl_status,passed|string',
            'scan_pkl' => 'requiredif:pkl_status,passed|string',
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


        $field_uploads = ["scan_pkl"];
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
                $new_path = 'pkl' . '/' . $original_name . '.' . $ext;
                Storage::move($file, $new_path);
                $request->$field_upload = $new_path;
            }
        }

        $pkl = [
            "pkl_status" => $request->pkl_status,
            "grade" => $request->grade,
            "scan_pkl" => $request->scan_pkl,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "pending",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];

        $pkl_id = DB::table("pkl")->insertGetId($pkl);
        $pkl["id"] = $pkl_id;

        return response()->json([
            'success' => true,
            'data' => $pkl,
            'message' => 'Data Berhasil Dibuat'
        ], 200);
    }
    public function update(Request $request)
    {
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $validator = Validator::make($request->all(),  [
            'pkl_status' => 'required|string|in:not_taken,ongoing,passed',
            'grade' => 'requiredif:pkl_status,passed|nullable|integer',
            'scan_pkl' => 'requiredif:pkl_status,passed|nullable|integer',
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


        $field_uploads = ["scan_pkl"];
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
                $new_path = 'pkl' . '/' . $original_name . '.' . $ext;
                Storage::move($file, $new_path);
                $request->$field_upload = $new_path;
            }
        }

        $pkl = [
            "pkl_status" => $request->pkl_status,
            "grade" => $request->grade,
            "scan_pkl" => $request->scan_pkl,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "pending",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];

        $pkl_id = DB::table("pkl")->insertGetId($pkl);
        $pkl["id"] = $pkl_id;

        return response()->json([
            'success' => true,
            'data' => $pkl,
            'message' => 'Data Berhasil Diperbarui'
        ], 200);
    }
}
