<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        if (Auth::user()->role_id == 2) {
            $id = Auth::user()->ref_id;
        } else {
            $id = $request->id;
        }
        $this->validate($request, [
            'nip' => 'required|string|unique:students,nim,' . $id,
            'nidn' => 'required|string|unique:students,nim,' . $id,
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'work_start_date' => 'nullable|integer',
            'photo' => 'nullable|string',
        ]);
        $data = [
            "nip" => $request->nip,
            "nidn" => $request->nidn,
            "province_id" => $request->province_id,
            "city_id" => $request->city_id,
            "work_start_date" => $request->work_start_date,
            "photo" => $request->photo,
            "updated_by" => Auth::user()->id,
        ];
        DB::table("lecture")->where('id', $id)->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Lecture successfully updated'
        ], 201);
    }
}
