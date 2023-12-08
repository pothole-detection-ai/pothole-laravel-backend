<?php

use Intervention\Image\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

// app/helpers.php

if (!function_exists('storeImage')) {
    /**
     * Store an image from base64 data.
     *
     * @param string $imageData  The base64-encoded image data or URL.
     * @param string $folderName The folder name where the image will be stored.
     * @param string $slug       The file name or slug for the image.
     * @param int    $maxWidth   The maximum width of the image.
     * @return string|bool       The stored file path or false on failure.
     */
    function storeImage($imageData, $folderName, $imageName, $maxWidth = 800)
    {
        // check existing folder
        if (!Storage::exists('public/' . $folderName)) {
            Storage::makeDirectory('public/' . $folderName);
        }

        // check if the image data is a URL
        if (filter_var($imageData, FILTER_VALIDATE_URL)) {
            // get the image data from the URL
            $imageData = file_get_contents($imageData);

            // store the image
            $imagePath = $folderName . '/' . $imageName . '.jpg';
            $imagePublicPath = 'public/' . $imagePath;

            // store the image with file_put_contents
            $store = file_put_contents(storage_path('app/' . $imagePublicPath), $imageData);

            // check if the image was stored
            if ($store) {
                return $imagePath;
            }
        }

        // check if the image data is base64-encoded
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            // get the image type
            $imageType = strtolower($type[1]);

            // check if the image type is allowed
            if (!in_array($imageType, ['jpg', 'jpeg', 'gif', 'png'])) {
                return false;
            }

            // get the image data without the type prefix
            $imageData = substr($imageData, strpos($imageData, ',') + 1);

            // decode the base64 data
            $imageData = base64_decode($imageData);

            // check if the image was properly decoded
            if (!$imageData) {
                return false;
            }

            // create the image
            $image = imagecreatefromstring($imageData);

            // check if the image was created
            if (!$image) {
                return false;
            }

            // get the image width and height
            $width = imagesx($image);
            $height = imagesy($image);

            // calculate the height from the given width to maintain the aspect ratio
            $newHeight = floor($height * ($maxWidth / $width));

            // create a new temporary image
            $tmp = imagecreatetruecolor($maxWidth, $newHeight);

            // copy and resize the old image into the new temporary image
            imagecopyresampled($tmp, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);

            // create the new image file
            $imagePath = $folderName . '/' . $imageName . '.jpg';
            $imagePublicPath = 'public/' . $imagePath;


            // store the image with imagejpeg
            $store = imagejpeg($tmp, storage_path('app/' . $imagePublicPath), 100);

            // check if the image was stored
            if ($store) {
                return $imagePath;
            }
        }

        // return false if the image can't be stored
        return false;
    }
}
