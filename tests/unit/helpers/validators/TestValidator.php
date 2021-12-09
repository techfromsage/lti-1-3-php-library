<?php

namespace IMSGlobal\LTI\Tests\unit\helpers\validators;

use IMSGlobal\LTI\Message_Validator;

class TestValidator implements Message_Validator {
    public function can_validate($jwt_body)
    {
        return true;
    }

    public function validate($jwt_body)
    {
        return !(isset($jwt_body['is_valid']) && $jwt_body['is_valid'] === false);
    }
}
