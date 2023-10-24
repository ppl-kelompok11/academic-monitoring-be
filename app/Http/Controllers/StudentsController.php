<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentsController extends Controller
{
    //
    public function __construct()
    {
        // $this->middleware('auth:api');
    }
    public function index()
    {
        $student = DB::table('students')->get();
        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    public function show($id)
    {
        $student = DB::table('students')->where('id', $id)->first();
        return response()->json($student);
    }

    public function store(Request $request)
    {
        // validate incoming request
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'nim' => 'required|string|unique:students,nim',
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'start_education_year' => 'required|integer',
            'status' => 'required|string',
            'photo' => 'nullable|string',
            'password' => 'required|string',
        ]);
        $user = [
            "name" => $request->name,
            "email" => $request->email,
            "password" => bcrypt($request->password),
            "role_id" => 2,
        ];
        DB::table("users")->insert($user);

        $student = [
            "nim" => $request->nim,
            "province_id" => $request->province_id,
            "city_id" => $request->city_id,
            "start_education_year" => $request->start_education_year,
            "status" => $request->status,
            "photo" => $request->photo,
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];
        DB::table("students")->insert($student);
        return response()->json([
            'success' => true,
            'message' => 'Student successfully registered'
        ], 201);
    }
    public function update(Request $request)
    {
        if (Auth::user()->role_id == 2) {
            $id = Auth::user()->ref_id;
        } else {
            $id = $request->id;
        }
        $this->validate($request, [
            'nim' => 'required|string|unique:students,nim,' . $id,
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'start_education_year' => 'required|integer',
            'status' => 'required|string',
            'photo' => 'nullable|string',
        ]);
        $data = [
            "nim" => $request->nim,
            "province_id" => $request->province_id,
            "city_id" => $request->city_id,
            "start_education_year" => $request->start_education_year,
            "status" => $request->status,
            "photo" => $request->photo,
            "updated_by" => Auth::user()->id,
        ];
        DB::table("students")->where('id', $id)->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Student successfully updated'
        ], 201);
    }
}
