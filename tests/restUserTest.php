<?php

require_once(__DIR__ . '/restTestHelpers.php');

class UserTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        TestHelpers::ResetCookies();
    }

    // assumes that there is an admin account user: test@example.com pw: changeme

    const WRONG_PASSWORD_ERROR_CODE = 9;

    public function testAuth()
    {
        $response = TestHelpers::SendRequest('/users/auth',
                                             'POST',
                                             'email=' . TestHelpers::GetAuthMail() . '&password=changeme',
                                             true);
                               
        $this->assertEquals(200, $response['code']);
    }
    
    public function testBadAuthNoPassword()
    {
        $response = TestHelpers::SendRequest('/users/auth',
                                             'POST',
                                             'email=' . TestHelpers::GetAuthMail());
        
        $this->assertEquals(400, $response['code']);
        $response_decoded = json_decode($response['response'], true);
        $this->assertEquals($response_decoded['app_code'], self::WRONG_PASSWORD_ERROR_CODE); // password is wrong message
    }
    
    public function testBadAuthWrongPassword()
    {
        $response = TestHelpers::SendRequest('/users/auth',
                                             'POST',
                                             'email=' . TestHelpers::GetAuthMail() . '&password=wrong');
        $this->assertEquals(403, $response['code']);
    }
    

    /**
     * @depends testAuth
     */
    public function testGetInfoPassIdWrongly()
    {
        $response = TestHelpers::SendRequest('/users',
                                             'GET',
                                             'id=1', true);
        $this->assertEquals(400, $response['code']);
    }
    
    /**
     * @depends testAuth
     */
    public function testGetInfoPassWrongId()
    {
        $response = TestHelpers::SendRequest('/users/wrongid',
                                             'GET',
                                             '', true);
        $this->assertEquals(404, $response['code']);
    }

    /**
     * @depends testAuth
     */
    public function testGetInfoPassId()
    {
        $response = TestHelpers::SendRequest('/users/1',
                                             'GET',
                                             '', true);
        $this->assertEquals(200, $response['code']);
        
    }
    
    public function testCreateAuthUpdateGetDelete()
    {
        // create user
        $response = TestHelpers::SendRequest('/users/',
                                             'POST',
                                             'email=nonexistent@mail.com&password=mypassword1&name=testuser', true);

        $this->assertEquals(200, $response['code']);
        
        // auth user
        $response = TestHelpers::SendRequest('/users/auth',
                                             'POST',
                                             'email=nonexistent@mail.com&password=mypassword1',
                                             true);
        
        $this->assertEquals(200, $response['code']);
        $response_decoded = json_decode($response['response'], true);
        $id = $response_decoded['user']['id'];
        
        // get user info (of himself)
        $response = TestHelpers::SendRequest('/users/' . $id,
                                             'GET',
                                             '',
                                             true);
                                      
        $response_decoded = json_decode($response['response'], true);
        $this->assertEquals(200, $response['code']);
        
        // change user data
        $response = TestHelpers::SendRequest('/users/' . $id,
                                             'PUT',
                                             'daily_calories=2500&password=123456',
                                             true);
        
        
        $response_decoded = json_decode($response['response'], true);
        
        $this->assertEquals(200, $response['code']);
        
        // get new updated user info (of himself)
        $response = TestHelpers::SendRequest('/users/' . $id,
                                             'GET',
                                             '',
                                             true);
                                      
        $response_decoded = json_decode($response['response'], true);
        $this->assertEquals(2500, $response_decoded['user']['daily_calories']);

        $this->assertEquals('nonexistent@mail.com', $response_decoded['user']['email']);
        
        // delete user
        $response = TestHelpers::SendRequest('/users/' . $id,
                                             'DELETE',
                                             '',
                                             true);
        $this->assertEquals(200, $response['code']);
        
    }

}