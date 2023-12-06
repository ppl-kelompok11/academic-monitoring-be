<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminsController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('jwtmiddleware');
    }
    public function show($id)
    {
        $student = DB::table('admins')
            ->select('admins.*', 'users.email', 'provinces.province_name as province_name', 'cities.city_name as city_name')
            ->leftJoin('users', 'admins.id', '=', 'users.ref_id')
            ->leftJoin('provinces', 'admins.province_id', '=', 'provinces.id')
            ->leftJoin('cities', 'admins.city_id', '=', 'cities.id')
            ->where('role_id', 1)
            ->where('admins.id', $id)
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
    public function update(Request $request)
    {
        $id = Auth::user()->ref_id;
        $old_data_user = db::table('users')->where('ref_id', $id)->where('role_id', 1)->first();
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|unique:users,email,' . $old_data_user->id,
            'name' => 'required|string',
            'nip' => 'required|string|unique:admins,nip,' . $id,
            'province_id' => 'required|integer|exists:provinces,id',
            'city_id' => 'required|integer|exists:cities,id',
            'address' => 'required|string',
            'phone' => 'required|string',
            'photo' => 'nullable',
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
                $new_path = 'admins' . '/' . $original_name . '.' . $ext;
                Storage::move($file, $new_path);
                $request->$field_upload = $new_path;
            }
        }

        $user_data = [
            'email' => $request->email,
        ];

        $admin_data = [
            'name' => $request->name,
            'nip' => $request->nip,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'phone' => $request->phone,
            'photo' => $request->photo,
        ];

        DB::table('users')->where('ref_id', $id)->where('role_id', 1)->update($user_data);

        DB::table('admins')->where('id', $id)->update($admin_data);
        return response()->json([
            'success' => true,
            'message' => 'Data successfully updated'
        ], 201);
    }
}
