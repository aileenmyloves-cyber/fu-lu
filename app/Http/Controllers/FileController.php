<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    /**
     * Server-side allowlist of safe upload extensions.
     */
    protected $allowed_mimes = ['jpg','jpeg','png','gif','webp','svg','bmp','pdf','doc','docx','xls','xlsx','csv','txt'];

    public function storeFile(Request $request)
    {
        $data = [];
        if ($request->hasFile('fileholder_files')) {
            // Intersect the requested extensions with the backend allowlist.
            $requested_mimes = collect(explode(',', (string) $request->mimes))
                ->map(fn($mime) => strtolower(trim($mime)))
                ->filter()
                ->intersect($this->allowed_mimes)
                ->values();
            $mimes = $requested_mimes->isNotEmpty() ? $requested_mimes->implode(',') : implode(',', $this->allowed_mimes);

            $validator = Validator::make($request->all(), [
                'fileholder_files' => 'required|file|mimes:'.$mimes.'|max:10240',
            ]);

            if ($validator->fails()) {
                $data['error'] = $validator->errors()->all();
                $data['status'] = false;

                return response()->json($data, 400);
            }

            $validated = $validator->safe()->all();

            $file_holder_files = $validated['fileholder_files'];
            $file_ext = $file_holder_files->getClientOriginalExtension();
            $file_store_name = Str::uuid().'.'.$file_ext;

            $data['path'] = "fileholder/img"; // store path

            $data['file_name'] = $file_store_name;
            $data['file_link'] = Storage::disk(Storage::getDefaultDriver())->url($data['path'].'/'.$data['file_name']);
            $data['file_type'] = $file_holder_files->getClientMimeType();
            $data['file_old_name'] = $file_holder_files->getClientOriginalName();

            $data['status'] = true;
            try {
                Storage::disk(Storage::getDefaultDriver())->putFileAs($data['path'], $file_holder_files, $data['file_name'], [
                    'visibility' => 'public',
                ]);

            } catch (Exception $e) {
                $data['status'] = false;
            }
        } else {
            $data['status'] = false;
            $data['error'] = 'Something went wrong! File is not detected.';
        }

        return response()->json($data, 200);
    }

    public function removeFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_info' => 'required|json',
        ]);

        if ($validator->fails()) {
            $data['error'] = $validator->errors()->all();
            $data['status'] = false;

            return response()->json($data, 400);
        }

        $validated = $validator->safe()->all();

        $file_info = json_decode($validated['file_info']);
        $data['status'] = true;
        try {
            Storage::disk(Storage::getDefaultDriver())->delete(rtrim($file_info->path, "/") . "/" . $file_info->file_name);

            $data['message'] = 'File Deleted Successfully!';
        } catch (Exception $e) {
            $data['status'] = false;
            $data['error'] = $e;
            $data['message'] = 'Something Went wrong! Please try again.';
        }

        $data['file_info'] = $file_info;

        return response()->json($data, 200);

    }
}