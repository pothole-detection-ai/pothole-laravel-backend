<?php

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
