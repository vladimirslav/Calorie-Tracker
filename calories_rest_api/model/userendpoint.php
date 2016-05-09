<?php

require_once 'endpoint.php';
require_once 'database.php';
require_once 'user.php';
require_once 'meal.php'; // need when we delete a user (delete his meals with him)

/* == Endpoints:
Add
Auth
*/

class UserEndpoint extends Endpoint
{
    const MISSING_EMAIL = -100;
    const NAME_TOO_SHORT = -101;
    const BAD_PASSWORD = -102;
    const EMAIL_EXISTS = -103;
    const BAD_PAGE = -104;
    const BAD_AMOUNT = -105;
        
    const USER_ID_KEY = 'user_id';
    const USER_AUTH_TOKEN = 'auth_token';
        
    public static function RunEndpoint($method, $args)
    {
        if($method == 'POST')
        {
            if (strcmp(strtolower($args[0]), 'users') === 0)
            {
                if (count($args) == 1)
                {
                    $param = self::GetMissingParameter(array('email', 'name', 'password'), $_POST);
                    if ($param === null)
                    {
                        return self::Add($_POST['email'], $_POST['name'], $_POST['password']);
                    }
                    else
                    {
                        return self::ReturnError(Endpoint::CODE_BAD_REQUEST, self::MISSING_PARAM, $param . ' is missing from the request');
                    }
                }
                else if (count($args) == 2 && strcmp(strtolower($args[1]), 'auth') === 0)
                {
                    $param = self::GetMissingParameter(array('email', 'password'), $_POST);
                    if ($param === null)
                    {
                        return self::Auth($_POST['email'], $_POST['password']);
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
                                             'User Add Request should not have any extra parameters in its URL');
                }
            }
        }
        else if ($method == 'PUT' && count($args) == 2 && 
                 strcmp(strtolower($args[0]), 'users') === 0 &&
                 strcmp(strtolower($args[1]), 'reset') === 0)
        {
            $parsed_params = array();
            parse_str(file_get_contents("php://input"), $parsed_params);
            return self::Reset($parsed_params);
        }
        else
        {
            $usercheck = self::GetCurrentUser();
            
            if ($usercheck['user'] != null)
            {
                $user = $usercheck['user'];
                // we have user - good to go
                if ($method == 'GET')
                {
                    if (strcmp(strtolower($args[0]), 'users') === 0)
                    {
                        if (count($args) > 1 && strcmp(strtolower($args[1]), 'list') === 0)
                        {
                            if (count($args) == 4)
                            {
                                return self::ListAll($user, array('amount' => $args[2], 'page' => $args[3]));
                            }
                            else
                            {
                                // extra args are given
                                return self::ReturnError(Endpoint::CODE_BAD_REQUEST,
                                                         self::EXTRA_ARGS_IN_LINK,
                                                         'User list should have the following format users/list/amount/page, i.e. users/list/20/0');
                            }
                        }
                        if (count($args) == 2)
                        {
                            return self::Get($user, $args[1]);
                        }
                        else
                        {
                            // extra args are given
                            return self::ReturnError(Endpoint::CODE_BAD_REQUEST,
                                                     self::EXTRA_ARGS_IN_LINK,
                                                     'ID should be passed as a second argument (nothing after that) - i.e. /users/0');
                        }
                    }
                }
                else if ($method == 'DELETE')
                {
                    if (strcmp(strtolower($args[0]), 'users') === 0 && count($args) == 2)
                    {
                        return self::Delete($user, $args[1]);
                    }
                    else
                    {
                        // extra args are given
                        return self::ReturnError(Endpoint::CODE_BAD_REQUEST,
                                                 self::EXTRA_ARGS_IN_LINK,
                                                 'ID should be passed as a second argument (nothing after that) - i.e. /users/0');
                    }
                }
                else if ($method == 'PUT')
                {
                    if (strcmp(strtolower($args[0]), 'users') === 0)
                    {
                        if (count($args) == 2)
                        {                     
                            $parsed_params = array();
                            parse_str(file_get_contents("php://input"), $parsed_params);
                            return self::Update($user, $args[1], $parsed_params);
                        }
                        else
                        {
                            // extra args are given
                            return self::ReturnError(Endpoint::CODE_BAD_REQUEST,
                                                     self::EXTRA_ARGS_IN_LINK,
                                                     'ID should be passed as a second argument (nothing after that) - i.e. /users/0 or /users/reset');
                        }
                    }
                }
            }
            else
            {
                // extra args are given
                return self::ReturnError($usercheck[self::HTTP_CODE],
                                         $usercheck[self::REST_API_CODE],
                                         $usercheck[self::REST_API_MESSAGE]);
            }
        }
        
        return self::ReturnError(Endpoint::CODE_NOT_FOUND);
    }
    
