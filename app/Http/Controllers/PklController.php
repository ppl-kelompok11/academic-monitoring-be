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
    public function index(Request $request)
    {
        if (Auth::user()->role_id == 2) {
            $student_id = Auth::user()->ref_id;
        }
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
        }

        $pkl = DB::table('pkl')->select("pkl.*", "students.name", "students.nim")->leftJoin("students", "pkl.student_id", "=", "students.id");

        if (Auth::user()->role_id == 2) {
            $pkl = $pkl->where('pkl.student_id', $student_id);
        }
        if (Auth::user()->role_id == 3) {
            $pkl = $pkl->where('students.lecture_id', $lecture_id)->where('pkl.verification_status', '01');
        }

        $search = $request->search;
        if (isset($search)) {
            $pkl = $pkl->where(function ($query) use ($search) {
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

        $pkl = $pkl->paginate($limit, ['page' => $page]);

        $field_uploads = ["scan_pkl"];
        // add url to scan_irs
        foreach ($pkl->items() as $item) {
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
        if (Auth::user()->role_id == 2) {
            $student_id = Auth::user()->ref_id;
        }
        if (Auth::user()->role_id == 3) {
            $lecture_id = Auth::user()->ref_id;
        }

        $pkl = DB::table('pkl')
            ->select("pkl.*", "students.name", "students.nim")
            ->leftJoin("students", "pkl.student_id", "=", "students.id")
            ->where('pkl.id', $id);

        if (Auth::user()->role_id == 2) {
            $pkl = $pkl->where('pkl.student_id', $student_id);
        }
        if (Auth::user()->role_id == 3) {
            $pkl = $pkl->where('students.lecture_id', $lecture_id);
        }

        $pkl = $pkl->first();

        $pkl->scan_pkl = [
            "filename" => substr($pkl->scan_pkl, strrpos($pkl->scan_pkl, '/') + 1),
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
            'grade' => 'required|string',
            'scan_pkl' => 'required|string',
            'semester_value' => 'required|integer',
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
            "semester_value" => $request->semester,
            "grade" => $request->grade,
            "scan_pkl" => $request->scan_pkl,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "01",
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
            'id' => 'required|integer|exists:pkl,id',
            'grade' => 'required|string',
            'scan_pkl' => 'required|string',
            'semester_value' => 'required|integer',
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
            "semester_value" => $request->semester_value,
            "grade" => $request->grade,
            "scan_pkl" => $request->scan_pkl,
            "student_id" => $student_id,
            "college_year_id" => $active_college_year->id,
            "verification_status" => "01",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];

        $pkl_id = DB::table("pkl")->where('id', $request->id)->update($pkl);

        return response()->json([
            'success' => true,
            'data' => $pkl,
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

        $pkl = [
            "verification_status" => $request->verification_status,
            "updated_by" => Auth::user()->id,
        ];

        DB::table("pkl")->where('id', $request->id)->update($pkl);

        return response()->json([
            'success' => true,
            'data' => $pkl,
            'message' => 'Status Berhasil Diperbarui'
        ], 200);
    }
    public function delete(Request $request)
    {
        // get ref id
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $this->validate($request, [
            'id' => 'required|integer|exists:pkl,id',
        ]);

        $pkl = DB::table("pkl")->where('id', $request->id)->where('student_id', $student_id)->first();

        if (!$pkl) return response()->json([
            'success' => false,
            'message' => 'Data not found'
        ], 422);

        DB::table("pkl")->where('id', $request->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data Successfully Deleted'
        ], 200);
    }
}
