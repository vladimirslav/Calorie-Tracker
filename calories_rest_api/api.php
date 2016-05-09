<?php
    require_once('model/userendpoint.php');
    require_once('model/mealendpoint.php');

    define("USERS_ENDPOINT", "users");
    define("MEALS_ENDPOINT", "meals");
    define("LOGOUT_ENDPOINT", "logout");
    define("DOC_ENDPOINT", "docs"); // for documentation
    
    http_response_code(404); // by default - show 404, unless we encounter a good method
    
    $ALLOWED_ENDPOINTS = array(
        USERS_ENDPOINT, MEALS_ENDPOINT, DOC_ENDPOINT, LOGOUT_ENDPOINT
    );

    $uri = $_GET['uri'];
    $addr_parts = explode('/', rtrim($uri, "/"));
    
    $method = $_SERVER['REQUEST_METHOD'];
    // additional method checks in case of 'POST'
    if (strcmp($method, 'POST') === 0 && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
    {
        if (strcmp($_SERVER['HTTP_X_HTTP_METHOD'], 'DELETE') === 0)
        {
            $this->method = 'DELETE';
        }
        else if (strcmp($_SERVER['HTTP_X_HTTP_METHOD'], 'PUT') === 0)
        {
            $this->method = 'PUT';
        }
        else
        {
            $method = 'UNKNOWN';
        }
    }
    
    $len = count($addr_parts);
    while($len > 0 && in_array(strtolower($addr_parts[0]), $ALLOWED_ENDPOINTS) == false)
    {
        array_shift($addr_parts);
        $len--;
    }
    
    if ($len > 0)
    {
        if (strcmp($addr_parts[0], USERS_ENDPOINT) === 0)
        {
            die(UserEndpoint::RunEndpoint($method, $addr_parts));
        }
        else if (strcmp($addr_parts[0], MEALS_ENDPOINT) === 0)
        {
            die(MealEndpoint::RunEndpoint($method, $addr_parts));
        }
        else if (strcmp($addr_parts[0], LOGOUT_ENDPOINT) === 0)
        {
            die(UserEndpoint::Logout());
        }
        else if (strcmp($addr_parts[0], DOC_ENDPOINT) === 0)
        {
            http_response_code(200);
            header("Content-type:application/pdf");
            header("Content-Disposition:attachment;filename='calorie_api_docs.pdf'");
            die(readfile("doc/api_doc.pdf"));
        }
    }

    die(Endpoint::ReturnError(Endpoint::CODE_NOT_FOUND));
?>