    private static function RetrieveUser($id)
    {
        $result = self::GetEmptyFunctionResult();
        $user_query = User::Get($id);
        $user = $user_query['user'];
        if ($user == null)
        {
            if ($user_query['code'] != 0)
            {
                // db error
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Error when requesting user record (' . DB::GetErrorCode() . ')';
            }
            else
            {
                // user not found
                $result[self::HTTP_CODE] = Endpoint::CODE_NOT_FOUND;
                $result[self::REST_API_CODE] = self::USER_NOT_FOUND;
                $result[self::REST_API_MESSAGE] = 'Requested user has not been found';
            }
        }
        else
        {
            $result['user'] = $user;
        }
        
        return $result;
    }
    
    /* do all the checks and get the user if we can */
    public static function GetCurrentUser()
    {
        $result = array('user' => null,
                        self::HTTP_CODE => Endpoint::CODE_OK,
                        self::REST_API_CODE => self::OPERATION_SUCCESSFUL,
                        self::REST_API_MESSAGE => '');
        
        if (self::HasValidAuthData() == false)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::AUTH_MISSING;
            $result[self::REST_API_MESSAGE] = 'No Valid auth Information has been found';
        }
        
        $user = null;
        if ($result[self::HTTP_CODE] == Endpoint::CODE_OK)
        {
            $result = self::RetrieveUser($_COOKIE[self::USER_ID_KEY]);
            $user = $result['user'];
        }
        
        if ($result[self::HTTP_CODE] == Endpoint::CODE_OK)
        {
            if ($user['auth_expiry_date'] < time())
            {
                self::LogoutUser();
                $result[self::HTTP_CODE] = Endpoint::CODE_GONE;
                $result[self::REST_API_CODE] = self::AUTH_EXPIRED;
                $result[self::REST_API_MESSAGE] = 'Authentication token expired. Please authenticate again';
            }
        }
        
        if ($result[self::HTTP_CODE] == Endpoint::CODE_OK)
        {
            if (strcmp($user['auth_token'], $_COOKIE[self::USER_AUTH_TOKEN]) !== 0)
            {
                self::LogoutUser();
                $result[self::HTTP_CODE] = Endpoint::CODE_GONE;
                $result[self::REST_API_CODE] = self::AUTH_WRONG;
                $result[self::REST_API_MESSAGE] = 'Wrong authentication credentials. Try to log in again' . $_COOKIE[self::USER_AUTH_TOKEN] . ' vs ' . $user['auth_token'];
            }
            else
            {
                $result['user'] = $user;
            }
        }
        
