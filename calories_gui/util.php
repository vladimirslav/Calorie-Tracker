<?php

function CmpByTime($meal1, $meal2)
{
    $t1 = strtotime($meal1['time']);
    $t2 = strtotime($meal2['time']);
    
    if ($t1 > $t2)
    {
        return -1;
    }
    else if ($t2 > $t1)
    {
        return 1;
    }
    
    return 0;
}

function markIfActive($current_page, $pagenames)
{   
    if (is_array($pagenames))
    {
        if (in_array($current_page, $pagenames))
        {
            echo 'class="active"';
        }
    }
    else if (strcmp($current_page, $pagenames) === 0)
    {
        echo 'class="active"';
    }
}

?>