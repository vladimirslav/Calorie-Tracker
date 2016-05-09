<?php

    require_once 'config.php';
    require_once 'model/database.php';
    require_once 'model/user.php';
    // generate htaccess for current folder:

    $execution_dir = dirname($_SERVER['REQUEST_URI']);
    
    $had_errors = false;
    
    $db_query = file_get_contents('res/user_table_creation_sql.sql');
    DB::RawQuery($db_query);
    
    if (DB::GetConnectionErrorCode())
    {
        echo "Could Not Connect To Database<br/>";
        echo DB::GetConnectionError();
        $had_errors = true;
    }
    
    if ($had_errors == false && DB::GetErrorCode())
    {
        echo "Error occured<br/>";
        echo DB::GetError();
        $had_errors = true;
    }
    
    if ($had_errors == false)
    {
        echo "User table created\n";
        
        $db_query = file_get_contents('res/meal_table_creation_sql.sql');
        DB::RawQuery($db_query);
    }
    
    if ($had_errors == false && DB::GetErrorCode())
    {
        echo "Error occured<br/>";
        echo DB::GetError();
        $had_errors = true;
    }
    
    if ($had_errors == false)
    {
        echo "Meal table created\n";
    
        $install_time = time(); 
        User::Add(SITE_ADMIN_EMAIL, 'Admin', 'changeme', User::ROLE_ADMIN);
    }
    
    if ($had_errors == false && DB::GetErrorCode())
    {
        echo "Error occured<br/>";
        echo DB::GetError();
        $had_errors = true;
    }
    
    if ($had_errors == false)
    {
        $htaccess_contents = file_get_contents('res/htaccess_template.templ.php');
        $htaccess_contents = str_replace('{{execution_dir}}', $execution_dir, $htaccess_contents);
        file_put_contents('.htaccess', $htaccess_contents);
    }

    if ($had_errors)
    {
        echo '<br/>Install Did not complete';
    }
    else
    {
        echo '<br/>Installed successfully! Admin user login: <br/><strong>' . SITE_ADMIN_EMAIL . '</strong><br/>' .
        'Password: <br/>' . '<strong>changeme</strong>';
    }

?>