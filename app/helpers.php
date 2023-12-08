<?php

use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;

if (!function_exists('generateFiledCode')) {
    function generateFiledCode($code)
    {
        $result = $code . '-' . date('s') . date('Y') . date('m') . date('d') . date('h') . date('i') . mt_rand(10000, 99999);

        return $result;
    }
}

if (!function_exists('validateThis')) {
    function validateThis($request, $rules = array())
    {
        return Validator::make($request->all(), $rules);
    }
}

if (!function_exists('validationMessage')) {
    function validationMessage($validation)
    {
        $validate = collect($validation)->flatten();
        return $validate->values()->all();
    }
}

if (!function_exists('storeImage')) {
    function storeImage($image_data, $folder_name, $slug = null, $max_width = 1200, $max_height = 800)
    {
        if ($slug === null) {
            $slug = "IMG";
        }

        $image_name = $slug . "-" . time() . '-' . rand(0, 99999) . '.jpg';
        $path = public_path('assets/media/' . $folder_name);
        $image_path = $path . '/' . $image_name;

        // Ensure the folder exists or create it
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        // Fetch image content if it's a URL
        if (filter_var($image_data, FILTER_VALIDATE_URL)) {
            $imageContent = file_get_contents($image_data);

            // Check if content was fetched successfully
            if ($imageContent !== false) {
                $image = Image::make($imageContent)->resize($max_width, $max_height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->save($image_path);
            }
        } else {
            // Extract base64 data
            $base64Prefix = 'data:image';
            if (strpos($image_data, $base64Prefix) !== false) {
                $image_data = substr($image_data, strpos($image_data, ',') + 1);
            }

            // Decode image data
            $image_data = base64_decode($image_data);

            // Check if decoding was successful before saving and resizing
            if ($image_data !== false) {
                $image = Image::make($image_data)->resize($max_width, $max_height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->save($image_path);
            }
        }

        return $image_name;
    }
}
