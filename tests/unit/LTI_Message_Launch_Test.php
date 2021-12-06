<?php

namespace IMSGlobal\LTI\Tests\unit;

use IMSGlobal\LTI\Cookie;
use IMSGlobal\LTI\LTI_Message_Launch;
use IMSGlobal\LTI\Tests\unit\helpers\InMemoryCache;

class LTI_Message_Launch_Test extends TestBase {

    private $publicKey;

    private $validatorsLoaded = false;

    public function setUp()
    {
        parent::setUp();        
        $this->publicKey = file_get_contents(dirname(__FILE__) . '/fixtures/private.pub');
    }

    public function testNewInstance()
    {
        $this->assertInstanceOf(
            'IMSGlobal\LTI\LTI_Message_Launch', 
            LTI_Message_Launch::newInstance(
                new DummyDatabase()
            )
        );
    }    

    public function testValidateStateWithInvalidStateThrowsException()
    {
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'State not found');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->once())->method('get_cookie');

        LTI_Message_Launch::newInstance(
            new DummyDatabase(),
            null,
            $cookie
        )->validate();
    }

    public function testValidateState()
    {
        $state = uniqid();
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Missing id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
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
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIxMjM0NSJ9.MPkXNVeUj89bEt6nOC7KT3JW2KgY8Kks86xpL2uUatNLlzJk0HEd0BDq48D9ZSIGLpopkmV_pG1h_WHnXrX8Svi7-BnUQDz1u9Jomm57YlIz11XPM6FHoSVhUm1pdxLIYFbZu51vrJRSAYxm_zwiTnM_FLb3ZQZGAFyQ1rKGoevLy9Cyr_9gWyncoyXktz8NJ88nXCDf2MC2HsYRrDW48uXyFe6CtVhdHWqqLcNgw9LQNEtEaB_yU1aKkLSVKOX7LhCZ79rNx-06kG7LtgAhGGcOZYgtGGx4xaFJxJ7eBsvQBEkAyZbd4E6vZKHVEN-rQfreNUgTla_xdRfMdgDEpA';
        $state = uniqid();
        $this->setExpectedException('\Exception', 'Invalid signature on id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);

        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
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
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Invalid id_token, JWT must contain 3 parts');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
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
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIxMjM0NSJ9.MPkXNVeUj89bEt6nOC7KT3JW2KgY8Kks86xpL2uUatNLlzJk0HEd0BDq48D9ZSIGLpopkmV_pG1h_WHnXrX8Svi7-BnUQDz1u9Jomm57YlIz11XPM6FHoSVhUm1pdxLIYFbZu51vrJRSAYxm_zwiTnM_FLb3ZQZGAFyQ1rKGoevLy9Cyr_9gWyncoyXktz8NJ88nXCDf2MC2HsYRrDW48uXyFe6CtVhdHWqqLcNgw9LQNEtEaB_yU1aKkLSVKOX7LhCZ79rNx-06kG7LtgAhGGcOZYgtGGx4xaFJxJ7eBsvQBEkAyZbd4E6vZKHVEN-rQfreNUgTla_xdRfMdgDEpA';
        $state = uniqid();
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Invalid signature on id_token');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
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
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJiYmIiLCJhdWQiOiI2Nzg5MCJ9.VcA1JqejcOLQzsNoaYfqwAHbahLXRXEoHZXta3eC9orspNp5feusB7tVJC392GyOd8irRzpLebrgSqKgFF6QycyX4SIf50xwFN_gKAZzEKGgF5TGJKskdKj7a9KdyANG9de9KvEUopEWChstS887HW-RCxL5P-orzdfg86L0IpeoOLaLPCxpk9SiO0vmIevdIkaKC_UgGtHbdIC05Xjdg0xYoOomc1KClPMfI-LXpSg7pAEOPg3FOHvbArwHA_maT2V_YbyJMCfnw-jpuS4azzkxM_oNnAB_vXjJSMIRGETOH-KNngCc7ulz946UfGE02Y7uhcQf8rVi1FqkSIZqJw';
        $state = uniqid();
        $this->setExpectedException('\Exception', 'Registration not found.');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();
        
        /** @var DummyDatabase|\PHPUnit_Framework_MockObject_MockObject */
        $registrationDatabase = $this->getMockBuilder('\IMSGlobal\LTI\Tests\unit\DummyDatabase')
            ->setMethods(['find_registration_by_issuer'])
            ->getMock();
        
        $registrationDatabase->expects($this->atLeastOnce())
            ->method('find_registration_by_issuer')
            ->with('bbb', '67890')
            ->willReturn(null);

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [$registrationDatabase, new InMemoryCache(), $cookie]
            )
            ->getMock();

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }    

    public function testValidateRegistrationMismatchClientId()
    {
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIifQ.LFh_PcAmtpeGhqOUoUcyCAc2-DuVFJGlfuTIhuiLYpHmgEFgnFo-il9DFu4Ze8SPPW44B6Mp-9hI1KK8bad0uGMfTtamTNrXkD0ze7gkd4HyEXaVOWygEcWe9hbGc3Ku1-wBL3giusx2wV4wlY5tcf9adKZxZnrkuvQ49X6bflxPRMBUgEpNOVIYVRaMOGXFUHnr4wdcvVurWmA9DSg3-oV5n3HiNqSx0aKa61laFAJuNw64Sc-4t50VeK8zoTBDms21hSdwfClCqy9yK6wm5fJGAVv1_-p6zpw9NZ5fRUMfcLvTxdJ1KWa6cqk3vBApT9rIq46wMM1zMcGcUt0ODA';
        $state = uniqid();
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Client id not registered for this issuer');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }  
    
    public function testValidateJwtSignature()
    {
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIxMjM0NSJ9.t8DOcU2zOhHGEkk6ly4HKvuOHHP-LU0XAA1FOUI6Emb_4prDGahMgLkBHqKsRpp0rqGTJyfuEHuNjA6sEK5hlPMMdudk9lwObKM5hxMKaquMBj4Cyx-LIhHjevN4UbhH9fg_FTq6royd-bYVBKqrwrZiOQxl9eZQb_ASqxpYvamPYEva7Jm-noN7Ye6ZU3xQADT8TRnmSBfZ60F5usg6E_X10TZ3QJFCAjQxDo_W6wTXdL2I0ccvQQLBPhett0MQorPR7yDFLBEomgr_PXTkq_BzMq4mJ8BKqmSJTwelJSWIc-rBEyVmBU2nF9-jdXNTM1DxCfg4YTJa_gXdwGh5zg';
        $state = uniqid();
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Invalid message type');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(['key' => $this->publicKey]);

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }    

    public function testValidateMessage() 
    {
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIxMjM0NSIsInN1YiI6ImFhYV91c2VyX3h5eiIsImh0dHBzOi8vcHVybC5pbXNnbG9iYWwub3JnL3NwZWMvbHRpL2NsYWltL21lc3NhZ2VfdHlwZSI6Ikx0aVJlc291cmNlTGlua1JlcXVlc3QiLCJodHRwczovL3B1cmwuaW1zZ2xvYmFsLm9yZy9zcGVjL2x0aS9jbGFpbS92ZXJzaW9uIjoiMS4zLjAiLCJodHRwczovL3B1cmwuaW1zZ2xvYmFsLm9yZy9zcGVjL2x0aS9jbGFpbS9yb2xlcyI6Imh0dHA6Ly9wdXJsLmltc2dsb2JhbC5vcmcvdm9jYWIvbGlzL3YyL21lbWJlcnNoaXAjSW5zdHJ1Y3RvciIsImh0dHBzOi8vcHVybC5pbXNnbG9iYWwub3JnL3NwZWMvbHRpL2NsYWltL3Jlc291cmNlX2xpbmsiOnsiaWQiOiJhYWFfMTIzNDUifX0.xThL4I4H7WWY5hgS_sIkL_3iiVscoZ7oMtrt3TCrmhLHRMebDFOGz_B_23q1DGxWtvFB3Rahw8ZAUT9q8OPPZsRIdk_7m0XCEOJeu1RM1NfCnrNWQbgbkkglYGOU64EjllbkxjVWoNyUn4A50DbDVbfG--T7kfhQC6A4XQlQpss8TY_JzmRJLFg071WuvwbvL2MxPQOztomvi2ndvs_jhmEEw1Wswiog1ZvBssmILoq0zFTJXoAOhBVZmjzBMYT3OPzk_FvM8NDRQaDemPzNATeh0ndmmNTCbNnO8HuHlB8gRyFSFza_O_fduSipRyOXle-nb6q7NED6AecAdWYt5w';
        $state = uniqid();
        // $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Invalid message type');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
            ->setMethods(['get_public_key'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(['key' => $this->publicKey]);

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }

    public function testValidateMessageInvalidMessage() 
    {
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIxMjM0NSIsInN1YiI6ImFhYV91c2VyX3h5eiIsImh0dHBzOi8vcHVybC5pbXNnbG9iYWwub3JnL3NwZWMvbHRpL2NsYWltL21lc3NhZ2VfdHlwZSI6Ikx0aVRlc3RSZXF1ZXN0IiwiaXNfdmFsaWQiOmZhbHNlLCJodHRwczovL3B1cmwuaW1zZ2xvYmFsLm9yZy9zcGVjL2x0aS9jbGFpbS92ZXJzaW9uIjoiMS4zLjAiLCJodHRwczovL3B1cmwuaW1zZ2xvYmFsLm9yZy9zcGVjL2x0aS9jbGFpbS9yb2xlcyI6Imh0dHA6Ly9wdXJsLmltc2dsb2JhbC5vcmcvdm9jYWIvbGlzL3YyL21lbWJlcnNoaXAjSW5zdHJ1Y3RvciIsImh0dHBzOi8vcHVybC5pbXNnbG9iYWwub3JnL3NwZWMvbHRpL2NsYWltL3Jlc291cmNlX2xpbmsiOnsiaWQiOiJhYWFfMTIzNDUifX0.cpgrZeefNaaZbphhWGe4_awqFxEnw4Cl6O4B49LVpNjvtvfYg0Bil0DktdGt81xCckdY1CcXVfLRb_UKOuUZCFu1X9KwC8x0knHq-Wf0inoGcfj8PiK1GjgJtzNrtniVYZC67zcL7gULIdNbJwsm4XvSjjz7iHDTTYYTOVlCOIrf4bTwFI6fB18V2SrD9kSnG9UGzHf96sLfNuVRgGeg2QQtTH0pIP3mgSUFNvCy5txthr7XEsOsgDmPIydEl3qEyQDiNieQZJc1nRBzXP0AoJW2M-wT783d97DhH4cqs2u88FB3DSzwO6rY7pKd1MMPT5ujWQC2FvezwM0iBg7iYw';
        $state = uniqid();
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Message validation failed');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
            ->setMethods(['get_public_key', 'import_validators'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(['key' => $this->publicKey]);

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
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhYWEiLCJhdWQiOiIxMjM0NSIsInN1YiI6ImFhYV91c2VyX3h5eiIsImh0dHBzOi8vcHVybC5pbXNnbG9iYWwub3JnL3NwZWMvbHRpL2NsYWltL21lc3NhZ2VfdHlwZSI6Ikx0aU11bHRpcGxlTWF0Y2hpbmdWYWxpZGF0b3JSZXF1ZXN0IiwiaXNfdmFsaWQiOmZhbHNlLCJodHRwczovL3B1cmwuaW1zZ2xvYmFsLm9yZy9zcGVjL2x0aS9jbGFpbS92ZXJzaW9uIjoiMS4zLjAiLCJodHRwczovL3B1cmwuaW1zZ2xvYmFsLm9yZy9zcGVjL2x0aS9jbGFpbS9yb2xlcyI6Imh0dHA6Ly9wdXJsLmltc2dsb2JhbC5vcmcvdm9jYWIvbGlzL3YyL21lbWJlcnNoaXAjSW5zdHJ1Y3RvciIsImh0dHBzOi8vcHVybC5pbXNnbG9iYWwub3JnL3NwZWMvbHRpL2NsYWltL3Jlc291cmNlX2xpbmsiOnsiaWQiOiJhYWFfMTIzNDUifX0.KqTrgmttOPYlgcfPxuobQJCj4xHUNt-FqInyhDmtZ-K2XVjUVMZw061cuR5oqlOd9XDSazptjrQPsjTVMTmo1uBdmhLZk4n78Ln-GjHi39F-MLrk_nVK2gR0ths-sfu8poQF8xXtznaayjifM95PGcBxhKiUBdUbkeBoh2Km8BQox_rQa5z_bQQX-8b2SUzidL1t46gWO_dl0ydAwbiDO1egdn1Szl9EyKvzW4DtUlnRuFtdi0yr14viWMM2syEZ0kYL2pCOBkNm1A-n-5pYGfKxvXRDur6VOrnFjySOZC3Z2Y-pXl6ERRGQuB_8yXOhiurTuH3IbRW-iPgyooll3A';
        $state = uniqid();
        $this->setExpectedException('\IMSGlobal\LTI\LTI_Exception', 'Validator conflict');
        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder('\IMSGlobal\LTI\Cookie')
            ->setMethods(['get_cookie'])
            ->getMock();

        $cookie->expects($this->atLeastOnce())->method('get_cookie')->with('lti1p3_' . $state)->willReturn($state);
        /** @var LTI_Message_Launch|\PHPUnit_Framework_MockObject_MockObject */
        $messageLaunch = $this->getMockBuilder('\IMSGlobal\LTI\LTI_Message_Launch')
            ->setMethods(['get_public_key', 'import_validators'])
            ->setConstructorArgs(
                [new DummyDatabase(), new InMemoryCache(), $cookie]
            )
            ->getMock();
        
        
        $messageLaunch->expects($this->atLeastOnce())
            ->method('get_public_key')
            ->willReturn(['key' => $this->publicKey]);

        $messageLaunch->expects($this->once())->method('import_validators')->willReturnCallback(
            function () {
                foreach (glob(__DIR__ . '/helpers/validators/*.php') as $filename) {
                    include_once $filename;
                }                
            }
        );

        $messageLaunch->validate(['state' => $state, 'id_token' => $jwt]);     
    }        
}