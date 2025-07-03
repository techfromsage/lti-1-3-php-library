<?php

namespace IMSGlobal\LTI\Tests\unit;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use IMSGlobal\LTI\Cookie;
use IMSGlobal\LTI\LTI_JWT_Exception;
use IMSGlobal\LTI\LTI_Message_Launch;
use IMSGlobal\LTI\LTI_Message_Validation_Exception;
use IMSGlobal\LTI\LTI_No_State_Found_Exception;
use IMSGlobal\LTI\LTI_Registration_Exception;
use IMSGlobal\LTI\Tests\unit\helpers\DummyDatabase;
use IMSGlobal\LTI\Tests\unit\helpers\InMemoryCache;

class LTI_Message_Launch_Test extends TestBase {

    private $privateKey;
    private $publicKey;

    public function setUp()
    {
        parent::setUp();        
        $this->publicKey = file_get_contents(dirname(__FILE__) . '/fixtures/private.pub');
        $this->privateKey = file_get_contents(dirname(__FILE__) . '/fixtures/private.key');
    }

    public function testNewInstance()
    {
        $this->assertInstanceOf(
            LTI_Message_Launch::class, 
            LTI_Message_Launch::newInstance(
                new DummyDatabase()
            )
        );
    }    

    public function testValidateStateWithInvalidStateThrowsException()
    {
        $this->setExpectedException(LTI_No_State_Found_Exception::class, 'State not found');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->once())->method('get_cookie');

