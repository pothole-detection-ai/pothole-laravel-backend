<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{

    public function sendResponse($code, $message, $data = array())
    {
        return response()->json([
            'code' => $code,
            'success' => true,
            'message' => $message,
            'data' => $this->normalize_result($data),
        ], 200);
    }

    public function sendError($code, $status, $errorMessages = array())
    {
        $response = [
            'code' => $code,
            'success' => false,
            'message' => $status,
        ];

        $response['data'] = $errorMessages;

        return response()->json($response, 400);
    }


    public function errorAuth($code, $status, $errorMessages = array())
    {
        $response = [
            'code' => $code,
            'success' => false,
            'message' => $status,
        ];

        $response['data'] = $errorMessages;

        return response()->json($response, 403);
    }

    public function errorEmpty($code, $status, $errorMessages = array())
    {
        $response = [
            'code' => $code,
            'success' => false,
            'message' => $status,
        ];

        $response['data'] = $errorMessages;

        return response()->json($response, 401);
    }


    public function normalize_result($result)
    {

        $result = json_decode(json_encode($result), true);

        array_walk_recursive($result, function (&$value) {
            $value = !is_null($value) ? $value : "";
        });

        return $result;
    }

    public function noAuth()
    {
        $response = [
            'code' => 401,
            'success' => false,
            'message' => 'Unauthenticated',
        ];

        return response()->json($response, 401);
    }
}
