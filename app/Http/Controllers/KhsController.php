<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class KhsController extends Controller
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

        $khs = DB::table('khs')->select("khs.*", "students.name", "students.nim")->leftJoin("students", "khs.student_id", "=", "students.id");

        if (Auth::user()->role_id == 2) {
            $khs = $khs->where('khs.student_id', $student_id);
        }
        if (Auth::user()->role_id == 3) {
            $khs = $khs->where('students.lecture_id', $lecture_id)->where('khs.verification_status', 'pending');
        }

        $search = $request->search;
        if (isset($search)) {
            $khs = $khs->where(function ($query) use ($search) {
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

        $khs = $khs->paginate($limit, ['page' => $page]);
        return response()->json([
            'success' => true,
            'data' => $khs->items(),
            'meta' => [
                'current_page' => $khs->currentPage(),
                'per_page' => $khs->perPage(),
                'last_page' => $khs->lastPage(),
                'total' => $khs->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $student_id = Auth::user()->ref_id;

        $khs = DB::table('khs')
            ->where('id', $id)
            ->where('student_id', $student_id)
            ->first();

        if (!$khs) return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);

        $khs->scan_khs = [
            "url" => env('APP_URL') . "/api/file/" . $khs->scan_khs,
            "path" => $khs->scan_khs,
        ];

        return response()->json($khs);
    }

    public function store(Request $request)
    {
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $validator = Validator::make($request->all(),  [
            'irs_id' => 'required|integer|exists:irs,id',
            'sks' => 'required|integer',
            'ip' => 'required|numeric',
            'scan_khs' => 'required|string',
        ]);

        $khs = DB::table("khs")->where('student_id', $student_id)->where('irs_id', $request->irs_id)->first();

        if ($khs) {
            return response()->json([
                'success' => false,
                'message' => 'Data KHS untuk IRS ini sudah ada'
            ], 422);
        }

        // if error
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $irs = DB::table('irs')
            ->where('id', $request->irs_id)
            ->first();

        $last_khs = DB::table('khs')->where('student_id', $student_id)->orderBy('id', 'desc')->first();
        $count_khs = DB::table('khs')->where('student_id', $student_id)->count();


        $field_uploads = ["scan_khs"];
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
            $new_path = 'khs' . '/' . $original_name . '.' . $ext;
            Storage::move($file, $new_path);
            $request->$field_upload = $new_path;
        }

        $khs = [
            "irs_id" => $request->irs_id,
            "sks" => $request->sks,
            "ip" => $request->ip,
            "scan_khs" => $request->scan_khs,
            "sks_kumulatif" => $last_khs ? $last_khs->sks_kumulatif + $request->sks : $request->sks,
            "ip_kumulatif" => $last_khs ? ($last_khs->ip_kumulatif * $count_khs + $request->ip) / ($count_khs + 1) : $request->ip,
            "semester" => $irs->semester,
            "student_id" => $student_id,
            "college_year_id" => $irs->college_year_id,
            "verification_status" => "pending",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];

        $khs_id = DB::table("khs")->insertGetId($khs);
        $khs["id"] = $khs_id;

        return response()->json([
            'success' => true,
            'data' => $khs,
            'message' => 'Data Berhasil Dibuat'
        ], 200);
    }
    public function update(Request $request)
    {
        $student_id = Auth::user()->ref_id;
        // validate incoming request
        $validator = Validator::make($request->all(),  [
            'irs_id' => 'required|integer|exists:irs,id',
            'sks' => 'required|integer',
            'ip' => 'required|numeric',
            'scan_khs' => 'required|string',
        ]);

        $khs = DB::table("khs")->where('student_id', $student_id)->where('irs_id', $request->irs_id)->whereNotIn('id', [$request->id])->first();

        if ($khs) {
            return response()->json([
                'success' => false,
                'message' => 'Data KHS untuk IRS ini sudah ada'
            ], 422);
        }

        // if error
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $irs = DB::table('irs')
            ->where('id', $request->irs_id)
            ->first();

        $last_khs = DB::table('khs')->where('student_id', $student_id)->orderBy('id', 'desc')->first();
        $count_khs = DB::table('khs')->where('student_id', $student_id)->count();


        $field_uploads = ["scan_khs"];
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
            $new_path = 'khs' . '/' . $original_name . '.' . $ext;
            Storage::move($file, $new_path);
            $request->$field_upload = $new_path;
        }

        $khs = [
            "irs_id" => $request->irs_id,
            "sks" => $request->sks,
            "ip" => $request->ip,
            "scan_khs" => $request->scan_khs,
            "sks_kumulatif" => $last_khs ? $last_khs->sks_kumulatif + $request->sks : $request->sks,
            "ip_kumulatif" => $last_khs ? ($last_khs->ip_kumulatif * $count_khs + $request->ip) / ($count_khs + 1) : $request->ip,
            "semester" => $irs->semester,
            "student_id" => $student_id,
            "college_year_id" => $irs->college_year_id,
            "verification_status" => "pending",
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];

        $khs_id = DB::table("khs")->where('id', $request->id)->update($khs);

        return response()->json([
            'success' => true,
            'data' => $khs,
            'message' => 'Data Berhasil Diperbarui'
        ], 200);
    }
}