        LTI_Message_Launch::newInstance(
            new DummyDatabase(),
            null,
            $cookie
        )->validate();
    }

    public function testValidateStateWithInvalidStateThrowsNoStateNotFoundExceptionIfUserBypassesStateValidation()
    {
        $this->setExpectedException(LTI_JWT_Exception::class, 'Missing id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->never())->method('get_cookie');

        LTI_Message_Launch::newInstance(
            new DummyDatabase(),
            null,
            $cookie
        )->validate(null, true);
    }

    public function testValidateState()
    {
        $state = uniqid();
        $this->setExpectedException(LTI_JWT_Exception::class, 'Missing id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        LTI_Message_Launch::newInstance(
            new DummyDatabase(),
            null,
            $cookie
        )->validate(['state' => $state]);
    }    

    public function testValidateJWTFormat()
    {
        $jwt = $this->encodeJWT($this->getValidJWTPayload());
        $state = uniqid();
        $this->setExpectedException(LTI_JWT_Exception::class, 'Invalid signature on id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);

        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        $messageLaunch->expects($this->atLeastOnce())->method('get_public_key')->willReturn($this->publicKey);

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);        
    }

    public function testValidateJWTFormatNotThreeParts()
    {
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIxMjM0NSJ9';
        $state = uniqid();
        $this->setExpectedException(LTI_JWT_Exception::class, 'Invalid id_token, JWT must contain 3 parts');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        LTI_Message_Launch::newInstance(
            new DummyDatabase(),
            new InMemoryCache(),
            $cookie
        )->validate(['state' => $state, 'id_token' => $jwt]);      
    }

    public function testValidateRegistration()
    {
        $badKey = openssl_pkey_new();
        $jwt = $this->encodeJWT($this->getValidJWTPayload(), $badKey);
        $state = uniqid();
        $this->setExpectedException(LTI_JWT_Exception::class, 'Invalid signature on id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        $messageLaunch->expects($this->atLeastOnce())->method('get_public_key')->willReturn($this->publicKey);

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }

    public function testValidateRegistrationNotFound()
    {
        $payload = $this->getValidJWTPayload();
        $payload['iss'] = 'bbb';
        $payload['aud'] = '67890';
        $jwt = $this->encodeJWT($payload);
        $state = uniqid();
        $this->setExpectedException(LTI_Registration_Exception::class, 'Registration not found.');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();
        
        /** @var DummyDatabase|\PHPUnit_Framework_MockObject_MockObject */
        $registrationDatabase = $this->getMockBuilder(DummyDatabase::class)
            ->setMethods(['find_registration_by_issuer'])
            ->getMock();
        
        $registrationDatabase->expects($this->atLeastOnce())
            ->method('find_registration_by_issuer')
            ->with('bbb', '67890')
            ->willReturn(null);

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [$registrationDatabase, new InMemoryCache(), $cookie]
            )
            ->getMock();

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }    

    public function testValidateRegistrationMismatchClientId()
    {
        $payload = $this->getValidJWTPayload();
        $payload['aud'] = uniqid();

        $jwt = $this->encodeJWT($payload);
        $state = uniqid();
        $this->setExpectedException(LTI_Registration_Exception::class, 'Registration not found');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }  

    public function testValidateRegistrationMissingClientId()
    {
        $payload = $this->getValidJWTPayload();
        unset($payload['aud']);

        $jwt = $this->encodeJWT($payload);
        $state = uniqid();
        $this->setExpectedException(LTI_Registration_Exception::class, 'Invalid client id');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }      
    
    public function testValidateJwtSignature()
    {
        $badKey = openssl_pkey_new();
        $payload = $this->getValidJWTPayload();
        $jwtWithInvalidSignature = $this->encodeJWT($payload, $badKey);
        $state = uniqid();
        $this->setExpectedException(LTI_JWT_Exception::class, 'Invalid signature on id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key', 'import_validators'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(['key' => $this->publicKey]);

        $messageLaunch->expects($this->never())->method('import_validators')->willReturnCallback(
            function () {
                foreach (glob(__DIR__ . '/helpers/validators/*.php') as $filename) {
                    include_once $filename;
                }                
            }
        );            

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwtWithInvalidSignature]);     

        $this->setExpectedException(null);
        $jwtWithValidSignature = $this->encodeJWT($payload);

        $messageLaunch->expects($this->once())->method('import_validators')->willReturnCallback(
            function () {
                foreach (glob(__DIR__ . '/helpers/validators/*.php') as $filename) {
                    include_once $filename;
                }                
            }
        );     

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwtWithValidSignature]);     
    }    

    public function testValidateMessage() 
    {
        $jwt = $this->encodeJWT($this->getValidJWTPayload());
        $state = uniqid();
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key', 'import_validators'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(new Key($this->publicKey, 'RS256'));

        $messageLaunch->expects($this->once())->method('import_validators')->willReturnCallback(
            function () {
                foreach (glob(__DIR__ . '/helpers/validators/*.php') as $filename) {
                    include_once $filename;
                }                
            }
        );

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }

    public function testValidateMessageInvalidMessage() 
    {
        $payload = $this->getValidJWTPayload();
        $payload['is_valid'] = false;
        $jwt = $this->encodeJWT($payload);
        $state = uniqid();
        $this->setExpectedException(LTI_Message_Validation_Exception::class, 'Message validation failed');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key', 'import_validators'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(new Key($this->publicKey, 'RS256'));

        $messageLaunch->expects($this->once())->method('import_validators')->willReturnCallback(
            function () {
                foreach (glob(__DIR__ . '/helpers/validators/*.php') as $filename) {
                    include_once $filename;
                }                
            }
        );

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }    

    public function testValidateMessageMatchesMultipleValidators() 
    {
        $payload = $this->getValidJWTPayload();
        $payload['https://purl.imsglobal.org/spec/lti/claim/message_type'] = 'LtiMultipleMatchingValidatorRequest';

        $jwt = $this->encodeJWT($payload);
        $state = uniqid();
        $this->setExpectedException(LTI_Message_Validation_Exception::class, 'Validator conflict');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder(LTI_Message_Launch::class)
            ->setMethods(['get_public_key', 'import_validators'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(new Key($this->publicKey, 'RS256'));

        $messageLaunch->expects($this->once())->method('import_validators')->willReturnCallback(
            function () {
                foreach (glob(__DIR__ . '/helpers/validators/*.php') as $filename) {
                    include_once $filename;
                }                
            }
        );

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }
    
    private function encodeJWT(array $payload, $key = null)
    {
        if (is_null($key)) {
            $key = $this->privateKey;
        }
        return JWT::encode($payload, $key, 'RS256');
    }

    private function getValidJWTPayload()
    {
        return json_decode(file_get_contents(dirname(__FILE__) . '/fixtures/jwts/lti_message_launch_tests_jwt.json'), true);
    }
}
