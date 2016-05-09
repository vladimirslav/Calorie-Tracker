<?php

include __DIR__ . '/../calories_rest_api/model/mealendpoint.php';

class DataValidationTest extends PHPUnit_Framework_TestCase
{
    public function testValidateDates()
    {
        $dates = array(
            array('date' => '10.11.111', 'valid' => false),
            array('date' => '1.1.111', 'valid' => false),
            array('date' => '11.111', 'valid' => false),
            array('date' => '11.11.2011', 'valid' => true),
            array('date' => '33.11.2011', 'valid' => false),
            array('date' => '15.21.2011', 'valid' => false),
            array('date' => '31.12.2011', 'valid' => true),
            array('date' => '29.02.2011', 'valid' => false),
            array('date' => '29.02.2012', 'valid' => true),
            array('date' => '', 'valid' => false),
        );
        
        foreach ($dates as $date)
        {
            $result = Endpoint::GetEmptyFunctionResult();
            MealEndpoint::ValidateDate($date['date'], $result);
            if ($date['valid'])
            {
                $this->assertEquals(200, $result['http_code'], $date['date']);
            }
            else
            {
                $this->assertNotEquals(200, $result['http_code'], $date['date']);
            }
        }
    }
    
    public function testValidateTime()
    {
        $time_values = array(
            array('time' => '29:29', 'valid' => false),
            array('time' => '24:00', 'valid' => false),
            array('time' => '23:59', 'valid' => true),
            array('time' => '00:00', 'valid' => true),
            array('time' => '08:08', 'valid' => true),
            array('time' => '00:000', 'valid' => false),
            array('time' => '1:00', 'valid' => false),
            array('time' => '10:00', 'valid' => true),
            array('time' => '', 'valid' => false),
        );
        
        foreach ($time_values as $time)
        {
            $result = Endpoint::GetEmptyFunctionResult();
            MealEndpoint::ValidateTime($time['time'], $result);
            if ($time['valid'])
            {
                $this->assertEquals(200, $result['http_code'], $time['time']);
            }
            else
            {
                $this->assertNotEquals(200, $result['http_code'], $time['time']);
            }
        }
    }
    
    public function testValidateCalories()
    {
        $calories_values = array(
            array('calories' => '5000', 'valid' => true),
            array('calories' => 5000, 'valid' => true),
            array('calories' => -5000, 'valid' => false),
            array('calories' => '', 'valid' => false),
            array('calories' => '40', 'valid' => true),
            array('calories' => 2, 'valid' => true),
            array('calories' => '-1502', 'valid' => false),
            array('calories' => 0, 'valid' => false),
            array('calories' => 1, 'valid' => true),
            array('calories' => 5001, 'valid' => false),
        );
        
        foreach ($calories_values as $calorie)
        {
            $result = Endpoint::GetEmptyFunctionResult();
            MealEndpoint::ValidateCalories($calorie['calories'], $result);
            if ($calorie['valid'])
            {
                $this->assertEquals(200, $result['http_code'], $calorie['calories']);
            }
            else
            {
                $this->assertNotEquals(200, $result['http_code'], $calorie['calories']);
            }
        }
    }
    
    public function testValidateText()
    {
        $text_values = array(
            array('text' => '', 'valid' => true),
            array('text' => 0, 'valid' => true),
            array('text' => 'verylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftextverylonglineoftext', 'valid' => false),
        );
        
        foreach ($text_values as $text)
        {
            $result = Endpoint::GetEmptyFunctionResult();
            MealEndpoint::ValidateText($text['text'], $result);
            if ($text['valid'])
            {
                $this->assertEquals(200, $result['http_code'], $text['text']);
            }
            else
            {
                $this->assertNotEquals(200, $result['http_code'], $text['text']);
            }
        }
    }
}