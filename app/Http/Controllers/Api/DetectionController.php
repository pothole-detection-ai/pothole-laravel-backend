<?php

namespace App\Http\Controllers\Api;

use App\Models\Detection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiController;
use App\Models\Pothole;

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
        if($detection_latitude < -90 || $detection_latitude > 90) {
            return $this->sendError(2, "Nilai latitude tidak valid, harus berada diantara -90 dan 90");
        }

        $detection_longitude = $request->detection_longitude;
        if($detection_longitude < -180 || $detection_longitude > 180) {
            return $this->sendError(2, "Nilai longitude tidak valid, harus berada diantara -180 dan 180");
        }

        // => VALIDATE DETECTION IMAGE
        $detection_image_name = generateFiledCode('DETECTION_IMAGE');
        $detection_image_folder = 'detection_images';
        $detection_image_path = storeImage($request->detection_image, $detection_image_folder, $detection_image_name);
        if(!$detection_image_path) {
            return $this->sendError(2, "Gambar harus berbentuk base64 atau URL");
        }

        // => VALIDATE DETECTION TYPE
        $detection_type = $request->detection_type;
        if($detection_type != 'CAPTURE' && $detection_type != 'REALTIME') {
            return $this->sendError(2, "Tipe deteksi harus berupa CAPTURE atau REALTIME");
        }

        // => VALIDATE DETECTION ALGORITHM
        $detection_algorithm = $request->detection_algorithm;
        if($detection_algorithm != 'YOLOV8-DEPTHINFO' && $detection_algorithm != 'MASKRCNN-SONARROBOT') {
            return $this->sendError(2, "Algoritma pendeteksian harus berupa YOLOV8-DEPTHINFO atau MASKRCNN-SONARROBOT");
        }

        // => VALIDATE DETECTION BY (check if user_code exists)
        $detection_by = $request->detection_by;
        $check_user = DB::table('users')->where('user_code', $detection_by)->first();
        if(!$check_user) {
            return $this->sendError(2, "User tidak ditemukan");
        }

        // => VALIDATE POTHOLE DATA
        $pothole_data = $request->pothole_data;
        $potholes = $this->validatePotholeData($pothole_data);

        // check if pothole_data is incorrect format
        if(!is_array($potholes)) {
            return $this->sendError(2, $potholes);
        }

        // === END:VALIDATION ===
        // === START:CREATE Pothole LOGIC ===
        DB::beginTransaction();
        try {
            $detection_code = generateFiledCode('DETECTION');
            $data = Detection::create([
                'detection_code' => $detection_code,
                'detection_latitude' => $detection_latitude,
                'detection_longitude' => $detection_longitude,
                'detection_image' => $detection_image_path,
                'detection_type' => $detection_type,
                'detection_algorithm' => $detection_algorithm,
                'detection_by' => $detection_by,
            ]);


            foreach($potholes as $pothole) {
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

    public function validatePotholeData($pothole_data) {
        // pothole_data contains:
        //    - pothole_object_number (numeric)
        //    - pothole_type || SERIOUSLY_DAMAGED or LESS_DAMAGED
        //    - pothole_length (numeric)
        //    - pothole_width (numeric)
        //    - pothole_height (numeric)
        // pothole_data format in string of array, it can be more than 1 pothole:
        //    - pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height||pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height||pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height

        if(!is_string($pothole_data)) {
            return "Data pendeteksian lubang harus berupa string";
        }

        // Regex pattern of 1 pothole
        // $pothole_pattern = '/(\d+):(SERIOUSLY_DAMAGED|LESS_DAMAGED):(\d+):(\d+):(\d+)/';

        $potholes = [];

        if(!strpos($pothole_data, '||')) {
            // check contain colon or not
            if(!strpos($pothole_data, ':')) {
                return "Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
            }

            // if contains colon, explode it
            $pothole_data_array = explode(':', $pothole_data);

            // check if array length is 5
            if(count($pothole_data_array) != 5) {
                return "Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
            }

            $pothole_object_number = $pothole_data_array[0];
            $pothole_type = $pothole_data_array[1];
            $pothole_length = $pothole_data_array[2];
            $pothole_width = $pothole_data_array[3];
            $pothole_height = $pothole_data_array[4];

            if(!$this->validateNumeric($pothole_object_number)) {
                return "pothole_object_number harus berupa angka";
            }

            if(!$this->validatePotholeType($pothole_type)) {
                return "pothole_type harus berupa SERIOUSLY_DAMAGED atau LESS_DAMAGED";
            }

            if(!$this->validateNumeric($pothole_length)) {
                return "pothole_length harus berupa angka";
            }

            if(!$this->validateNumeric($pothole_width)) {
                return "pothole_width harus berupa angka";
            }

            if(!$this->validateNumeric($pothole_height)) {
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
            if($pothole_data_array[count($pothole_data_array) - 1] == '') {
                return "Format pothole_data tidak valid. Hapus separator '||' diakhir string";
            }

            $i = 1;
            foreach($pothole_data_array as $pothole_data) {
                // check contain colon or not
                if(!strpos($pothole_data, ':')) {
                    return "Object ke-". $i ." => Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
                }

                // if contains colon, explode it
                $pothole_data_array = explode(':', $pothole_data);

                // check if array length is 5
                if(count($pothole_data_array) != 5) {
                    return "Data pendeteksian lubang harus berupa string dengan format: pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height||pothole_object_number:pothole_type:pothole_length:pothole_width:pothole_height";
                }

                $pothole_object_number = $pothole_data_array[0];
                $pothole_type = $pothole_data_array[1];
                $pothole_length = $pothole_data_array[2];
                $pothole_width = $pothole_data_array[3];
                $pothole_height = $pothole_data_array[4];

                if(!$this->validateNumeric($pothole_object_number)) {
                    return "Object ke-". $i ." pothole_object_number harus berupa angka";
                }

                if(!$this->validatePotholeType($pothole_type)) {
                    return "Object ke-". $i ." pothole_type harus berupa SERIOUSLY_DAMAGED atau LESS_DAMAGED";
                }

                if(!$this->validateNumeric($pothole_length)) {
                    return "Object ke-". $i ." pothole_length harus berupa angka";
                }

                if(!$this->validateNumeric($pothole_width)) {
                    return "Object ke-". $i ." pothole_width harus berupa angka";
                }

                if(!$this->validateNumeric($pothole_height)) {
                    return "Object ke-". $i ." => pothole_height harus berupa angka";
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

    public function validateNumeric($pothole_object_number) {
        if(!is_numeric($pothole_object_number)) {
            return false;
        }
        return true;
    }

    public function validatePotholeType($pothole_type) {
        if($pothole_type != 'SERIOUSLY_DAMAGED' && $pothole_type != 'LESS_DAMAGED') {
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
        if($detection_latitude < -90 || $detection_latitude > 90) {
            return $this->sendError(2, "Nilai latitude tidak valid, harus berada diantara -90 dan 90");
        }

        $detection_longitude = $request->detection_longitude;
        if($detection_longitude < -180 || $detection_longitude > 180) {
            return $this->sendError(2, "Nilai longitude tidak valid, harus berada diantara -180 dan 180");
        }

        // => VALIDATE DETECTION IMAGE
        $detection_image_name = generateFiledCode('DETECTION_IMAGE');
        $detection_image_folder = 'detection_images';
        $detection_image_path = storeImage($request->detection_image, $detection_image_folder, $detection_image_name);
        if(!$detection_image_path) {
            return $this->sendError(2, "Gambar harus berbentuk base64 atau URL");
        }

        // => VALIDATE DETECTION TYPE
        $detection_type = $request->detection_type;
        if($detection_type != 'CAPTURE' && $detection_type != 'REALTIME') {
            return $this->sendError(2, "Tipe deteksi harus berupa CAPTURE atau REALTIME");
        }

        // => VALIDATE DETECTION ALGORITHM
        $detection_algorithm = $request->detection_algorithm;
        if($detection_algorithm != 'YOLOV8-DEPTHINFO' && $detection_algorithm != 'MASKRCNN-SONARROBOT') {
            return $this->sendError(2, "Algoritma pendeteksian harus berupa YOLOV8-DEPTHINFO atau MASKRCNN-SONARROBOT");
        }

        // => VALIDATE DETECTION BY (check if user_code exists)
        $detection_by = $request->detection_by;
        $check_user = DB::table('users')->where('user_code', $detection_by)->first();
        if(!$check_user) {
            return $this->sendError(2, "User tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $check_detection->update([
                'detection_latitude' => $detection_latitude,
                'detection_longitude' => $detection_longitude,
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
}
