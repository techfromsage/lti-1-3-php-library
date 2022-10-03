<?php

namespace IMSGlobal\LTI\Tests\unit\message_validators;

include_once dirname(dirname(dirname(dirname(__FILE__)))) . '/src/lti/message_validators/deep_link_message_validator.php';

use IMSGlobal\LTI\Deep_Link_Message_Validator;
use IMSGlobal\LTI\LTI_Exception;
use IMSGlobal\LTI\LTI_Message_Validation_Exception;
use IMSGlobal\LTI\Tests\unit\TestBase;

class Deep_Link_Message_Validator_Test extends TestBase {
    private $jwtBody = [];
    public function setUp() {
        parent::setUp();
        $this->jwtBody = [
            'sub' => 'lti_user_123',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/roles' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty',
            'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings' => [
                'deep_link_return_url' => 'https://example.com/return_url',
                'accept_types' => ['ltiResourceLink'],
                'accept_presentation_document_targets' => ['iframe', 'window']
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
        $validator = new Deep_Link_Message_Validator();
        $this->assertEquals($expectedResponse, $validator->can_validate($jwtBody));
    }

    public function canValidateProvider()
    {
        return [
            'empty jwt' => [[], false],
            'LtiDeepLinkingRequest' => [['https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingRequest'], true],
            'LtiResourceLinkRequest' => [['https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest'], false],
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
        $this->setExpectedException(LTI_Message_Validation_Exception::class, $expectedExceptionMessage);

        $validator = new Deep_Link_Message_Validator();
        $this->jwtBody[$propertyToReplace] = $replacementValue;

        $validator->validate($this->jwtBody);
    }

    public function validateJwtBodyProvider()
    {
        return [
            'sub' => ['sub', null, 'Must have a user (sub)'],
            'lti version' => ['https://purl.imsglobal.org/spec/lti/claim/version', null, 'Incorrect version, expected 1.3.0'],
            'lti version 1.0' => ['https://purl.imsglobal.org/spec/lti/claim/version', '1.0', 'Incorrect version, expected 1.3.0'],
            'lti version 1.1' => ['https://purl.imsglobal.org/spec/lti/claim/version', '1.1', 'Incorrect version, expected 1.3.0'],
            'lti version 1.3.1' => ['https://purl.imsglobal.org/spec/lti/claim/version', '1.3.1', 'Incorrect version, expected 1.3.0'],
            'roles' => ['https://purl.imsglobal.org/spec/lti/claim/roles', null, 'Missing Roles Claim'],
            'deep linking settings' => ['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings', null, 'Missing Deep Linking Settings']
        ];
    }

    /**
     * 
     * @param string $propertyToRemove 
     * @param mixed  $replacementValue
     * @param string $expectedExceptionMessage 
     * 
     * @return void 
     * @throws LTI_Exception 
     * 
     * @dataProvider validateDeepLinkingSettingsProvider
     */
    public function testValidateDeepLinkingSettings($propertyToReplace, $replacementValue, $expectedExceptionMessage)
    {
        $this->setExpectedException(LTI_Message_Validation_Exception::class, $expectedExceptionMessage);

        $validator = new Deep_Link_Message_Validator();
        $this->jwtBody['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings'][$propertyToReplace] = $replacementValue;

        $validator->validate($this->jwtBody);
    }

    public function validateDeepLinkingSettingsProvider()
    {
        return [
            'deep_link_return_url' => ['deep_link_return_url', null, 'Missing Deep Linking Return URL'],
            'empty accept type' => ['accept_types', [], 'Must support resource link placement types'],
            'link accept type' => ['accept_types', ['link'], 'Must support resource link placement types'],
            'empty placement type' => ['accept_presentation_document_targets', [], 'Must support a presentation type']
        ];
    }    
}
