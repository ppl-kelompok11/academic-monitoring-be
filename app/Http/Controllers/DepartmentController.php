<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('jwtmiddleware');
    }
    public function show($id)
    {
        $student = DB::table('department')
            ->select('department.*', 'users.email')
            ->leftJoin('users', 'department.id', '=', 'users.ref_id')
            ->where('role_id', 4)
            ->where('department.id', $id)
            ->first();

        return response()->json($student);
    }
    public function update(Request $request)
    {
        $id = Auth::user()->ref_id;
        $old_data_user = db::table('users')->where('ref_id', $id)->where('role_id', 4)->first();
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|unique:users,email,' . $old_data_user->id,
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::table('users')->where('ref_id', $id)->where('role_id', 4)->update([
            'email' => $request->email,
        ]);

        DB::table('department')->where('id', $id)->update([
            'name' => $request->name,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Data successfully updated'
        ], 201);
    }
}
