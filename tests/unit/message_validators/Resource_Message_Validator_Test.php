<?php

namespace IMSGlobal\LTI\Tests\unit\message_validators;

use IMSGlobal\LTI\Resource_Message_Validator;
use IMSGlobal\LTI\Tests\unit\TestBase;

class Resource_Message_Validator_Test extends TestBase {
    private $jwtBody = [];
    public function setUp() {
        parent::setUp();
        $this->jwtBody = [
            'sub' => 'lti_user_123',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/roles' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty',
            'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                'id' => 'my_id_abc_123'
            ]
        ];
    }
    /**
     * 
     * @param array   $jwtBody 
     * @param boolean $expectedResponse 
     * 
     * @return void 
     * 
     * @dataProvider canValidateProvider
     */
    public function testCanValidate(array $jwtBody, $expectedResponse)
    {
        $validator = new Resource_Message_Validator();
        $this->assertEquals($expectedResponse, $validator->can_validate($jwtBody));
    }

    public function canValidateProvider()
    {
        return [
            'empty jwt' => [[], false],
            'LtiDeepLinkingRequest' => [['https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingRequest'], false],
            'LtiResourceLinkRequest' => [['https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest'], true],
            'LtiSubmissionReviewRequest' => [['https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiSubmissionReviewRequest'], false],
            'invalid request type' => [['https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiWibbleRequest'], false],
        ];
    }

    /**
     * 
     * @param string $propertyToReplace
     * @param mixed  $replacementValue
     * @param string $expectedExceptionMessage 
     * 
     * @return void 
     * @throws LTI_Exception 
     * 
     * @dataProvider validateJwtBodyProvider
     */
    public function testValidateJwtBody($propertyToReplace, $replacementValue, $expectedExceptionMessage)
    {
        $this->setExpectedException('IMSGlobal\LTI\LTI_Exception', $expectedExceptionMessage);

        $validator = new Resource_Message_Validator();
        $this->jwtBody[$propertyToReplace] = $replacementValue;

        $validator->validate($this->jwtBody);
    }

    public function validateJwtBodyProvider()
    {
        return [
            'lti version' => ['https://purl.imsglobal.org/spec/lti/claim/version', null, 'Incorrect version, expected 1.3.0'],
            'lti version 1.0' => ['https://purl.imsglobal.org/spec/lti/claim/version', '1.0', 'Incorrect version, expected 1.3.0'],
            'lti version 1.1' => ['https://purl.imsglobal.org/spec/lti/claim/version', '1.1', 'Incorrect version, expected 1.3.0'],
            'lti version 1.3.1' => ['https://purl.imsglobal.org/spec/lti/claim/version', '1.3.1', 'Incorrect version, expected 1.3.0'],
            'roles' => ['https://purl.imsglobal.org/spec/lti/claim/roles', null, 'Missing Roles Claim'],
            'resource link id' => ['https://purl.imsglobal.org/spec/lti/claim/resource_link', null, 'Missing Resource Link Id']
        ];
    }    
}