<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IrsController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('jwtmiddleware');
    }
    public function index()
    {
        $student_id = Auth::user()->ref_id;

        $irs = DB::table('irs')
            ->where('student_id', $student_id)
            ->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $irs->items(),
            'meta' => [
                'current_page' => $irs->currentPage(),
                'per_page' => $irs->perPage(),
                'last_page' => $irs->lastPage(),
                'total' => $irs->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $student_id = Auth::user()->ref_id;

        $irs = DB::table('irs')
            ->where('id', $id)
            ->where('student_id', $student_id)
            ->first();

        if (!$irs) return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);

        $irs->scan_irs = [
            "url" => env('APP_URL') . "/api/file/" . $irs->scan_irs,
            "path" => $irs->scan_irs,
        ];

        return response()->json($irs);
    }

    public function store(Request $request)
    {
        // validate incoming request
        $this->validate($request, [
            'semester' => 'required|integer',
            'sks' => 'required|integer',
            'scan_irs' => 'required|string',
        ]);

        $field_uploads = ["scan_irs"];
        // check file scan irs is exist
        foreach ($field_uploads as $field_upload) {
            $file = $request->$field_upload;
            if (!Storage::exists($file)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File ' . $field_upload . ' not found'
                ], 422);
            }

            // move file from tmp to storage
            $original_name = pathinfo(storage_path($file), PATHINFO_FILENAME);
            $ext = pathinfo(storage_path($file), PATHINFO_EXTENSION);
            $new_path = 'irs' . '/' . $original_name . '.' . $ext;
            Storage::move($file, $new_path);
            $request->$field_upload = $new_path;
        }

        $student_id = Auth::user()->ref_id;
        $active_college_year = DB::table('college_years')
            ->where('active', true)
            ->first();

        $irs = [
            "semester" => $request->semester,
            "sks" => $request->sks,
            "scan_irs" => $request->scan_irs,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "pending",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];
        $irs_id = DB::table("irs")->insertGetId($irs);

        return response()->json([
            'success' => true,
            'data' => $irs,
            'message' => 'Data Berhasil Dibuat'
        ], 200);
    }
    public function update(Request $request)
    {
        // validate incoming request
        $this->validate($request, [
            'semester' => 'required|integer|max:14',
            'sks' => 'required|integer',
            'scan_irs' => 'required|string',
        ]);


        $field_uploads = ["scan_irs"];
        // check file scan irs is exist
        foreach ($field_uploads as $field_upload) {
            $file = $request->$field_upload;
            if (!Storage::exists($file)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File ' . $field_upload . ' not found'
                ], 422);
            }

            // move file from tmp to storage
            $original_name = pathinfo(storage_path($file), PATHINFO_FILENAME);
            $ext = pathinfo(storage_path($file), PATHINFO_EXTENSION);
            $new_path = 'irs' . '/' . $original_name . '.' . $ext;
            Storage::move($file, $new_path);
            $request->$field_upload = $new_path;
        }

        $student_id = Auth::user()->ref_id;
        $active_college_year = DB::table('college_years')
            ->where('active', true)
            ->first();

        $irs = [
            "semester" => $request->semester,
            "sks" => $request->sks,
            "verification_status" => "pending",
            "scan_irs" => $request->scan_irs,
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];
        $irs_id = DB::table("irs")->where('student_id', $student_id)->where('id', $request->id)->update($irs);

        return response()->json([
            'success' => true,
            'data' => $irs,
            'message' => 'Data Berhasil Diperbarui'
        ], 200);
    }
}
