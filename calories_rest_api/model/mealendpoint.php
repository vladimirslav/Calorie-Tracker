<?php

require_once 'endpoint.php';
require_once 'database.php';

// depends on user logins:
require_once 'userendpoint.php';

require_once 'user.php';
require_once 'meal.php';

/* == Endpoints:
Add
Get
*/

class MealEndpoint extends Endpoint
{
    const OPERATION_SUCCESSFUL = 0;
    
    const CALORIE_COUNT_WRONG = -200;
    const COMMENT_TOO_LONG = -201;
    const BAD_DATE = -202;
    const BAD_TIME = -204;
    const BAD_AMOUNT = -205;
    const MEAL_NOT_FOUND = -206;
    const TOO_MUCH_DATA = -207;
    
    public static function RunEndpoint($method, $args)
    {       
        // all meals require authenticated user:
        $request_result = UserEndpoint::GetCurrentUser();
        if ($request_result['user'] != null)
        {
            $user = $request_result['user'];
            if ($method == 'POST')
            {
                if (strcmp(strtolower($args[0]), 'meals') === 0)
                {
                    if (count($args) == 2 && is_numeric($args[1]))
                    {
                        $param = self::GetMissingParameter(array('date', 'time', 'calories'), $_POST);
                        if ($param === null)
                        {
                            return self::Add($user,
                                             $args[1],
                                             isset($_POST['text']) ? $_POST['text'] : null,
                                             $_POST['date'],
                                             $_POST['time'],
                                             (int)$_POST['calories']);
                        }
                        else
                        {
                            return self::ReturnError(Endpoint::CODE_BAD_REQUEST, self::MISSING_PARAM, $param . ' is missing from the request');
                        }
                    }
                    else
                    {
                        // extra args are given
                        return self::ReturnError(Endpoint::CODE_BAD_REQUEST,
                                                 self::EXTRA_ARGS_IN_LINK,
                                                 'Meal Add Request should not have any extra parameters in its URL - simply /meal/ownerId i.e. /meal/0');
                    }
                }
            }
            else if ($method == 'GET')
            {
                if (count($args) == 2 && is_numeric($args[1]))
                {
                    return self::Get($user, $args[1]);
                }
                else if (count($args) > 1 && strcmp(strtolower($args[1]), 'list') === 0)
                {
                    if (count($args) == 3 && is_numeric($args[2]))
                    {
                        return self::ListAll($user, $args[2], $_GET);
                    }
                    else
                    {                
                        $request_result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                        $request_result[self::REST_API_CODE] = self::EXTRA_ARGS_IN_LINK;
                        $request_result[self::REST_API_MESSAGE] = 'list/ requires a numeric owner id as a next argument - i.e. meals/list/0 (nothing after that)';
                    }
                }
                if (count($args) == 3 && strcmp(strtolower($args[1]), 'filter') === 0 && is_numeric($args[2]))
                {
                    return self::ListByCriteria($user, $args[2], $_GET);
                }
                else
                {
                    $request_result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                    $request_result[self::REST_API_CODE] = self::EXTRA_ARGS_IN_LINK;
                    $request_result[self::REST_API_MESSAGE] = 'either ID should be passed as a second argument - i.e. /meals/0'
                                                              . '(nothing after that) or /list/{user_id} or /filter/{user_id}';
                }
            }
            else if ($method == 'DELETE')
            {
                if (strcmp(strtolower($args[0]), 'meals') === 0 && count($args) == 2)
                {
                    return self::Delete($user, $args[1]);
                }
                else
                {
                    $request_result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                    $request_result[self::REST_API_CODE] = self::EXTRA_ARGS_IN_LINK;
                    $request_result[self::REST_API_MESSAGE] = 'ID should be passed as a second argument (nothing after that) - i.e. /meals/0';
                }
            }
            else if ($method == 'PUT')
            {
                if (strcmp(strtolower($args[0]), 'meals') === 0 && count($args) == 2)
                {
                    $params_to_update = array();
                    parse_str(file_get_contents("php://input"), $params_to_update);
                    return self::Update($user, $args[1], $params_to_update);
                }
                else
                {
                    $request_result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                    $request_result[self::REST_API_CODE] = self::EXTRA_ARGS_IN_LINK;
                    $request_result[self::REST_API_MESSAGE] = 'ID should be passed as a second argument (nothing after that) - i.e. /meals/0';
                }
            }
        }
        
        // if we reach this stage - this is clearly due to an error
        return self::ReturnError($request_result[self::HTTP_CODE],
                                 $request_result[self::REST_API_CODE],
                                 $request_result[self::REST_API_MESSAGE]);
    }
        
