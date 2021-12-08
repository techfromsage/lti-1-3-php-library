<?php

namespace IMSGlobal\LTI\Tests\unit\helpers;

use IMSGlobal\LTI\LTI_Registration;
use PHPUnit_Framework_MockObject_MockObject;

trait MockLTIRegistration {
    /**
     * Generates a mock LTI registration
     * 
     * @return PHPUnit_Framework_MockObject_MockObject|\IMSGlobal\LTI\LTI_Registration
     * 
     * @throws PHPUnit_Framework_Exception 
     */
    protected function getMockLTIRegistration()
    {
        return $this->getMockBuilder(LTI_Registration::class)
            ->setMethods(['get_kid', 'get_tool_private_key'])
            ->getMock();
    }    
}