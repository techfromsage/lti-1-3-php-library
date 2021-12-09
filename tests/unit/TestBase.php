<?php

namespace IMSGlobal\LTI\Tests\unit;

use PHPUnit_Framework_TestCase;

abstract class TestBase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $className = basename(get_class($this));
        $testName = $this->getName();
        echo "  Test: {$className}->{$testName}\n";        
    }    
}
