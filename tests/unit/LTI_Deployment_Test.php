<?php

namespace IMSGlobal\LTI\Tests\unit;

use IMSGlobal\LTI\LTI_Deployment;

class LTI_Deployment_Test extends TestBase {
    public function testNewInstance()
    {
        $this->assertInstanceOf('IMSGlobal\LTI\LTI_Deployment', LTI_Deployment::newInstance());
    }        

    public function testSetGetDeploymentId()
    {
        $deploymentId = uniqid();
        $deployment = LTI_Deployment::newInstance();
        $this->assertInstanceOf('IMSGlobal\LTI\LTI_Deployment', $deployment->set_deployment_id($deploymentId));
        $this->assertEquals($deploymentId, $deployment->get_deployment_id());
    }
}