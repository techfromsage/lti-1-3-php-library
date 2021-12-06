<?php

namespace IMSGlobal\LTI\Tests\unit\helpers\validators;

use IMSGlobal\LTI\Message_Validator;

class MatchesMoreThanOneValidatorValidator implements Message_Validator {
    public function can_validate($jwt_body)
    {
        return $jwt_body['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiMultipleMatchingValidatorRequest';
    }

    public function validate($jwt_body)
    {
        return false;
    }
}