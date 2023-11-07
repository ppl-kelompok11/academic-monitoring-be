<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CoreService\CoreResponse;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;
use App\CoreService\CoreException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Intervention\Image\Facades\Image;

class FileController extends Controller
{
    public function upload()
    {
        // $req = request()->all();
        // $path = request()->file('file')->store('tmp');
        $file = request()->file('file');
        $originalname = $file->getClientOriginalName();

        if (Storage::exists("tmp/" . $originalname)) {
            // create new name with date time format and extension
            $originalname = date("YmdHis") . "." . $file->getClientOriginalExtension();
        }
        $path = $file->storeAs('tmp', $originalname);
        $ext = pathinfo(storage_path($path), PATHINFO_EXTENSION);

        $url = URL::to('api/file/temp-file/' . $originalname);
        $result = [
            "url" => $url,
            "filename" => $originalname,
            "path" => $path,
            "ext" => $ext
        ];
        return response()->json($result);
    }

    public function getTempFile($originalname)
    {
        $data = "tmp/" . $originalname;
        if (Storage::exists($data)) {
            $file = Storage::get($data);
            $type = Storage::mimeType($data);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        } else {
            $path = "default/notfound.png";
            $file = Storage::get($path);
            $type = Storage::mimeType($path);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        }
    }

    public function getFile($model, $fileName)
    {
        dd("masuk");
        $data = $fileName;
        if (Storage::exists($data)) {
            dd("ada");
            $file = Storage::get($data);
            $type = Storage::mimeType($data);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        } else {
            $path = "default/notfound.png";
            $file = Storage::get($path);
            $type = Storage::mimeType($path);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        }
    }
}
