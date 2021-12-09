<?php

namespace IMSGlobal\LTI\Tests\unit;

use Firebase\JWT\JWT;
use IMSGlobal\LTI\LTI_Deep_Link;
use IMSGlobal\LTI\LTI_Deep_Link_Resource;
use IMSGlobal\LTI\Tests\unit\helpers\MockLTIRegistration;

class LTI_Deep_Link_Test extends TestBase {
    private $privateKey;
    private $publicKey;

    use MockLTIRegistration;

    public function setUp()
    {
        $this->publicKey = file_get_contents(dirname(__FILE__) . '/fixtures/private.pub');
        $this->privateKey = file_get_contents(dirname(__FILE__) . '/fixtures/private.key');
        parent::setUp();
    }    

    public function testGetResponseJWT() {
        $registration = $this->getMockLTIRegistration();
        $registration->expects($this->once())->method('get_kid')->willReturn('my_kid');
        $registration->expects($this->once())->method('get_tool_private_key')->willReturn($this->privateKey);

        $issuer = uniqid();
        $clientId = uniqid();

        $registration->set_issuer($issuer);
        $registration->set_client_id($clientId);

        $deploymentId = uniqid();
        $deepLinkSettings = [
            'deep_link_return_url' => 'https://example.com/lti-platform/1234',
            'accept_types' => ['ltiResourceLink'],
            'accept_presentation_document_targets' => ['iframe', 'window'],
            'data' => uniqid()
        ];

        $deepLink = new LTI_Deep_Link($registration, $deploymentId, $deepLinkSettings);

        $deepLinkResource = LTI_Deep_Link_Resource::newInstance()
            ->set_title('My Resource')
            ->set_url('https://example.com');

        $jwt = $deepLink->get_response_jwt([$deepLinkResource]);

        $decodedJWT = JWT::decode($jwt, $this->publicKey, ['RS256']);

        $this->assertEquals($decodedJWT->iss, $clientId);
        $this->assertEquals($decodedJWT->aud, [$issuer]);
        $this->assertInternalType('integer', $decodedJWT->exp);
        $this->assertInternalType('integer', $decodedJWT->exp);
        $this->assertInternalType('string', $decodedJWT->nonce);
        $this->assertNotEmpty($decodedJWT->nonce);
        $this->assertEquals($deploymentId, $decodedJWT->{'https://purl.imsglobal.org/spec/lti/claim/deployment_id'});
        $this->assertEquals(
            'LtiDeepLinkingResponse', 
            $decodedJWT->{'https://purl.imsglobal.org/spec/lti/claim/message_type'}
        );
        $this->assertEquals(
            '1.3.0', 
            $decodedJWT->{'https://purl.imsglobal.org/spec/lti/claim/version'}
        );    
        $this->assertInternalType(
            'array', 
            $decodedJWT->{'https://purl.imsglobal.org/spec/lti-dl/claim/content_items'}
        );            

        $contentItems = $decodedJWT->{'https://purl.imsglobal.org/spec/lti-dl/claim/content_items'};
        $this->assertCount(1, $contentItems);
        

        $this->assertEquals('ltiResourceLink', $contentItems[0]->type);
        $this->assertEquals('My Resource', $contentItems[0]->title);
        $this->assertEquals('https://example.com', $contentItems[0]->url);
        $this->assertEquals(
            $deepLinkSettings['data'], 
            $decodedJWT->{'https://purl.imsglobal.org/spec/lti-dl/claim/data'}
        );        
    }
}
