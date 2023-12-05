<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class LectureController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('jwtmiddleware');
    }
    public function index()
    {
        $student = DB::table('lecture')
            ->select('lecture.*', 'users.email')
            ->join('users', 'lecture.id', '=', 'users.ref_id')
            ->where('role_id', 3)
            ->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $student->items(),
            'meta' => [
                'current_page' => $student->currentPage(),
                'per_page' => $student->perPage(),
                'last_page' => $student->lastPage(),
                'total' => $student->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $student = DB::table('lecture')
            ->select('lecture.*', 'users.email')
            ->join('users', 'lecture.id', '=', 'users.ref_id')
            ->where('role_id', 3)
            ->where('lecture.id', $id)
            ->first();

        $field_uploads = ["photo"];
        // add url to field_uploads
        foreach ($field_uploads as $field_upload) {
            if ($student->$field_upload) {
                $student->$field_upload = [
                    "filename" => substr($student->photo, strrpos($student->photo, '/') + 1),
                    "url" => env('APP_URL') . "/api/file/" . $student->$field_upload,
                    "path" => $student->$field_upload,
                ];
            }
        }
        return response()->json($student);
    }

    public function store(Request $request)
    {
        try {
            // validate incoming request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'nip' => 'required|string',
                'nidn' => 'required|string',
                'province_id' => 'nullable|integer',
                'city_id' => 'nullable|integer',
                'work_start_date' => 'nullable|date',
                'photo' => 'nullable|string',
                'password' => 'required|string',
            ]);

            // if error
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $lecture = [
                "name" => $request->name,
                "nip" => $request->nip,
                "nidn" => $request->nidn,
                "province_id" => $request->province_id,
                "city_id" => $request->city_id,
                "work_start_date" => $request->work_start_date,
                "photo" => $request->photo,
                "created_by" => Auth::user()->id,
            ];
            $lecture_id = DB::table("lecture")->insertGetId($lecture);
            $user = [
                "email" => $request->email,
                "username" => $request->nip,
                "password" => bcrypt($request->password),
                "ref_id" => $lecture_id,
                "role_id" => 3,
            ];
            DB::table("users")->insert($user);


            return response()->json([
                'success' => true,
                'message' => 'Lecture successfully registered'
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Lecture failed to register',
                'error' => $exception->getMessage()
            ], 500);
        }
    }
    public function update(Request $request)
    {
        if (Auth::user()->role_id == 3) {
            $id = Auth::user()->ref_id;
        } else {
            $id = $request->id;
        }
        $user = DB::table('users')->where('ref_id', $id)->where('role_id', 3)->first();
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|unique:lecture,nip,' . $id,
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email,' . $user->id,
            'nidn' => 'required|string|unique:lecture,nidn,' . $id,
            'province_id' => 'nullable|integer|exists:provinces,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'work_start_date' => 'nullable|date',
            'photo' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $field_uploads = ["photo"];
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
                $new_path = 'lecture' . '/' . $original_name . '.' . $ext;
                Storage::move($file, $new_path);
                $request->$field_upload = $new_path;
            }
        }

        $data = [
            "nip" => $request->nip,
            "nidn" => $request->nidn,
            "province_id" => $request->province_id,
            "city_id" => $request->city_id,
            "work_start_date" => $request->work_start_date,
            "address" => $request->address,
            "photo" => $request->photo,
            "phone" => $request->phone,
            "updated_by" => Auth::user()->id,
        ];
        DB::table("lecture")->where('id', $id)->update($data);
        DB::table("users")->where('ref_id', $id)->where('role_id', 3)->update([
            "email" => $request->email,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Lecture successfully updated'
        ], 201);
    }
}
