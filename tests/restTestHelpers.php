<?php

class TestHelpers
{
    public static function GetApiLink()
    {
        // todo(vslav) - move to config
        return 'http://localhost/calories/api';
    }
    
    public static function GetAuthMail()
    {
        // todo(vslav) - move to config
        return 'test@example.com';
    }
    
    public static function GetTestCookieFile()
    {
        return dirname(__FILE__) . '/cookie.txt';
    }

    public static function ResetCookies()
    {
        if (file_exists(self::GetTestCookieFile()))
        {
            unlink(self::GetTestCookieFile());
        }
    }

    public static function SendRequest($endpoint, $method, $data, $use_cookies = false)
    {
        $ch = curl_init(self::GetApiLink() . $endpoint);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if ($use_cookies)
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, self::GetTestCookieFile());
            curl_setopt($ch, CURLOPT_COOKIEFILE, self::GetTestCookieFile());
        }
            
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        return array('response' => $response, 'code' => $httpcode);
    }
}