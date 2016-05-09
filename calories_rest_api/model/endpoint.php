<?php

class Endpoint
{
    const OK = 0;
    const ERROR_WRONG_METHOD = -1;
    const ERROR_WRONG_ENDPOINT = -2;
    const SERVER_ERROR = -3;
    
    const CODE_OK = 200;
    const CODE_CREATED = 201;
    
    const CODE_BAD_REQUEST = 400;
    const CODE_FORBIDDEN = 403;
    const CODE_NOT_FOUND = 404;
    const CODE_METHOD_NOT_ALLOWED = 405;
    const CODE_GONE = 410;
    
    const CODE_INTERNAL_ERROR = 500;
    
    const OPERATION_SUCCESSFUL = 0;
    const ERROR_WRONG_ARGUMENTS = 1;
    const ERROR_MISSING_PARAM = 2;
    const INTERNAL_DB_ERROR = 3;
    const AUTH_EXPIRED = 4;
    const AUTH_MISSING = 5;
    const AUTH_WRONG = 6;
    const USER_NOT_FOUND = 7;
    const FORBIDDEN_OPERATION = 8;
    const MISSING_PARAM = 9;
    const EXTRA_ARGS_IN_LINK = 10;
    const INTERNAL_MAILER_ERROR = 11;
    
    const REST_API_CODE = 'app_code';
    const HTTP_CODE = 'http_code';
    const REST_API_MESSAGE = 'message';
    
    static public function GetUserId()
    {
        if (isset($_COOKIE['user_id']))
        {
            return $_COOKIE['user_id'];
        }
        
        return null;
    }
    
    static public function GetUserAuthToken()
    {
        if (isset($_COOKIE['token']))
        {
            return $_COOKIE['token'];
        }
        
        return null;
    }
    
    static public function GetMissingParameter($mandatory_parameters, $request_data)
    {
        foreach ($mandatory_parameters as $param)
        {
            if (isset($request_data[$param]) == false)
            {
                return $param;
            }
        }
        return null;
    }
    
    public static function ReturnError($code = self::CODE_NOT_FOUND, $error_code = -1, $msg = '')
    {
        $error_data = array();
        if (strlen($msg) > 0)
        {
            $error_data[self::REST_API_MESSAGE] = $msg;
        }
        
        return static::PrepareResponse($error_data, $error_code, $code);
    }
    
    public static function RunEndpoint($method, $args)
    {       
        return $this->ReturnError();
    }
    
    public static function GetEmptyFunctionResult()
    {
        return array(self::HTTP_CODE => Endpoint::CODE_OK,
                     self::REST_API_CODE => self::OPERATION_SUCCESSFUL,
                     self::REST_API_MESSAGE => '');
    }
    
    public static function IsResultOK(&$result)
    {
        return $result[self::HTTP_CODE] == Endpoint::CODE_OK;
    }

    public static function PrepareResponse($data, $error_code = 0, $status = self::CODE_OK)
    {
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");
        
        http_response_code($status);
        $data[self::REST_API_CODE] = $error_code;
        
        return json_encode($data);
    }
}