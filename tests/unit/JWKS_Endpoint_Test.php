<?php

namespace IMSGlobal\LTI\Tests\unit;

use IMSGlobal\LTI\JWKS_Endpoint;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_Exception;

class JWKS_Endpoint_Test extends TestBase {
    private $privateKey;
    private $registrationDatabase;

    public function setUp()
    {
        $this->privateKey = file_get_contents(dirname(__FILE__) . '/fixtures/private.key');
        $this->registrationDatabase = new DummyDatabase();
        parent::setUp();
    }

    public function testNewInstance()
    {
        $kid = uniqid();
        $keys = [$kid => $this->privateKey];
        $this->assertInstanceOf('IMSGlobal\LTI\JWKS_Endpoint', JWKS_Endpoint::newInstance($keys));
    }

    public function testNewInstanceFromIssuerAndClientId()
    {
        $issuer = uniqid();
        $clientId = uniqid();

        $registration = $this->getMockLTIRegistration();
        $registration->expects($this->once())->method('get_kid')->willReturn('my_kid');
        $registration->expects($this->once())->method('get_tool_private_key')->willReturn($this->privateKey);

        $database = $this->getMockRegistrationDatabase();
        $database->expects($this->once())
            ->method('find_registration_by_issuer')
            ->with($issuer, $clientId)
            ->willReturn($registration);

        $jwksEndpoint = JWKS_Endpoint::from_issuer($database, $issuer, $clientId);
        $this->assertInstanceOf('IMSGlobal\LTI\JWKS_Endpoint', $jwksEndpoint);

        $jwks = $jwksEndpoint->get_public_jwks();
        $this->assertArrayHasKey('keys', $jwks);
        $this->assertCount(1, $jwks['keys']);
        $this->assertEquals('my_kid', $jwks['keys'][0]['kid']);
    }

    public function testNewInstanceFromRegistration()
    {
        $registration = $this->getMockLTIRegistration();
        $registration->expects($this->once())->method('get_kid')->willReturn('my_kid');
        $registration->expects($this->once())->method('get_tool_private_key')->willReturn($this->privateKey);


        $jwksEndpoint = JWKS_Endpoint::from_registration($registration);
        $this->assertInstanceOf('IMSGlobal\LTI\JWKS_Endpoint', $jwksEndpoint);

        $jwks = $jwksEndpoint->get_public_jwks();
        $this->assertArrayHasKey('keys', $jwks);
        $this->assertCount(1, $jwks['keys']);
        $this->assertEquals('my_kid', $jwks['keys'][0]['kid']);
    }

    public function testGetPublicJwks()
    {
        $keys = [
            'my_first_key' => $this->privateKey,
            'my_next_key' => $this->privateKey
        ];
        $jwksEndpoint = JWKS_Endpoint::newInstance($keys);

        $jwks = $jwksEndpoint->get_public_jwks();
        $this->assertArrayHasKey('keys', $jwks);
        $this->assertCount(2, $jwks['keys']);
        $this->assertEquals('my_first_key', $jwks['keys'][0]['kid']);        
        $this->assertEquals('my_next_key', $jwks['keys'][1]['kid']);        
        foreach ($jwks['keys'] as $key) {
            $this->assertEquals('RSA', $key['kty']);
            $this->assertEquals('RS256', $key['alg']);
            $this->assertEquals('sig', $key['use']);
            $this->assertNotEmpty($key['e']);
            $this->assertNotEmpty($key['n']);
        }       
    }

    /**
     * Generates a mock registration database
     * 
     * @return PHPUnit_Framework_MockObject_MockObject|\IMSGlobal\LTI\Tests\unit\DummyDatabase
     * 
     * @throws PHPUnit_Framework_Exception 
     */
    private function getMockRegistrationDatabase()
    {
        return $this->getMockBuilder('\IMSGlobal\LTI\Tests\unit\DummyDatabase')
            ->setMethods(['find_registration_by_issuer'])
            ->getMock();
    }

    /**
     * Generates a mock LTI registration
     * 
     * @return PHPUnit_Framework_MockObject_MockObject|\IMSGlobal\LTI\LTI_Registration
     * 
     * @throws PHPUnit_Framework_Exception 
     */
    private function getMockLTIRegistration()
    {
        return $this->getMockBuilder('\IMSGlobal\LTI\LTI_Registration')
            ->setMethods(['get_kid', 'get_tool_private_key'])
            ->getMock();
    }
}