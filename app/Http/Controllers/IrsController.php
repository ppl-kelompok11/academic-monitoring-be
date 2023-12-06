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
    public function index(Request $request)
    {
        if (Auth::user()->role_id == 2) {
            $student_id = Auth::user()->ref_id;
        }
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
        }

        $irs = DB::table('irs')->select("irs.*", "students.name", "students.nim")->leftJoin("students", "irs.student_id", "=", "students.id");

        if (Auth::user()->role_id == 2) {
            $irs = $irs->where('irs.student_id', $student_id);
        }
        if (Auth::user()->role_id == 3) {
            $irs = $irs->where('students.lecture_id', $lecture_id)->where('irs.verification_status', '01');
        }

        $search = $request->search;
        if (isset($search)) {
            $irs = $irs->where(function ($query) use ($search) {
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

        $irs = $irs->paginate($limit, ['page' => $page]);

        $field_uploads = ["scan_irs"];
        // add url to scan_irs
        foreach ($irs->items() as $item) {
            foreach ($field_uploads as $field_upload) {
                if ($item->$field_upload) {
                    $item->$field_upload = [
                        // get file name from last / in path
                        "filename" => substr($item->$field_upload, strrpos($item->$field_upload, '/') + 1),
                        "url" => env('APP_URL') . "/api/file/" . $item->$field_upload,
                        "path" => $item->$field_upload,
                    ];
                }
            }
        }

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
        if (Auth::user()->role_id == 2) {
            $student_id = Auth::user()->ref_id;
        }
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
        }

        $irs = DB::table('irs')
            ->select("irs.*", "students.name", "students.nim")
            ->leftJoin("students", "irs.student_id", "=", "students.id")
            ->where('irs.id', $id);

        if (Auth::user()->role_id == 2) {
            $irs = $irs->where('irs.student_id', $student_id);
        }
        if (Auth::user()->role_id == 3) {
            $irs = $irs->where('students.lecture_id', $lecture_id);
        }

        $irs = $irs->first();


        if (!$irs) return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);

        $irs->scan_irs = [
            "filename" => substr($irs->scan_irs, strrpos($irs->scan_irs, '/') + 1),
            "url" => env('APP_URL') . "/api/file/" . $irs->scan_irs,
            "path" => $irs->scan_irs,
        ];

        return response()->json($irs);
    }

    public function store(Request $request)
    {
        // validate incoming request
        $this->validate($request, [
            'semester_value' => 'required|integer',
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
            "semester_value" => $request->semester_value,
            "sks" => $request->sks,
            "scan_irs" => $request->scan_irs,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "01",
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
            'semester_value' => 'required|integer|exists:semester,value',
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
            "semester_value" => $request->semester_value,
            "sks" => $request->sks,
            "verification_status" => "01",
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
    public function validation(Request $request)
    {
        // validate incoming request
        $this->validate($request, [
            'id' => 'required|integer',
            'verification_status' => 'required|string|in:00,01,02',
        ]);

        $irs = [
            "verification_status" => $request->verification_status,
            "updated_by" => Auth::user()->id,
        ];

        DB::table("irs")->where('id', $request->id)->update($irs);

        return response()->json([
            'success' => true,
            'data' => $irs,
            'message' => 'Status Berhasil Diperbarui'
        ], 200);
    }
    public function delete(Request $request)
    {
        // get ref id
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $this->validate($request, [
            'id' => 'required|integer|exists:irs,id',
        ]);

        $irs = DB::table("irs")->where('id', $request->id)->where('student_id', $student_id)->first();

        if (!$irs) return response()->json([
            'success' => false,
            'message' => 'Data not found'
        ], 422);

        DB::table("khs")->where('irs_id', $request->id)->delete();
        DB::table("irs")->where('id', $request->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data Successfully Deleted'
        ], 200);
    }
}