        return $result;
    }
    
    public static function Logout()
    {
        setcookie(self::USER_AUTH_TOKEN, null);
        setcookie(self::USER_ID_KEY, null);
        return self::PrepareResponse(array('message' => 'Logged Out'));
    }
    
    public static function HasValidAuthData()
    {
        return isset($_COOKIE[self::USER_ID_KEY]) && isset($_COOKIE[self::USER_AUTH_TOKEN]);
    }
    
    private static function LogoutUser()
    {
        setcookie(self::USER_AUTH_TOKEN, null);
        setcookie(self::USER_ID_KEY, null);
    }
        
    public static function Add($email, $name, $password)
    {
        $result = self::GetEmptyFunctionResult();
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL) == false)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::MISSING_EMAIL;
            $result[self::REST_API_MESSAGE] = $email . ' is not a valid email';
        }
        
        if (self::IsResultOK($result) && strlen($name) < 2)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::NAME_TOO_SHORT;
            $result[self::REST_API_MESSAGE] = 'Name must be at least 2 character long';
        }
        
        if (self::IsResultOK($result) && strlen($password) < 6)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::BAD_PASSWORD;
            $result[self::REST_API_MESSAGE] = 'Password must be at least 6 character long';
        }
        
        if (self::IsResultOK($result))
        {
            // check if we already have such an email
            $res = DB::RawQuery("SELECT 1 FROM `" . User::DB_TABLE_NAME . "` WHERE `email` = '"  . DB::Escape($email) . "' LIMIT 1");
            if ($res != null && (int)(mysqli_num_rows($res)) === 1)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::EMAIL_EXISTS;
                $result[self::REST_API_MESSAGE] = 'Given email already exists in the database';
            }
            
            if (self::IsResultOK($result) && DB::GetErrorCode())
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request Failed When Checking If Email already exists('. DB::GetErrorCode() . ')';
            }
        }
        
        if (self::IsResultOK($result))
        {
            User::Add($email, $name, $password, User::ROLE_USER);
        
            if (DB::GetErrorCode())
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request failed to add new data('. DB::GetErrorCode() . ')';
            }
        }
        
        if (self::IsResultOK($result))
        {
            return self::PrepareResponse(array('message' => 'User Created'));
        }
        else
        {
            return self::ReturnError($result[self::HTTP_CODE],
                                     $result[self::REST_API_CODE],
                                     $result[self::REST_API_MESSAGE]);
        }
    }

    function Update($by, $id, $params)
    {
        $result = self::GetEmptyFunctionResult();
        
        $params_user = array('name', 'daily_calories', 'role', 'password');
        // trying to access another user with your own is a no-no, unless you are a moderator or an admin
        if ($id != $by['id'] && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You do not have permission to edit other user info';
        }
        
        if (self::IsResultOK($result) && isset($params['role']) && $by['role'] != User::ROLE_ADMIN)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You do not have permission to edit other user access role';
        }
        
        if (self::IsResultOK($result) && isset($params['password']))
        {
            if (strlen($params['password']) < 6)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::BAD_PASSWORD;
                $result[self::REST_API_MESSAGE] = 'Password must be at least 6 character long';
            }
            
            if (self::IsResultOK($result))
            {
                if ($by['id'] != $id && $by['role'] != User::ROLE_ADMIN)
                {
                    $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
                    $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
                    $result[self::REST_API_MESSAGE] = 'You do not have permission to edit other user passwords';
                }
                else
                {
                    $params['password'] = User::EncryptPassword($params['password']);
                }
            }
        }
        
        $success = false;
        $user = null;
        if (self::IsResultOK($result))
        {
            $result = self::RetrieveUser($id);
            $user = $result['user'];
        }
        
        if (self::IsResultOK($result) && isset($params['role']))
        {
            $roles = array(
                'administrator' => User::ROLE_ADMIN,
                'moderator' => User::ROLE_MODERATOR,
                'user' => User::ROLE_USER
            );
            
            $rolename = strtolower($params['role']);
            
            if (isset($roles[$rolename]))
            {
                $params['role'] = $roles[$rolename];
                $user['role'] = $roles[$rolename];
            }
            else
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::ERROR_WRONG_ARGUMENTS;
                $result[self::REST_API_MESSAGE] = 'Role parameter value can only be ' . implode(',', array_keys($roles));
            }
        }
        
        if (self::IsResultOK($result) && isset($params['name']))
        {
            if (strlen($params['name']) == 0 || strlen($params['name']) > 255)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::ERROR_WRONG_ARGUMENTS;
                $result[self::REST_API_MESSAGE] = 'User Name Should Be Between 1 and 255 symbols';
            }
            else
            {
                $user['name'] = $params['name'];
            }
        }
        
        if (self::IsResultOK($result) && isset($params['daily_calories']))
        {
            if ((int)$params['daily_calories'] < 500 || (int)$params['daily_calories'] > 12000)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::ERROR_WRONG_ARGUMENTS;
                $result[self::REST_API_MESSAGE] = 'Daily calories should be an integer within range of 500...12000';
            }
            else
            {
                $user['daily_calories'] = (int)$params['daily_calories'];
            }
        }
        
        // screening
        $params_changed = array();
        if (self::IsResultOK($result))
        {
            foreach ($params_user as $param)
            {
                if (isset($params[$param]))
                {
                    $params_changed[$param] = $params[$param];
                }
            }
            
            if (empty($params_changed))
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                $result[self::REST_API_CODE] = self::MISSING_PARAM;
                $result[self::REST_API_MESSAGE] = 'No valid parameters given. Valid parameters are ' . implode(',', $params_user);
            }
        }
        
        if (self::IsResultOK($result))
        {
            if (User::Update($id, $params_changed) == false)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Error when trying to update user record (' . DB::GetErrorCode() . ')';
            }
        }
        
        if (self::IsResultOK($result))
        {
            return self::PrepareResponse(array('message' => 'User Data Change Successful', 
                                               'user' => User::GetRequestedUserData($user)));
        }
        else
        {
            return self::ReturnError($result[self::HTTP_CODE],
                                     $result[self::REST_API_CODE],
                                     $result[self::REST_API_MESSAGE]);
        }
    }
        
    function Auth($email, $password)
    {
        $result = DB::GetSingle(User::DB_TABLE_NAME, array('where' => array('email' => $email, 
                                                                            'password' => User::EncryptPassword($password))));
        
        if (DB::GetErrorCode())
        {
            return self::ReturnError(Endpoint::CODE_INTERNAL_ERROR,
                                     self::INTERNAL_DB_ERROR,
                                     'Internal server error during user authentication(' . DB::GetErrorCode() . ')');
        }
        
        if ($result === null)
        {
            return self::ReturnError(Endpoint::CODE_FORBIDDEN,
                                     self::AUTH_WRONG,
                                     'Invalid login or password');
        }
        
        $login_time = time();
        $auth_token = User::GenerateAuthToken($login_time, $result['email']);
        User::UpdateAuth($result['id'], $auth_token, User::GenerateAuthExpiryDate($login_time));
        
        if (DB::GetErrorCode())
        {
            return self::ReturnError(Endpoint::CODE_INTERNAL_ERROR,
                                     self::INTERNAL_DB_ERROR,
                                     'Error when trying to rewrite auth token (' . DB::GetErrorCode() . ')');
        }
        else
        {
            $expiry_time = time() + 24 * 60 * 60;
            setcookie(self::USER_ID_KEY, $result['id'], $expiry_time, '/');
            setcookie(self::USER_AUTH_TOKEN, $auth_token, $expiry_time, '/');
        }
        
        return self::PrepareResponse(array('message' => 'Authentication Successful',
                                                        'user' => User::GetRequestedUserData($result)));
    }

    function Get($by, $id)
    {
        $result = self::GetEmptyFunctionResult();
    
        // trying to access another user with your own is a no-no, unless you are moderator or admin
        if ($id != $by['id'] && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You do not have permission to get info on other users';
        }
        
        if (self::IsResultOK($result))
        {
            $result = self::RetrieveUser($id);
            $user = $result['user'];
        }
        
        if (self::IsResultOK($result) == false)
        {
            return self::ReturnError($result[self::HTTP_CODE],
                                     $result[self::REST_API_CODE],
                                     $result[self::REST_API_MESSAGE]);
        }
        
        return self::PrepareResponse(array('message' => 'Request successful', 'user' => User::GetRequestedUserData($user)));
    }
    
    
    function Delete($by, $id)
    {
        $result = self::GetEmptyFunctionResult();
        // trying to access another user with your own is a no-no, unless you are moderator or admin
        if ($id != $by['id'] && $by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You do not have permission to delete other users';
        }
        
        $user = null;
        if (self::IsResultOK($result))
        {
            $result = self::RetrieveUser($id);
            $user = $result['user'];
        }
        

        
        if (self::IsResultOK($result))
        {
            if ($id != $by['id'] && 
                $by['role'] == User::ROLE_MODERATOR && 
                $user['role'] != User::ROLE_USER)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
                $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
                $result[self::REST_API_MESSAGE] = 'Your account can only delete simple user accounts';
            }
        }
        
        if (self::IsResultOK($result) && Meal::DeleteByOwner($id) == false)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
            $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
            $result[self::REST_API_MESSAGE] =  'Error when trying to delete user meals from database (' . DB::GetErrorCode() . ')';
        }
        
        if (self::IsResultOK($result) && User::Delete($id) == false)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
            $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
            $result[self::REST_API_MESSAGE] =  'Error when trying to delete user from database (' . DB::GetErrorCode() . ')';
        }
        
        if (self::IsResultOK($result))
        {
            return self::PrepareResponse(array('message' => 'User deleted successfuly'));
        }
        else
        {
            return self::ReturnError($result[self::HTTP_CODE],
                                     $result[self::REST_API_CODE],
                                     $result[self::REST_API_MESSAGE]);
        }
    }

    function ListAll($by, $params)
    {
        $result = self::GetEmptyFunctionResult();
        if ($by['role'] != User::ROLE_ADMIN && $by['role'] != User::ROLE_MODERATOR)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_FORBIDDEN;
            $result[self::REST_API_CODE] = self::FORBIDDEN_OPERATION;
            $result[self::REST_API_MESSAGE] = 'You do not have permission to access list with other users';
        }

        $amount = 20;
        if (self::IsResultOK($result))
        {
            if (isset($params['amount']))
            {
                $amount_temp = (int)($params['amount']);
                if ($amount_temp >= API_MAX_USERS_REQUESTED || $amount_temp <= 0)
                {
                    $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
                    $result[self::REST_API_CODE] = self::BAD_AMOUNT;
                    $result[self::REST_API_MESSAGE] = 'Amount should be greater than zero and lesser than ' . (API_MAX_USERS_REQUESTED + 1);
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
        
        $users = array();
        if (self::IsResultOK($result))
        {
            $user_query = User::GetAll($page,
                                       $amount);
            if ($user_query['code'] != 0)
            {
                // db error
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request failed to get users ('. DB::GetErrorCode() . ' )';
            }
            else
            {
                $users = $user_query['users'];
            }
        }
        
        if (self::IsResultOK($result))
        {
            $users_prepared = array();
            for ($i = 0; $i < count($users); $i++)
            {
                $users_prepared[] = User::GetRequestedUserData($users[$i]);
            }
            
            return self::PrepareResponse(array('users' => $users_prepared, 
                                               'page' => $page,
                                               'amount' => count($users_prepared),
                                               'last' => (count($users_prepared) < $amount) ? 'true' : 'false'));
        }
        
        return self::ReturnError($result[self::HTTP_CODE],
                                 $result[self::REST_API_CODE],
                                 $result[self::REST_API_MESSAGE]);
    }
    
    function Reset($params)
    {
        $result = self::GetEmptyFunctionResult();
        if (isset($params['email']) == false)
        {
            $result[self::HTTP_CODE] = Endpoint::CODE_BAD_REQUEST;
            $result[self::REST_API_CODE] = self::MISSING_EMAIL;
            $result[self::REST_API_MESSAGE] = 'You must specify the email for password reset';
        }
        
        $user = null;
        if (self::IsResultOK($result))
        {
            $user_query = User::GetByEmail($params['email']);
            // db error
            if ($user_query['code'] != 0)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request failed to get users ('. DB::GetErrorCode() . ' )';
            }
            else if ($user_query['user'] == null)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Request failed to find users';
            }
            else
            {
                $user = $user_query['user'];
            }
        }
        
        $password = '';
        if (self::IsResultOK($result))
        {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
            $password = substr(str_shuffle( $chars ), 0, 10);
            User::Update($user['id'], array('password' => User::EncryptPassword($password)));
        }
        
        if (self::IsResultOK($result))
        {
            if (mail($user['email'] , 'Password Recovery', 'Your New Password is ' . $password) == false)
            {
                $result[self::HTTP_CODE] = Endpoint::CODE_INTERNAL_ERROR;
                $result[self::REST_API_CODE] = self::INTERNAL_DB_ERROR;
                $result[self::REST_API_MESSAGE] = 'Password mail could not be sent due to error in the system';
            }
        }
        
        if (self::IsResultOK($result))
        {
            return self::PrepareResponse(array('message' => 'Password reset successful'));
        }
        
        return self::ReturnError($result[self::HTTP_CODE],
                                 $result[self::REST_API_CODE],
                                 $result[self::REST_API_MESSAGE]);
    }

}