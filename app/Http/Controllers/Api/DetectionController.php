<?php

namespace App\Http\Controllers\Api;

use App\Models\Detection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiController;
use App\Models\Pothole;
use App\Models\PotholeDepthCollectionData;
use Illuminate\Support\Facades\Http;

class DetectionController extends ApiController
{
    public function index()
    {
        $data = Detection::where('detections.is_deleted', 0)
            ->get();

        return $this->sendResponse(0, "Data pendeteksian lubang berhasil ditemukan", $data);
    }


    public function store(Request $request)
    {
        // === START:VALIDATION ===
        $rules = [
            'detection_latitude' => 'required|max:255',
            'detection_longitude' => 'required|max:255',
            'detection_image' => 'required',
            'detection_type' => 'required|max:255', // CAPTURE or REALTIME
            'detection_algorithm' => 'required|max:255', // YOLOV8-DEPTHINFO or MASKRCNN-SONARROBOT
            'detection_by' => 'required|max:255',
            'pothole_data' => 'required',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Parameter tidak terpenuhi', validationMessage($validator->errors()));
        }

        // => VALIDATE LAT LONG
        $detection_latitude = $request->detection_latitude;
        if ($detection_latitude < -90 || $detection_latitude > 90) {
            return $this->sendError(2, "Nilai latitude tidak valid, harus berada diantara -90 dan 90");
        }

        $detection_longitude = $request->detection_longitude;
        if ($detection_longitude < -180 || $detection_longitude > 180) {
            return $this->sendError(2, "Nilai longitude tidak valid, harus berada diantara -180 dan 180");
        }

        // => VALIDATE DETECTION IMAGE
        $detection_image_name = generateFiledCode('DETECTION_IMAGE');
        $detection_image_folder = 'detection_images';
        $detection_image_path = storeImage($request->detection_image, $detection_image_folder, $detection_image_name);
        if (!$detection_image_path) {
            return $this->sendError(2, "Gambar harus berbentuk base64 atau URL");
        }

        // => VALIDATE DETECTION TYPE
        $detection_type = $request->detection_type;
        if ($detection_type != 'CAPTURE' && $detection_type != 'REALTIME') {
            return $this->sendError(2, "Tipe deteksi harus berupa CAPTURE atau REALTIME");
        }

        // => VALIDATE DETECTION ALGORITHM
        $detection_algorithm = $request->detection_algorithm;
        if ($detection_algorithm != 'YOLOV8-DEPTHINFO' && $detection_algorithm != 'MASKRCNN-SONARROBOT') {
            return $this->sendError(2, "Algoritma pendeteksian harus berupa YOLOV8-DEPTHINFO atau MASKRCNN-SONARROBOT");
        }

        // => VALIDATE DETECTION BY (check if user_code exists)
        $detection_by = $request->detection_by;
        $check_user = DB::table('users')->where('user_code', $detection_by)->first();
        if (!$check_user) {
            return $this->sendError(2, "User tidak ditemukan");
        }

        // => VALIDATE POTHOLE DATA
        $pothole_data = $request->pothole_data;
        $potholes = $this->validatePotholeData($pothole_data);

        // check if pothole_data is incorrect format
        if (!is_array($potholes)) {
            return $this->sendError(2, $potholes);
        }

        // === END:VALIDATION ===
        // === START:CREATE Pothole LOGIC ===
        DB::beginTransaction();
        try {
            $detection_code = generateFiledCode('DETECTION');
            // HIT API REVERSE GEOCODING TO GET DETECTION LOCATION
            $api_reverse_geocoding = "https://geocode.maps.co/reverse?lat=" . $detection_latitude . "&lon=" . $detection_longitude . "&api_key=65975f7d3f14b145509135xly6961bd";
            $request_api_reverse_geocoding = file_get_contents($api_reverse_geocoding);
            // check if request_api_reverse_geocoding is false
            if (!$request_api_reverse_geocoding) {
                return $this->sendError(2, "Gagal mendapatkan lokasi pendeteksian lubang");
            }
            $request_api_reverse_geocoding = json_decode($request_api_reverse_geocoding, true);
            $detection_location = $request_api_reverse_geocoding['display_name'];

            $data = Detection::create([
                'detection_code' => $detection_code,
                'detection_latitude' => $detection_latitude,
                'detection_longitude' => $detection_longitude,
                'detection_location' => $detection_location,
                'detection_image' => $detection_image_path,
                'detection_type' => $detection_type,
                'detection_algorithm' => $detection_algorithm,
                'detection_by' => $detection_by,
            ]);


            foreach ($potholes as $pothole) {
                Pothole::create([
                    'pothole_code' => generateFiledCode('POTHOLE'),
                    'pothole_detection_code' => $detection_code,
                    'pothole_object_number' => $pothole['pothole_object_number'],
                    'pothole_type' => $pothole['pothole_type'],
                    'pothole_length' => $pothole['pothole_length'],
                    'pothole_width' => $pothole['pothole_width'],
                    'pothole_height' => $pothole['pothole_height'],
                ]);
            }

            DB::commit();

            $data['potholes'] = $potholes;
            return $this->sendResponse(0, "Data pendeteksian berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Data pendeteksian gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE Pothole LOGIC ===
    }

    public function validatePotholeData($pothole_data)
    {
        // pothole_data contains:
        //    - pothole_object_number (numeric)
        //    - pothole_type || SERIOUSLY_DAMAGED or LESS_DAMAGED
        //    - pothole_length (numeric)
        //    - pothole_width (numeric)
        //    - pothole_height (numeric)
        // pothole_data format in string of array, it can be more than 1 pothole:
        //    - pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height||pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height||pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height

        if (!is_string($pothole_data)) {
            return "Data pendeteksian lubang harus berupa string";
        }

        // Regex pattern of 1 pothole
        // $pothole_pattern = '/(\d+):(SERIOUSLY_DAMAGED|LESS_DAMAGED):(\d+):(\d+):(\d+)/';

        $potholes = [];

        if (!strpos($pothole_data, '||')) {
            // check contain colon or not
            if (!strpos($pothole_data, ':')) {
                return "Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
            }

            // if contains colon, explode it
            $pothole_data_array = explode(':', $pothole_data);

            // check if array length is 5
            if (count($pothole_data_array) != 5) {
                return "Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
            }

            $pothole_object_number = $pothole_data_array[0];
            $pothole_type = $pothole_data_array[1];
            $pothole_length = $pothole_data_array[2];
            $pothole_width = $pothole_data_array[3];
            $pothole_height = $pothole_data_array[4];

            if (!$this->validateNumeric($pothole_object_number)) {
                return "pothole_object_number harus berupa angka";
            }

            if (!$this->validatePotholeType($pothole_type)) {
                return "pothole_type harus berupa SERIOUSLY_DAMAGED atau LESS_DAMAGED";
            }

            if (!$this->validateNumeric($pothole_length)) {
                return "pothole_length harus berupa angka";
            }

            if (!$this->validateNumeric($pothole_width)) {
                return "pothole_width harus berupa angka";
            }

            if (!$this->validateNumeric($pothole_height)) {
                return "pothole_height harus berupa angka";
            }

            $potholes = [
                [
                    'pothole_object_number' => $pothole_object_number,
                    'pothole_type' => $pothole_type,
                    'pothole_length' => $pothole_length,
                    'pothole_width' => $pothole_width,
                    'pothole_height' => $pothole_height,
                ]
            ];
        } else {
            $pothole_data_array = explode('||', $pothole_data);

            // check after the last || is empty or not
            if ($pothole_data_array[count($pothole_data_array) - 1] == '') {
                return "Format pothole_data tidak valid. Hapus separator '||' diakhir string";
            }

            $i = 1;
            foreach ($pothole_data_array as $pothole_data) {
                // check contain colon or not
                if (!strpos($pothole_data, ':')) {
                    return "Object ke-" . $i . " => Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
                }

                // if contains colon, explode it
                $pothole_data_array = explode(':', $pothole_data);

                // check if array length is 5
                if (count($pothole_data_array) != 5) {
                    return "Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height||pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
                }

                $pothole_object_number = $pothole_data_array[0];
                $pothole_type = $pothole_data_array[1];
                $pothole_length = $pothole_data_array[2];
                $pothole_width = $pothole_data_array[3];
                $pothole_height = $pothole_data_array[4];

                if (!$this->validateNumeric($pothole_object_number)) {
                    return "Object ke-" . $i . " pothole_object_number harus berupa angka";
                }

                if (!$this->validatePotholeType($pothole_type)) {
                    return "Object ke-" . $i . " pothole_type harus berupa SERIOUSLY_DAMAGED atau LESS_DAMAGED";
                }

                if (!$this->validateNumeric($pothole_length)) {
                    return "Object ke-" . $i . " pothole_length harus berupa angka";
                }

                if (!$this->validateNumeric($pothole_width)) {
                    return "Object ke-" . $i . " pothole_width harus berupa angka";
                }

                if (!$this->validateNumeric($pothole_height)) {
                    return "Object ke-" . $i . " => pothole_height harus berupa angka";
                }

                $potholes[] = [
                    'pothole_object_number' => $pothole_object_number,
                    'pothole_type' => $pothole_type,
                    'pothole_length' => $pothole_length,
                    'pothole_width' => $pothole_width,
                    'pothole_height' => $pothole_height,
                ];

                $i++;
            }
        }
        return $potholes;
    }

    public function validateNumeric($pothole_object_number)
    {
        if (!is_numeric($pothole_object_number)) {
            return false;
        }
        return true;
    }

    public function validatePotholeType($pothole_type)
    {
        if ($pothole_type != 'SERIOUSLY_DAMAGED' && $pothole_type != 'LESS_DAMAGED') {
            return false;
        }
        return true;
    }

    public function show(string $detection_code)
    {
        $data = Detection::where('detection_code', $detection_code)
            ->where('is_deleted', 0)
            ->first();

        if (!$data) {
            return $this->sendError(1, "Data pendeteksian lubang tidak ditemukan");
        }
        return $this->sendResponse(0, "Data pendeteksian lubang berhasil ditemukan", $data);
    }

    public function update(Request $request, string $detection_code)
    {
        $rules = [
            'detection_code' => 'required|max:255',
            'detection_latitude' => 'required|max:255',
            'detection_longitude' => 'required|max:255',
            'detection_image' => 'required',
            'detection_type' => 'required|max:255', // CAPTURE or REALTIME
            'detection_algorithm' => 'required|max:255', // YOLOV8-DEPTHINFO or MASKRCNN-SONARROBOT
            'detection_by' => 'required|max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Parameter tidak terpenuhi', validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING DETECTION
        $check_detection = Detection::where('detection_code', $detection_code)
            ->where('is_deleted', 0)->first();
        if (!$check_detection) {
            return $this->sendError(2, 'Data pendeteksian lubang tidak ditemukan');
        }

        // => VALIDATE LATITUDE LONGITUDE
        $detection_latitude = $request->detection_latitude;
        if ($detection_latitude < -90 || $detection_latitude > 90) {
            return $this->sendError(2, "Nilai latitude tidak valid, harus berada diantara -90 dan 90");
        }

        $detection_longitude = $request->detection_longitude;
        if ($detection_longitude < -180 || $detection_longitude > 180) {
            return $this->sendError(2, "Nilai longitude tidak valid, harus berada diantara -180 dan 180");
        }

        // => VALIDATE DETECTION IMAGE
        $detection_image_name = generateFiledCode('DETECTION_IMAGE');
        $detection_image_folder = 'detection_images';
        $detection_image_path = storeImage($request->detection_image, $detection_image_folder, $detection_image_name);
        if (!$detection_image_path) {
            return $this->sendError(2, "Gambar harus berbentuk base64 atau URL");
        }

        // => VALIDATE DETECTION TYPE
        $detection_type = $request->detection_type;
        if ($detection_type != 'CAPTURE' && $detection_type != 'REALTIME') {
            return $this->sendError(2, "Tipe deteksi harus berupa CAPTURE atau REALTIME");
        }

        // => VALIDATE DETECTION ALGORITHM
        $detection_algorithm = $request->detection_algorithm;
        if ($detection_algorithm != 'YOLOV8-DEPTHINFO' && $detection_algorithm != 'MASKRCNN-SONARROBOT') {
            return $this->sendError(2, "Algoritma pendeteksian harus berupa YOLOV8-DEPTHINFO atau MASKRCNN-SONARROBOT");
        }

        // => VALIDATE DETECTION BY (check if user_code exists)
        $detection_by = $request->detection_by;
        $check_user = DB::table('users')->where('user_code', $detection_by)->first();
        if (!$check_user) {
            return $this->sendError(2, "User tidak ditemukan");
        }

        // HIT API REVERSE GEOCODING TO GET DETECTION LOCATION
        $api_reverse_geocoding = "https://geocode.maps.co/reverse?lat=" . $detection_latitude . "&lon=" . $detection_longitude . "&api_key=65975f7d3f14b145509135xly6961bd";
        $request_api_reverse_geocoding = file_get_contents($api_reverse_geocoding);
        // check if request_api_reverse_geocoding is false
        if (!$request_api_reverse_geocoding) {
            return $this->sendError(2, "Gagal mendapatkan lokasi pendeteksian lubang");
        }
        $request_api_reverse_geocoding = json_decode($request_api_reverse_geocoding, true);
        $detection_location = $request_api_reverse_geocoding['display_name'];

        DB::beginTransaction();
        try {
            $check_detection->update([
                'detection_latitude' => $detection_latitude,
                'detection_longitude' => $detection_longitude,
                'detection_location' => $detection_location,
                'detection_image' => $detection_image_path,
                'detection_type' => $detection_type,
                'detection_algorithm' => $detection_algorithm,
                'detection_by' => $detection_by,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Data pendeteksian lubang berhasil diupdate", $check_detection);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Data pendeteksian lubang gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $detection_code)
    {
        $data = Detection::where('detection_code', $detection_code)
            ->where('is_deleted', 0)
            ->first();

        if (!$data) {
            return $this->sendError(1, "Data pendeteksian lubang tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                'is_deleted' => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Data pendeteksian lubang berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Data pendeteksian lubang gagal dihapus", $e->getMessage());
        }
    }

    public function pothole_maps($latitude = -2.984515005097713, $longitude = 104.73386996077537, $radius = 10)
    {
        $conditions = [
            'detections.is_deleted' => 0,
            'potholes.is_deleted' => 0,
        ];

        $total_pothole_detected = $this->countPotholes($conditions);

        $conditions['potholes.pothole_type'] = 'SERIOUSLY_DAMAGED';
        $total_pothole_seriously_damaged = $this->countPotholes($conditions);

        $conditions['potholes.pothole_type'] = 'LESS_DAMAGED';
        $total_pothole_less_damaged = $this->countPotholes($conditions);

        $nearbyDetections = $this->getNearbyDetections($latitude, $longitude, $radius);

        $data = [
            'total_pothole_detected' => $total_pothole_detected,
            'total_pothole_seriously_damaged' => $total_pothole_seriously_damaged,
            'total_pothole_less_damaged' => $total_pothole_less_damaged,
            'center_latitude' => $latitude,
            'center_longitude' => $longitude,
            'nearby_detections' => $nearbyDetections,
        ];

        return $this->sendResponse(0, "Data pendeteksian lubang berhasil ditemukan", $data);
    }

    protected function countPotholes($conditions)
    {
        return DB::table('detections')
            ->join('potholes', 'detections.detection_code', '=', 'potholes.pothole_detection_code')
            ->where($conditions)
            ->count();
    }

    protected function getNearbyDetections($latitude, $longitude, $radius)
    {
        return DB::table('detections')
            ->join('potholes', 'detections.detection_code', '=', 'potholes.pothole_detection_code')
            ->select('detections.detection_code', 'detections.detection_latitude', 'detections.detection_longitude', 'detections.detection_location', 'detections.detection_image', 'detections.detection_type', 'detections.detection_algorithm', 'detections.detection_by', 'detections.created_at', 'detections.updated_at')
            ->selectRaw('( 6371 * acos( cos( radians(?) ) * cos( radians( detection_latitude ) ) * cos( radians( detection_longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( detection_latitude ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
            ->where('detections.is_deleted', 0)
            ->where('potholes.is_deleted', 0)
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();
    }

    // FLASK API
    public function detect(Request $request)
    {
        $rules = [
            'detection_latitude' => 'required|max:255',
            'detection_longitude' => 'required|max:255',
            'detection_image' => 'required',
            // 'detection_type' => 'required|max:255', // CAPTURE or REALTIME
            // 'detection_algorithm' => 'required|max:255', // YOLOV8-DEPTHINFO or MASKRCNN-SONARROBOT
            'detection_by' => 'required|max:255',
            // 'pothole_data' => 'required',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Parameter tidak terpenuhi', validationMessage($validator->errors()));
        }

        // => VALIDATE LAT LONG
        $detection_latitude = $request->detection_latitude;
        if ($detection_latitude < -90 || $detection_latitude > 90) {
            return $this->sendError(2, "Nilai latitude tidak valid, harus berada diantara -90 dan 90");
        }

        $detection_longitude = $request->detection_longitude;
        if ($detection_longitude < -180 || $detection_longitude > 180) {
            return $this->sendError(2, "Nilai longitude tidak valid, harus berada diantara -180 dan 180");
        }

        // => VALIDATE DETECTION BY (check if user_code exists)
        $detection_by = $request->detection_by;
        $check_user = DB::table('users')->where('user_code', $detection_by)->first();
        if (!$check_user) {
            return $this->sendError(2, "User tidak ditemukan");
        }


        $base64_image_string = $request->detection_image;

        $base64_image_string = str_replace('data:image/png;base64,', '', $base64_image_string);
        $base64_image_string = str_replace('data:image/jpeg;base64,', '', $base64_image_string);
        $base64_image_string = str_replace('data:image/jpg;base64,', '', $base64_image_string);

        $base64_image_string = $this->cropAndResizeBase64Image($base64_image_string);

        $response = Http::post(env('FLASK_API_URL') . '/detect', [
            'base64_image_string' => $base64_image_string,
        ]);
        $response = $response->json();
        $total_pothole = $response['total_objects'];
        $base64_detected_image_string = $response['base64_image_string'];

        // INSERT TO DATABASE
        $original_image_name = generateFiledCode('ORIGINAL_IMAGE');
        $original_image_folder = 'original_images';
        $original_image_path = storeImage($base64_image_string, $original_image_folder, $original_image_name);
        if (!$original_image_path) {
            return $this->sendError(2, "Gambar harus berbentuk base64 atau URL");
        }

        $detected_image_name = generateFiledCode('DETECTED_IMAGE');
        $detected_image_folder = 'detected_images';
        $detected_image_path = storeImage($base64_detected_image_string, $detected_image_folder, $detected_image_name);
        if (!$detected_image_path) {
            return $this->sendError(2, "Gambar harus berbentuk base64 atau URL");
        }

        DB::beginTransaction();
        try {
            $detection_code = generateFiledCode('DETECTION');
            // HIT API REVERSE GEOCODING TO GET DETECTION LOCATION
            $api_reverse_geocoding = "https://geocode.maps.co/reverse?lat=" . $detection_latitude . "&lon=" . $detection_longitude . "&api_key=65975f7d3f14b145509135xly6961bd";
            $request_api_reverse_geocoding = file_get_contents($api_reverse_geocoding);
            // check if request_api_reverse_geocoding is false
            if (!$request_api_reverse_geocoding) {
                return $this->sendError(2, "Gagal mendapatkan lokasi pendeteksian lubang");
            }
            $request_api_reverse_geocoding = json_decode($request_api_reverse_geocoding, true);
            $detection_location = $request_api_reverse_geocoding['display_name'];

            $data = Detection::create([
                'detection_code' => $detection_code,
                'detection_latitude' => $detection_latitude,
                'detection_longitude' => $detection_longitude,
                'detection_location' => $detection_location,
                'detection_image' => $original_image_path,
                'detection_image_result' => $detected_image_path,
                'detection_by' => $detection_by,
            ]);

            $potholes = [];
            for ($i = 1; $i <= $total_pothole; $i++) {
                $potholes[] = Pothole::create([
                    'pothole_code' => generateFiledCode('POTHOLE'),
                    'pothole_detection_code' => $detection_code,
                    'pothole_object_number' => strval($i),
                    'pothole_type' => 'SERIOUSLY_DAMAGED',
                    'pothole_length' => strval(mt_rand(3, 7)),
                    'pothole_width' => strval(mt_rand(3, 7)),
                    'pothole_height' => strval(mt_rand(1, 3)),
                ]);
            }

            $data['potholes'] = $potholes;

            DB::commit();


            return $this->sendResponse(0, "Data pendeteksian berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Data pendeteksian gagal ditambahkan", $e->getMessage());
        }

        return response()->json($response);
    }

    protected function cropAndResizeBase64Image($base64ImageString, $width = 960, $height = 540)
    {
        // Decode base64 image string
        $decodedImage = base64_decode($base64ImageString);

        // Create image resource from decoded string
        $image = imagecreatefromstring($decodedImage);

        // Get original image dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Calculate aspect ratio
        $aspectRatio = $originalWidth / $originalHeight;

        // Calculate new dimensions based on aspect ratio
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
        if ($width / $height > $aspectRatio) {
            $newWidth = $height * $aspectRatio;
        } else {
            $newHeight = $width / $aspectRatio;
        }

        // Create cropped image with target aspect ratio
        $croppedImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($croppedImage, $image, 0, 0, ($originalWidth - $newWidth) / 2, ($originalHeight - $newHeight) / 2, $width, $height, $newWidth, $newHeight);

        // Create final resized image
        $resizedImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($resizedImage, $croppedImage, 0, 0, 0, 0, $width, $height, $width, $height);

        // Output final image as base64 string
        ob_start();
        imagejpeg($resizedImage, null, 100);
        $output = ob_get_clean();
        $base64ResizedImage = base64_encode($output);

        // Free up memory
        imagedestroy($image);
        imagedestroy($croppedImage);
        imagedestroy($resizedImage);

        return $base64ResizedImage;
    }

    public function store_v2(Request $request)
    {
        // === START:VALIDATION ===
        $rules = [
            'detection_latitude' => 'required|max:255',
            'detection_longitude' => 'required|max:255',
            'detection_image' => 'required',
            'detection_type' => 'required|max:255', // CAPTURE or REALTIME
            'detection_algorithm' => 'required|max:255', // YOLOV8-DEPTHINFO or MASKRCNN-SONARROBOT
            'detection_by' => 'required|max:255',
            'pothole_data' => 'required',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Parameter tidak terpenuhi', validationMessage($validator->errors()));
        }

        // => VALIDATE LAT LONG
        $detection_latitude = $request->detection_latitude;
        if ($detection_latitude < -90 || $detection_latitude > 90) {
            return $this->sendError(2, "Nilai latitude tidak valid, harus berada diantara -90 dan 90");
        }

        $detection_longitude = $request->detection_longitude;
        if ($detection_longitude < -180 || $detection_longitude > 180) {
            return $this->sendError(2, "Nilai longitude tidak valid, harus berada diantara -180 dan 180");
        }

        // => VALIDATE DETECTION IMAGE
        $detection_image_name = generateFiledCode('DETECTION_IMAGE');
        $detection_image_folder = 'detection_images';
        $detection_image_path = storeImage($request->detection_image, $detection_image_folder, $detection_image_name);
        if (!$detection_image_path) {
            return $this->sendError(2, "Gambar harus berbentuk base64 atau URL");
        }

        // => VALIDATE DETECTION TYPE
        $detection_type = $request->detection_type;
        if ($detection_type != 'CAPTURE' && $detection_type != 'REALTIME') {
            return $this->sendError(2, "Tipe deteksi harus berupa CAPTURE atau REALTIME");
        }

        // => VALIDATE DETECTION ALGORITHM
        $detection_algorithm = $request->detection_algorithm;
        if ($detection_algorithm != 'YOLOV8-DEPTHINFO' && $detection_algorithm != 'MASKRCNN-SONARROBOT') {
            return $this->sendError(2, "Algoritma pendeteksian harus berupa YOLOV8-DEPTHINFO atau MASKRCNN-SONARROBOT");
        }

        // => VALIDATE DETECTION BY (check if user_code exists)
        $detection_by = $request->detection_by;
        $check_user = DB::table('users')->where('user_code', $detection_by)->first();
        if (!$check_user) {
            return $this->sendError(2, "User tidak ditemukan");
        }

        // => VALIDATE POTHOLE DATA
        $pothole_data = $request->pothole_data;
        $potholes = $this->validatePotholeData($pothole_data);

        // check if pothole_data is incorrect format
        if (!is_array($potholes)) {
            return $this->sendError(2, $potholes);
        }

        // === END:VALIDATION ===
        // === START:CREATE Pothole LOGIC ===
        DB::beginTransaction();
        try {
            $detection_code = generateFiledCode('DETECTION');
            // HIT API REVERSE GEOCODING TO GET DETECTION LOCATION
            $api_reverse_geocoding = "https://geocode.maps.co/reverse?lat=" . $detection_latitude . "&lon=" . $detection_longitude . "&api_key=65975f7d3f14b145509135xly6961bd";
            $request_api_reverse_geocoding = file_get_contents($api_reverse_geocoding);
            // check if request_api_reverse_geocoding is false
            if (!$request_api_reverse_geocoding) {
                return $this->sendError(2, "Gagal mendapatkan lokasi pendeteksian lubang");
            }
            $request_api_reverse_geocoding = json_decode($request_api_reverse_geocoding, true);
            $detection_location = $request_api_reverse_geocoding['display_name'];

            $data = Detection::create([
                'detection_code' => $detection_code,
                'detection_latitude' => $detection_latitude,
                'detection_longitude' => $detection_longitude,
                'detection_location' => $detection_location,
                'detection_image' => $detection_image_path,
                'detection_type' => $detection_type,
                'detection_algorithm' => $detection_algorithm,
                'detection_by' => $detection_by,
            ]);


            foreach ($potholes as $pothole) {
                Pothole::create([
                    'pothole_code' => generateFiledCode('POTHOLE'),
                    'pothole_detection_code' => $detection_code,
                    'pothole_object_number' => $pothole['pothole_object_number'],
                    'pothole_type' => $pothole['pothole_type'],
                    'pothole_length' => $pothole['pothole_length'],
                    'pothole_width' => $pothole['pothole_width'],
                    'pothole_height' => $pothole['pothole_height'],
                ]);
            }

            DB::commit();

            $data['potholes'] = $potholes;
            return $this->sendResponse(0, "Data pendeteksian berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Data pendeteksian gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE Pothole LOGIC ===
    }

    // PENGAMBILAN DATA
    public function pothole_depth_collection_data(Request $request)
    {
        $rules = [
            'depth1' => 'required|numeric',
            'depth2' => 'required|numeric',
            'depth3' => 'required|numeric',
            'depth4' => 'required|numeric',
            'latitude' => 'required|max:255',
            'longitude' => 'required|max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Parameter tidak terpenuhi', validationMessage($validator->errors()));
        }

        // => VALIDATE LAT LONG
        $latitude = $request->latitude;
        if ($latitude < -90 || $latitude > 90) {
            return $this->sendError(2, "Nilai latitude tidak valid, harus berada diantara -90 dan 90");
        }

        $longitude = $request->longitude;
        if ($longitude < -180 || $longitude > 180) {
            return $this->sendError(2, "Nilai longitude tidak valid, harus berada diantara -180 dan 180");
        }

        DB::beginTransaction();
        try {
            $data = PotholeDepthCollectionData::create([
                'pothole_depth_code' => generateFiledCode('POTHOLE_DEPTH'),
                'pothole_depth_1' => $request->depth1,
                'pothole_depth_2' => $request->depth2,
                'pothole_depth_3' => $request->depth3,
                'pothole_depth_4' => $request->depth4,
                'pothole_depth_latitude' => $latitude,
                'pothole_depth_longitude' => $longitude,
            ]);

            DB::commit();

            return $this->sendResponse(0, "Data berhasil disimpan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Data gagal disimpan", $e->getMessage());
        }
    }

    public function pothole_depth_collection_data_view()
    {
        $data = PotholeDepthCollectionData::where('is_deleted', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pothole_depth_collection_data', compact('data'));
    }
}
