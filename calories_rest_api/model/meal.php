<?php

require_once 'database.php';


class Meal
{
    const DB_TABLE_NAME = 'Meals';

    const MEAL_OK = 0;
    const SECONDS_IN_A_DAY = 86400;
    const MAX_DAYS_IN_REQUEST = 62;
    
    static function Get($id)
    {
        $response = array( 'meal' => null, 
                           'code' => self::MEAL_OK );
        $meal = DB::GetSingle(self::DB_TABLE_NAME, array('where' => array('id' => (int)$id)));
        $response['meal'] = $meal;
        $response['code'] = DB::GetErrorCode();
        
        return $response;
    }
    
    public static function GetMealTime($timestamp)
    {
        return $timestamp % self::SECONDS_IN_A_DAY;
    }
    
    public static function GetMealDate($timestamp)
    {
        // just get the date from substracting time
        return $timestamp - self::GetMealTime($timestamp);
    }
    
    public static function Add($owner,
                               $text,
                               $meal_date_timestamp,
                               $meal_time_in_seconds,
                               $calories)
    {
        $id = DB::Insert(self::DB_TABLE_NAME, array(
            'owner' => $owner,
            'text' => $text,
            'meal_date' => $meal_date_timestamp,
            'meal_time' => $meal_time_in_seconds,
            'calories' => $calories,
        ));
        
        return array('code' => 0, 'id' => $id);
    }
    
    public static function DeleteByOwner($owner)
    {
        return DB::Delete(self::DB_TABLE_NAME, array('where' => array('owner' => $owner)));
        return DB::GetErrorCode() == 0;
    }
    
    public static function Delete($id)
    {
        return DB::Delete(self::DB_TABLE_NAME, array('where' => array('id' => $id)));
        return DB::GetErrorCode() == 0;
    }
    
    public static function GetRequestedMealData($meal)
    {
        // free to return all meal data
        // but let's not risk it
        // in case we add something to meal that we don't want to return
        $data = array(
            'id' => $meal['id'],
            'text' => $meal['text'],
            'date' => date('d.m.Y', $meal['meal_date']),
            'time' => date('H:i', $meal['meal_time'] - 3600), // 0 timestamp gives 1:00, that's why substract time
            'calories' => $meal['calories'],
        );
        
        return $data;
    }
    
    public static function Update($id, $data)
    {
        DB::Update(self::DB_TABLE_NAME, $data, array('where' => array('id' => $id)));
        return DB::GetErrorCode() == 0;
    }
    
    public static function GetMealsByDateTime($owner, $start_date, $end_date, $start_time, $end_time)
    {
        $params = array('_cond1' => array('key' => 'meal_date', 'val' => (int)$start_date, 'sign' => '>='),
                        '_cond2' => array('key' => 'meal_date', 'val' => (int)$end_date, 'sign' => '<='),
                        'owner' => $owner);
        if ($start_time != -1)
        {
            $params['_cond3'] = array('key' => 'meal_time', 'val' => (int)($start_time), 'sign' => '>=');
        }
        
        if ($end_time != -1)
        {
            $params['_cond4'] = array('key' => 'meal_time', 'val' => (int)($end_time), 'sign' => '<=');
        }
        $response = array( 'meals' => null, 
                           'code' => self::MEAL_OK );
                           
        $meals = DB::GetAsArray(self::DB_TABLE_NAME, array('where' => $params));
        
        $response['meals'] = $meals;
        $response['code'] = DB::GetErrorCode();
        
        return $response;
    }
    
    public static function GetMealsByOwner($owner, $page, $amount)
    {
        $params = array('owner' => $owner);
        
        $response = array( 'meals' => null, 
                           'code' => self::MEAL_OK );
                           
        $meals = DB::GetAsArray(self::DB_TABLE_NAME, array('where' => $params,
                                                           'offset' => $page * $amount,
                                                           'limit' => $amount));
        
        $response['meals'] = $meals;
        $response['code'] = DB::GetErrorCode();
        
        return $response;
    }

}

?>