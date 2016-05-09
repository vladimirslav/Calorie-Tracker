<?php
    require_once 'util.php';

    header("Access-Control-Allow-Orgin: *");
    header("Access-Control-Allow-Methods: *");
    header('Access-Control-Allow-Headers: x-requested-with');
    
    if (isset($_POST['page']) == true)
    {
        $user = null;
        $is_admin = false;
        if (isset($_POST['data']) && isset($_POST['data']['user']))
        {
            $user = $_POST['data']['user'];
            if (strcmp($user['role'], 'administrator') === 0 ||
                strcmp($user['role'], 'moderator') === 0)
            {
                $is_admin = true;
            }
        }
    
        // always include div id data for successful replacement
        ob_start();
        echo '<div id="data">';
        $page = $_POST['page'];
        if (strcmp($page, 'login') === 0)
        {
            include 'views/login.html.php';
        }
        else if (strcmp($page, 'register') === 0)
        {
            include 'views/register.html.php';
        }
        else if (strcmp($page, 'main') === 0)
        {
            $meals = array();
            $calories = 0;
            if (isset($_POST['data']['meals']))
            {
                $meals = $_POST['data']['meals'];
                foreach ($meals as $meal)
                {
                    $calories += $meal['calories'];
                }
            }
            $calories_class = 'calories_ok';
            if ($calories > $user['daily_calories'])
            {
                $calories_class = 'calories_exceeded';
            }
            
            usort($meals, 'CmpByTime');
            include 'views/main.html.php';
            include 'views/day.html.php';
        }
        else if (strcmp($page, 'addmeal') === 0)
        {
            $is_edit = (isset($_POST['data']['is_edit']));
            $meal_id = (isset($_POST['data']['meal_id'])) ? (int)($_POST['data']['meal_id']) : 0;
            $owner_id = (isset($_POST['data']['meal_owner'])) ? (int)($_POST['data']['meal_owner']) : $user['id'];
            include 'views/main.html.php';
            include 'views/add.html.php';
        }
        else if (strcmp($page, 'settings') === 0)
        {
            $settings_user_id = $user['id'];
            if (isset($_POST['data']['settings_id']))
            {
                $settings_user_id = (int)$_POST['data']['settings_id'];
            }
            
            $settings_user_name = $user['name'];
            if (isset($_POST['data']['settings_name']))
            {
                $settings_user_name = $_POST['data']['settings_name'];
            }
            
            include 'views/main.html.php';            
            include 'views/settings.html.php';
        }
        else if (strcmp($page, 'statistics') === 0)
        {
            include 'views/main.html.php';
            include 'views/filter.html.php';
        }
        else if (strcmp($page, 'userlist') === 0)
        {
            $users = array();
            if (isset($_POST['data']['users']))
            {
                $users = $_POST['data']['users'];
            }
            
            $is_last = true;
            if (isset($_POST['data']['is_last']))
            {
                $is_last = (strcmp($_POST['data']['is_last'], 'true') === 0);
            }
            
            $page_num = 0;
            if (isset($_POST['data']['page_num']))
            {
                $page_num = (int)($_POST['data']['page_num']);
            }
            
            include 'views/main.html.php';
            include 'views/userlist.html.php';
        }
        else if (strcmp($page, 'meallist') === 0)
        {
            $meals = array();
            
            $owner_name = $user['name'];
            if (isset($_POST['data']['owner_id']))
            {
                $owner_name = $_POST['data']['owner_name'];
            }
            $owner_name = strip_tags($owner_name);
            
            $owner_id = $user['id'];
            if (isset($_POST['data']['owner_id']))
            {
                $owner_id = $_POST['data']['owner_id'];
            }
            
            if (isset($_POST['data']['meals']))
            {
                $meals = $_POST['data']['meals'];
            }
            
            $is_last = true;
            if (isset($_POST['data']['is_last']))
            {
                $is_last = (strcmp($_POST['data']['is_last'], 'true') === 0);
            }
            
            $page_num = 0;
            if (isset($_POST['data']['page_num']))
            {
                $page_num = (int)($_POST['data']['page_num']);
            }
            
            include 'views/main.html.php';
            include 'views/meallist.html.php';
        }
        else if (strcmp($page, 'daylist') === 0)
        {
            $meals = array();
            $meals_by_date = array();
            if (isset($_POST['data']['meals']))
            {
                $meals = $_POST['data']['meals'];
                foreach ($meals as $meal)
                {
                    if (isset($meals[$meal['date']]) == false)
                    {
                        $meals_by_date[$meal['date']]['data'][] = $meal;
                    }
                    else
                    {
                        $meals_by_date[$meal['date']]['data'] = array($meal);
                    }
                }
            }
            
            
            foreach ($meals_by_date as $date => $meals)
            {
                foreach ($meals['data'] as $meal)
                {
                    if (isset($meals_by_date[$date]['calories_total']))
                    {
                        $meals_by_date[$date]['calories_total'] += $meal['calories'];
                    }
                    else
                    {
                        $meals_by_date[$date]['calories_total'] = $meal['calories'];
                    }
                }
                
                usort($meals_by_date[$date]['data'], 'CmpByTime');
            }
            
            krsort($meals_by_date);
            
            include 'views/main.html.php';            
            include 'views/daylist.html.php';
        }
        else if (strcmp($page, 'recoverpassword') === 0)
        {
            include 'views/forgotpassword.html.php';
        }
        else if (strcmp($page, 'about') === 0)
        {
            include 'views/main.html.php';            
            include 'views/about.html.php';            
        }
        else
        {
            echo 'Requested view not found <a href="/" data-type="req_login">Try to log in once more</a>';
        }
        echo '</div>';
        echo ob_get_clean();
    }
    else
    {
        http_response_code(404); // not found
    }
?>