    public static function Add($by, $owner, $text, $date, $time, $calories)
    {
        $result = self::GetEmptyFunctionResult();
        $date_timestamp = 0;
        $time_in_seconds = 0;
        
        if ($by['id'] != $owner && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You are not allowed to add meals for other users';
        }
        
        if (self::IsResultOK($result))
        {
            self::ValidateText($text, $result);
        }
        
        if (self::IsResultOK($result))
        {
            $date_timestamp = self::ValidateDate($date, $result);
        }
        
        if (self::IsResultOK($result))
        {
            $time_in_seconds = self::ValidateTime($time, $result);
        }
        
        if (self::IsResultOK($result))
        {
            self::ValidateCalories($calories, $result);
        }
        
        $success = false;
        $meal = null;

        if (self::IsResultOK($result))
        {
            $query = Meal::Add($owner, $text, $date_timestamp, $time_in_seconds, $calories);
            if ($query['code'] == 0)
            {
                $query = Meal::Get($query['id']);
                if ($query['code'] == 0)
                {
                    $meal = $query['meal'];
                    $success = true;
                }
            }
        }
        
        if (self::IsResultOK($result) && $success == false)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
            $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
            $result[self::REST_API_MESSAGE] = 'Request failed to add new data('. DB::GetErrorCode() . ')';
        }
        
