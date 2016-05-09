<?php

require_once(__DIR__ . '/restTestHelpers.php');

class MealTest extends PHPUnit_Framework_TestCase
{
    static $id;

    public static function setUpBeforeClass()
    {
        TestHelpers::ResetCookies();
        // create user
        $response = TestHelpers::SendRequest('/users/',
                                             'POST',
                                             'email=nonexistent@mail.com&password=mypassword1&name=testuser', true);
        
        // auth user
        $response = TestHelpers::SendRequest('/users/auth',
                                             'POST',
                                             'email=nonexistent@mail.com&password=mypassword1',
                                              true);
        
        $response_decoded = json_decode($response['response'], true);
        self::$id = $response_decoded['user']['id'];
    }
    
    public static function tearDownAfterClass()
    {
        $response = TestHelpers::SendRequest('/users/' . self::$id,
                                             'DELETE',
                                             '',
                                             true);
    }
    
    public function testAddMeal()
    {
        // add meal
        $response = TestHelpers::SendRequest('/meals/' . self::$id,
                                             'POST',
                                             'date='.date('d.m.Y').'&time='.date('H:i').'&text=banana', true);
        print_r($response);

        $this->assertEquals(200, $response['code']);
        
    }
    
    public function testDeleteMeal()
    {
        // add meal
        $response = TestHelpers::SendRequest('/meals/' . self::$id,
                                             'POST',
                                             'calories=500&date='.date('d.m.Y').'&time='.date('H:i').'&text=banana', true);
        $this->assertEquals(200, $response['code']);
        
        $response_decoded = json_decode($response['response'], true);
        $meal_id = $response_decoded['meal']['id'];
        
        $response = TestHelpers::SendRequest('/meals/' . $meal_id,
                                             'GET',
                                             '', true);
        
        $this->assertEquals(200, $response['code']);
        $response_decoded = json_decode($response['response'], true);
        $meal_id = $response_decoded['meal']['id'];
        
        $response = TestHelpers::SendRequest('/meals/' . $meal_id,
                                             'DELETE',
                                             '', true);

        $this->assertEquals(200, $response['code']);
        
    }

    public function testUpdateMeal()
    {
        // add meal
        $response = TestHelpers::SendRequest('/meals/' . self::$id,
                                             'POST',
                                             'calories=500&date='.date('d.m.Y').'&time='.date('H:i').'&text=banana', true);
        $this->assertEquals(200, $response['code']);
        
        $response_decoded = json_decode($response['response'], true);
        $meal_id = $response_decoded['meal']['id'];
                
	$teststamp = time() - 86400;
	$date = date('d.m.Y', $teststamp);
	$time = date('H:i', $teststamp);

        $response = TestHelpers::SendRequest('/meals/' . $meal_id,
                                             'PUT',
                                             'date='.$date.'&time='.$time.'&calories=1000&text=steak', true);
        $this->assertEquals(200, $response['code']);
        $response_decoded = json_decode($response['response'], true);
        // update should return the updated recotd
        $this->assertEquals(1000, $response_decoded['meal']['calories']);
        $this->assertEquals('steak', $response_decoded['meal']['text']);
        $this->assertEquals($date, $response_decoded['meal']['date']);
        $this->assertEquals($time, $response_decoded['meal']['time']);

        $response = TestHelpers::SendRequest('/meals/' . $meal_id,
                                             'GET',
                                             '', true);
        // double-check if database got changed too                                             
        $response_decoded = json_decode($response['response'], true);
        $this->assertEquals(200, $response['code']);
        $this->assertEquals(1000, $response_decoded['meal']['calories']);
        $this->assertEquals('steak', $response_decoded['meal']['text']);
        $this->assertEquals($date, $response_decoded['meal']['date']);
        $this->assertEquals($time, $response_decoded['meal']['time']);
    }
    
    /**
     * @depends testUpdateMeal
     */
    public function testListMeals()
    {
        $get_params = '?startdate='.date('d.m.Y', time() - 86400).'&enddate='.date('d.m.Y', time());
        $response = TestHelpers::SendRequest('/meals/list/' . self::$id. $get_params,
                                             'GET',
                                             '', true);
        $this->assertEquals(200, $response['code']);
        
        $response_decoded = json_decode($response['response'], true);
        $this->assertNotEmpty($response_decoded['meals']);
    }
}