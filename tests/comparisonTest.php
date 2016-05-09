<?php

include __DIR__ . '/../calories_gui/util.php';

class ComparisonTest extends PHPUnit_Framework_TestCase
{
    static $id;
    
    public function testCompareTimes()
    {
        // time1, time2, expected result
        // -1 if first is greater
        //  1 if second is greater
        //  0 if equals
        $time_pairs = array(
            array('08:00', '10:00', 1),
            array('23:00', '23:00', 0),
            array('23:59', '10:00', -1),
            array('08:00', '08:30', 1),
            array('20:00', '12:40', -1),
            array('00:00', '00:00', 0),
        );
        
        foreach ($time_pairs as $pair)
        {
            //imitate meals with member 'time':
            $meal1 = array('time' => $pair[0]);
            $meal2 = array('time' => $pair[1]);
            $this->assertEquals($pair[2], CmpByTime($meal1, $meal2));
        }
        
    }
}