        if (self::IsResultOK($result))
        {
            return self::PrepareResponse(array('message' => 'Meal Added', 
                                               'meal' => Meal::GetRequestedMealData($meal)));
        }
        else
        {
            return self::ReturnError($result[self::HTTP_CODE],
                                     $result[self::REST_API_CODE],
                                     $result[self::REST_API_MESSAGE]);
        }
    }

    public static function Update($by, $id, $params)
    {
        $result = self::RetrieveMeal($id);
        if (isset($result['meal']))
        {
            $meal = $result['meal'];
            
            if ($meal['owner'] != $by['id'] && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
                $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
                $result[self::REST_API_MESSAGE] = 'You do not have permission to edit other user meals';
            }
            
            $params_changed = array();
            
            if (self::IsResultOK($result) && isset($params['date']))
            {
                $meal_date = self::ValidateDate($params['date'], $result);
                if ($result[self::HTTP_CODE] == Endpoint::CODE_OK)
                {
                    $params_changed['meal_date'] = $meal_date;
                    $meal['meal_date'] = $meal_date;
                }
            }
            
            if (self::IsResultOK($result) && isset($params['time']))
            {
                $meal_time = self::ValidateTime($params['time'], $result);
                if ($result[self::HTTP_CODE] == Endpoint::CODE_OK)
                {
                    $params_changed['meal_time'] = $meal_time;
                    $meal['meal_time'] = $meal_time;
                }
            }
            
            if (self::IsResultOK($result) && isset($params['calories']))
            {
                self::ValidateCalories((int)($params['calories']), $result);
                if ($result[self::HTTP_CODE] == Endpoint::CODE_OK)
                {
                    $params_changed['calories'] = (int)($params['calories']);
                    $meal['calories'] = (int)($params['calories']);
                }
            }
            
            if (self::IsResultOK($result) && isset($params['text']))
            {
                self::ValidateText($params['text'], $result);
                if ($result[self::HTTP_CODE] == Endpoint::CODE_OK)
                {
                    $params_changed['text'] = $params['text'];
                    $meal['text'] = $params['text'];
                }
            }
            
            if (self::IsResultOK($result))
            {
                if (empty($params_changed))
                {
                    $params_meal = array('date', 'time', 'calories', 'text');
                    $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                    $result[self::REST_API_CODE] = self::MISSING_PARAM;
                    $result[self::REST_API_MESSAGE] = 'No valid parameters given. Valid parameters are ' . implode(',', $params_meal);
                }
            }
            
            if (self::IsResultOK($result))
            {
                if (Meal::Update($id, $params_changed) == false)
                {
                    $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                    $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                    $result[self::REST_API_MESSAGE] = 'Error when trying to update meal';
                }
                else
                {
                    return self::PrepareResponse(array('message' => 'Updated Successfully', 
                                                       'meal' => Meal::GetRequestedMealData($meal)));
                }
            }
        }
        
        
        return self::ReturnError($result[self::HTTP_CODE],
                                 $result[self::REST_API_CODE],
                                 $result[self::REST_API_MESSAGE]);
    }
    
    private static function RetrieveMeal($id)
    {
        $result = self::GetEmptyFunctionResult();
        $meal_query = Meal::Get($id);
        $meal = $meal_query['meal'];
        if ($meal == null)
        {
            if ($meal_query['code'] != 0)
            {
                // db error
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request failed to get meal ('. DB::GetErrorCode() . ')';
            }
            else
            {
                // meal not found
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::MEAL_NOT_FOUND;
                $result[self::REST_API_MESSAGE] = 'Request failed to get meal ('. DB::GetErrorCode() . ')';
            }
        }
        else
        {
            $result['meal'] = $meal;
        }
        
        return $result;
    }

    public static function Get($by, $id)
    {
        $result = self::RetrieveMeal($id);
        
        if (isset($result['meal']))
        {
            $meal = $result['meal'];
            // trying to access another user with your own is a no-no, unless you are moderator or admin
            if ($by['id'] != $meal['owner'] && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
                $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
                $result[self::REST_API_MESSAGE] = 'You do not have permission to view meals of other users';
            }
            else
            {
                return self::PrepareResponse(array('meal' => Meal::GetRequestedMealData($meal)));
            }
        }
        
        return self::ReturnError($result[self::HTTP_CODE],
                                 $result[self::REST_API_CODE],
                                 $result[self::REST_API_MESSAGE]);
    }
    
    
    public static function Delete($by, $id)
    {
        $result = self::RetrieveMeal($id);
        
        if (isset($result['meal']))
        {
            $meal = $result['meal'];
            // trying to access another user with your own is a no-no, unless you are moderator or admin
            if ($id != $meal['owner'] && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
                $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
                $result[self::REST_API_MESSAGE] = 'You do not have permission to delete meals of others';
            }
            
            if (Meal::Delete($id))
            {
                return self::PrepareResponse(array('message' => 'Meal Deleted'));
            }
        }
        
        return self::ReturnError($result[self::HTTP_CODE],
                                 $result[self::REST_API_CODE],
                                 $result[self::REST_API_MESSAGE]);
    }
    
    public static function ListByCriteria($by, $id, $params)
    {
        $result = self::GetEmptyFunctionResult();
        $start_date_timestamp = 0;
        $end_date_timestamp = 0;
        
        $start_time_timestamp = 0;
        $end_time_timestamp = 0;
        
        $request_params = array();
        if ($id != $by['id'] && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You do not have permission to edit other user meals';
        }

        $param = self::GetMissingParameter(array('startdate', 'enddate'), $params);
        if ($param !== null)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::MISSING_PARAM;
            $result[self::REST_API_MESSAGE] = $param . ' is missing from the request';
        }
        
        if (self::IsResultOK($result))
        {
            $start_date_timestamp = self::ValidateDate($params['startdate'], $result);
        }
        
        if (self::IsResultOK($result))
        {
            $end_date_timestamp = self::ValidateDate($params['enddate'], $result);
        }

        if (self::IsResultOK($result) && $start_date_timestamp > $end_date_timestamp)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::BAD_DATE;
            $result[self::REST_API_MESSAGE] = 'End date must not be before the start date';
        }
        
        if (self::IsResultOK($result) && isset($params['starttime']))
        {
            $start_time_timestamp = self::ValidateTime($params['starttime'], $result);
        }
        
        if (self::IsResultOK($result) && isset($params['endtime']))
        {
            $end_time_timestamp = self::ValidateTime($params['endtime'], $result);
        }
        
        // if both time params are set - do a check
        if (isset($params['starttime']) && isset($params['endtime']))
        {
            if (self::IsResultOK($result) && $start_time_timestamp > $end_time_timestamp)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::BAD_TIME;
                $result[self::REST_API_MESSAGE] = 'End time must not be lesser than start time';
            }
        }
        
        if (self::IsResultOK($result))
        {
            $requested_days = ((int)$params['enddate'] - (int)$params['startdate']) / Meal::SECONDS_IN_A_DAY;
            if ($requested_days > Meal::MAX_DAYS_IN_REQUEST)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::TOO_MUCH_DATA;
                $result[self::REST_API_MESSAGE] = 'Too much days requested';
            }
        }
        
        $meals = array();
        if (self::IsResultOK($result))
        {
            $meal_query = Meal::GetMealsByDateTime($id,
                                                   $start_date_timestamp,
                                                   $end_date_timestamp,
                                                   isset($params['starttime']) ? $start_time_timestamp : -1,
                                                   isset($params['endtime']) ? $end_time_timestamp : -1);
            if ($meal_query['code'] != 0)
            {
                // db error
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request failed to get meal ('. DB::GetErrorCode() . ' )';
            }
            else
            {
                $meals = $meal_query['meals'];
            }
        }
        
        if (self::IsResultOK($result))
        {
            $meals_prepared = array();
            for ($i = 0; $i < count($meals); $i++)
            {
                $meals_prepared[] = Meal::GetRequestedMealData($meals[$i]);
            }
            
            return self::PrepareResponse(array('meals' => $meals_prepared, 
                                               'from' => $params['startdate'],
                                               'to' => $params['enddate']));
        }
        
        return self::ReturnError($result[self::HTTP_CODE],
                                 $result[self::REST_API_CODE],
                                 $result[self::REST_API_MESSAGE]);
    }
    
    function ListAll($by, $owner, $params)
    {

        $result = self::GetEmptyFunctionResult();
        if ($by['id'] != $owner && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You do not have permission to access list with meals for this user';
        }

        $amount = 20;
        if (self::IsResultOK($result))
        {
            if (isset($params['amount']))
            {
                $amount_temp = (int)($params['amount']);
                if ($amount_temp > API_MAX_MEALS_REQUESTED || $amount_temp <= 0)
                {
                    $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                    $result[self::REST_API_CODE] = self::BAD_AMOUNT;
                    $result[self::REST_API_MESSAGE] = 'Amount should be greater than zero and lesser than ' . (API_MAX_MEALS_REQUESTED + 1);
                }
                else
                {
                    $amount = $amount_temp;
                }
            }
        }

        $page = 0; // offset
        if (self::IsResultOK($result) && isset($params['page']))
        {
            if ((int)$params['page'] < 0)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::BAD_PAGE;
                $result[self::REST_API_MESSAGE] = 'Page requested must not be lesser than zero';
            }
            else
            {
                $page = (int)($params['page']);
            }
        }

        $meals = array();
        if (self::IsResultOK($result))
        {
            $meal_query = Meal::GetMealsByOwner($owner,
                                                $page,
                                                $amount);
            if ($meal_query['code'] != 0)
            {
                // db error
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request failed to get meals ('. DB::GetErrorCode() . ' )';
            }
            else
            {
                $meals = $meal_query['meals'];
            }
        }
        
        if (self::IsResultOK($result))
        {
            $meals_prepared = array();
            for ($i = 0; $i < count($meals); $i++)
            {
                $meals_prepared[] = Meal::GetRequestedMealData($meals[$i]);
            }
            
            return self::PrepareResponse(array('meals' => $meals_prepared, 
                                               'page' => $page,
                                               'amount' => count($meals_prepared),
                                               'last' => (count($meals_prepared) < $amount) ? 'true' : 'false'));
        }
        
        return self::ReturnError($result[self::HTTP_CODE],
                                 $result[self::REST_API_CODE],
                                 $result[self::REST_API_MESSAGE]);
    }

    
    public static function ValidateCalories($calories, &$result)
    {
        if ((int)$calories < 1 || (int)$calories > 5000)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::CALORIE_COUNT_WRONG;
            $result[self::REST_API_MESSAGE] = 'Each meal can be between 1 and 5000 calories';
        }
    }
    
    public static function ValidateText($text, &$result)
    {
        if ($text != null && strlen($text) > 254)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::COMMENT_TOO_LONG;
            $result[self::REST_API_MESSAGE] = 'Comment must not be longer than 254 characters';
        }
    }
    
    public static function ValidateDate($date, &$result)
    {
        $errors = array();
        $errormsg = null;
        $date_timestamp = null;
        
        if (preg_match('/^([0-3][0-9])\.([0-1][0-9])\.([0-9]{4})$/', $date, $matches))
        {
            $check_date = date_create_from_format("d.m.Y", $date);
            $errors = DateTime::getLastErrors();
        }
        else
        {
            $errors['error_count'] = 1;
            $errors['errors'] = array('Date does not match dd.mm.YYYY format - check if days / months has leading zeroes');
        }
        
        if ($errors['error_count'] > 0)
        {
            $errormsg = 'Improper date format (should be valid dd.mm.YYYY). Error ' . array_pop($errors['errors']);
        }
        else if ($errors['warning_count'] > 0)
        {
            $errormsg = 'Improper date (should be valid dd.mm.YYYY). Error: ' . array_pop($errors['warnings']);
        }
        else if (date_timestamp_get($check_date)  > time() + 86400)
        {
            $errormsg = 'Date must not exceed the next days date (' . date('d.m.Y', time() + 86400) . ')';
        }
        else
        {
            date_modify($check_date, "today");
            $date_timestamp = date_timestamp_get($check_date);
        }
        
        if ($errormsg != null)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::BAD_TIME;
            $result[self::REST_API_MESSAGE] = $errormsg;
        }
        
        return $date_timestamp;
    }
 
    public static function ValidateTime($time, &$result)
    {
        $time_in_seconds = null;
        if (preg_match("/^(2[0-3]|[01][0-9]):([0-5][0-9])$/", $time) != 1)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::BAD_TIME;
            $result[self::REST_API_MESSAGE] = 'Time should be given in 24h format with leading zeroes: i.e. 03:09)';
        }
        else
        {
            // 0 pos - hours
            // 1 pos - minutes
            $time_exploded = explode(":", $time);
            $time_in_seconds = $time_exploded[0] * 3600 + $time_exploded[1] * 60;
        }
        
        return $time_in_seconds;
    }
}