<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentsController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('jwtmiddleware');
    }
    public function index(Request $request)
    {
        $student = DB::table('students')
            ->select('students.*', 'users.email', 'users.id as user_id', 'users.active as active')
            ->join('users', 'students.id', '=', 'users.ref_id')
            ->where('role_id', 2);


        $search = $request->search;
        if (isset($search)) {
            $student = $student->where(function ($query) use ($search) {
                $query->whereRaw("UPPER(students.name) LIKE '" . strtoupper($search) . "%'")
                    ->orwhereRaw("UPPER(students.nim) LIKE '" . strtoupper($search) . "%'");
            });
        }

        if (Auth::user()->role_id == 3) {
            $student = $student->where('lecture_id', Auth::user()->ref_id);
        }



        // custom filter
        $skripsi_status = $request->skripsi_status;
        if ($skripsi_status) {
            $student = $student->leftJoin('skripsi', 'students.id', '=', 'skripsi.student_id');
            if ($skripsi_status == "graduate") {
                $student = $student->where('skripsi.verification_status', '02')->select('students.*', 'users.email', 'skripsi.grade');
            }

            if ($skripsi_status == "not_graduate") {
                $student = $student->where('skripsi.verification_status', '!=', '02')->orWhereNull('skripsi.verification_status')->select('students.*', 'users.email', 'skripsi.grade');
            }
        }

        $pkl_status = $request->pkl_status;
        if ($pkl_status) {
            $student = $student->leftJoin('pkl', 'students.id', '=', 'pkl.student_id');
            if ($pkl_status == "graduate") {
                $student = $student->where('pkl.verification_status', '02')->select('students.*', 'users.email', 'pkl.grade');
            }
            if ($pkl_status == "not_graduate") {
                $student = $student->where('pkl.verification_status', '!=', '02')->orWhereNull('pkl.verification_status')->select('students.*', 'users.email', 'pkl.grade');
            }
        }

        // start filter
        $filters = ["status", "start_education_year"];

        foreach ($filters as $filter) {
            if ($request->$filter) {
                $student = $student->where($filter, $request->$filter);
            }
        }
        $limit = 10;
        if ($request->limit) {
            $limit = $request->limit;
        }

        $student = $student->paginate($limit);

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
        $student = DB::table('students')
            ->select('students.*', 'users.email', 'lecture.name as lecture_name', 'provinces.province_name', 'cities.city_name')
            ->leftJoin('users', 'students.id', '=', 'users.ref_id')
            ->leftJoin('lecture', 'students.lecture_id', '=', 'lecture.id')
            ->leftJoin('provinces', 'students.province_id', '=', 'provinces.id')
            ->leftJoin('cities', 'students.city_id', '=', 'cities.id')
            ->where('role_id', 2)
            ->where('students.id', $id)
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
        // validate incoming request
        $this->validate($request, [
            'name' => 'required|string',
            'nim' => 'required|string|unique:students,nim',
            'province_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'start_education_year' => 'required|integer',
            'status' => 'required|string',
            'entrance_code' => 'required|string',
            'photo' => 'nullable|string',
        ]);
        $student = [
            "name" => $request->name,
            "nim" => $request->nim,
            "province_id" => $request->province_id,
            "city_id" => $request->city_id,
            "start_education_year" => $request->start_education_year,
            "lecture_id" => 1,
            "entrance_code" => $request->entrance_code,
            "status" => $request->status,
            "photo" => $request->photo,
            "created_by" => Auth::user()->id,
            "updated_by" => Auth::user()->id,
        ];
        $student_id = DB::table("students")->insertGetId($student);

        // create email with name + nim + @gmail.com

        // remove space
        // $name = str_replace(' ', '', $request->name);
        $user = [
            "username" => $request->nim,
            "password" => bcrypt('123456'),
            "ref_id" => $student_id,
            "role_id" => 2,
            "active" => false,
            "ref_table" => "students",
        ];
        DB::table("users")->insert($user);


        return response()->json([
            'success' => true,
            'message' => 'Student successfully registered'
        ], 201);
    }
    public function update(Request $request)
    {
        if (Auth::user()->role_id == 2) {
            $id = Auth::user()->ref_id;
            $old_data_user = db::table('users')->where('ref_id', $id)->where('role_id', 2)->first();
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|unique:users,email,' . $old_data_user->id,
                'gender' => 'nullable|string|in:male,female',
                'province_id' => 'nullable|integer|exists:provinces,id',
                'city_id' => 'nullable|integer|exists:cities,id',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);
        }
        if (Auth::user()->role_id == 1) {
            $id = $request->id;
            $old_data_user = db::table('users')->where('ref_id', $id)->where('role_id', 2)->first();
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:students,id',
                'name' => 'required|string',
                'nim' => 'required|string|unique:students,nim,' . $id,
                'email' => 'required|string|unique:users,email,' . $old_data_user->id,
                'gender' => 'nullable|string|in:male,female',
                'province_id' => 'nullable|integer|exists:provinces,id',
                'city_id' => 'nullable|integer|exists:cities,id',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'start_education_year' => 'required|integer',
                'status' => 'required|string',
                'photo' => 'nullable|string',
            ]);
        }


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }



        if (!$old_data_user->active && Auth::user()->role_id == 2) {
            if ($request->password == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is required',
                ], 422);
            }
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
                $new_path = 'students' . '/' . $original_name . '.' . $ext;
                Storage::move($file, $new_path);
                $request->$field_upload = $new_path;
            }
        }

        $data = [
            "gender" => $request->gender,
            "province_id" => $request->province_id,
            "city_id" => $request->city_id,
            "address" => $request->address,
            "phone" => $request->phone,
            "photo" => $request->photo,
            "updated_by" => Auth::user()->id,
        ];

        if (Auth::user()->role_id == 1) {
            $data['name'] = $request->name;
            $data['nim'] = $request->nim;
            $data['start_education_year'] = $request->start_education_year;
            $data['status'] = $request->start_experience_year;
        }

        DB::table("students")->where('id', $id)->update($data);

        if (Auth::user()->role_id == 2) {
            $user = [
                "email" => $request->email,
            ];

            if (!$old_data_user->active) {
                $user['password'] = bcrypt($request->password);
                $user['active'] = true;
            }
            db::table("users")->where('id', Auth::user()->id)->update($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student successfully updated'
        ], 201);
    }

    public function academic($id)
    {

        $student = DB::table('students')->where('id', $id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => "Student not found",
            ], 422);
        }

        $academic_history = DB::table('semester')
            ->leftJoin('irs', function ($join) use ($id) {
                $join->on('irs.semester_value', '=', 'semester.value')
                    ->where('irs.student_id', '=', $id);
            })
            ->leftJoin('khs', function ($join) use ($id) {
                $join->on('khs.semester_value', '=', 'semester.value')
                    ->where('khs.student_id', '=', $id);
            })
            ->leftJoin('pkl', function ($join) use ($id) {
                $join->on('pkl.semester_value', '=', 'semester.value')
                    ->where('pkl.student_id', '=', $id);
            })
            ->leftJoin('skripsi', function ($join) use ($id) {
                $join->on('skripsi.semester_value', '=', 'semester.value')
                    ->where('skripsi.student_id', '=', $id);
            })
            ->select('semester.value as semester_value', 'irs.id as irs_id', 'irs.verification_status as irs_verification_status', 'khs.id as khs_id', 'khs.verification_status as khs_verification_status', 'pkl.id as pkl_id', 'pkl.verification_status as pkl_verification_status', 'skripsi.id as skripsi_id', 'skripsi.verification_status as skripsi_verification_status')
            ->orderBy('semester.value', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $academic_history,
        ], 200);
    }
    static function excelImport(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|string',
            ]);
            $path = Storage::path($request->file);
            // load with phpspreadsheet
            $data = IOFactory::load($path);
            $data = $data->getActiveSheet()->toArray();
            // remove header in first row
            array_shift($data);

            array_map(function ($item) {
                $lecture = DB::table('lecture')->where('nip', $item[4])->first();
                $student = [
                    "name" => $item[0],
                    "nim" => $item[1],
                    "start_education_year" => $item[2],
                    "entrance_code" => $item[3],
                    "lecture_id" => $lecture->id,
                    "status" => "00",
                    "created_by" => Auth::user()->id,
                    "updated_by" => Auth::user()->id,
                ];
                $user = [
                    "username" => $item[1],
                    "password" => bcrypt('123456'),
                    "role_id" => 2,
                    "active" => false,
                    "ref_table" => "students",
                ];
                $student_id = DB::table("students")->insertGetId($student);
                $user['ref_id'] = $student_id;
                DB::table("users")->insert($user);
            }, $data);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Student successfully imported'
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    public function delete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:students,id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            DB::table("irs")->where('student_id', $request->id)->delete();
            DB::table("khs")->where('student_id', $request->id)->delete();
            DB::table("pkl")->where('student_id', $request->id)->delete();
            DB::table("skripsi")->where('student_id', $request->id)->delete();
            DB::table('users')->where('ref_id', $request->id)->where('role_id', 3)->delete();
            DB::table('students')->where('id', $request->id)->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
