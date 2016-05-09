<?php

require_once 'database.php';


class User
{

    const ROLE_USER = 0;
    const ROLE_MODERATOR = 1;
    const ROLE_ADMIN = 2;

    const DB_TABLE_NAME = 'Users';
    
    const USER_OK = 0;

    static function Get($id)
    {
        $response = array( 'user' => null, 
                           'code' => self::USER_OK );
        $user = DB::GetSingle(self::DB_TABLE_NAME, array('where' => array('id' => (int)$id)));
        $response['user'] = $user;
        $response['code'] = DB::GetErrorCode();
        
        return $response;
    }
    
    static function GetAll($page, $amount)
    {
        $response = array( 'users' => null, 
                           'code' => self::USER_OK );
        $users = DB::GetAsArray(self::DB_TABLE_NAME, array('offset' => $page * $amount, 
                                                           'limit' => $amount));
        
        $response['users'] = $users;
        $response['code'] = DB::GetErrorCode();
        return $response;
    }
    
    static function GetByEmail($email)
    {
        $response = array( 'users' => null, 
                           'code' => self::USER_OK );
        $user = DB::GetSingle(self::DB_TABLE_NAME, array('where' => array('email' => $email)));
        
        $response['user'] = $user;
        $response['code'] = DB::GetErrorCode();
        return $response;
    }
    
    public static function Add($email, $name, $password_plain, $role)
    {
        $creation_time = time();
        return DB::Insert(self::DB_TABLE_NAME, array(
            'role' => $role,
            'email' => $email,
            'name' => $name,
            'password' => self::EncryptPassword($password_plain),
            'daily_calories' => 2000,
            'reg_date' => $creation_time,
            'auth_token' => self::GenerateAuthToken($creation_time, $email),
            'auth_expiry_date' => self::GenerateAuthExpiryDate($creation_time)
        ));
    }
    
    public static function Delete($id)
    {
        DB::Delete(self::DB_TABLE_NAME, array('where' => array('id' => $id)));
        return DB::GetErrorCode() == 0;
    }
    
    public static function Update($id, $data)
    {
        DB::Update(self::DB_TABLE_NAME, $data, array('where' => array('id' => $id)));
        return DB::GetErrorCode() == 0;
    }
    
    public static function UpdateAuth($id, $auth_token, $exp_time)
    {
        DB::Update(self::DB_TABLE_NAME,
                   array('auth_token' => $auth_token, 
                         'auth_expiry_date' => $exp_time),
                   array('where' => array('id' => $id)));

    }
    
    public static function GetRoleName($role)
    {
        switch($role)
        {
            case self::ROLE_ADMIN:
                return 'administrator';
            case self::ROLE_MODERATOR:
                return 'moderator';
            default:
                return 'user';
        }
    }
    
    
    // since we do not want to send everything about the user
    public static function GetRequestedUserData($user)
    {
        $data = array(
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => self::GetRoleName($user['role']),
            'daily_calories' => $user['daily_calories'],
            'reg_date' => date('d.m.Y', $user['reg_date']),
        );
        
        return $data;
    }
    
    public static function GenerateAuthToken($time, $email)
    {
        return md5($time . $email);
    }

    public static function GenerateAuthExpiryDate($time)
    {
        return time() + 24*60*60;
    }
    
    public static function EncryptPassword($pw)
    {
        //todo(vslavs) - make something safer
        return md5($pw);
    }

}

?>