<?php

namespace IMSGlobal\LTI\Tests\unit;

use IMSGlobal\LTI\LTI_Deployment;

class LTI_Deployment_Test extends TestBase {
    public function testNewInstance()
    {
        $this->assertInstanceOf(LTI_Deployment::class, LTI_Deployment::newInstance());
    }        

    public function testSetGetDeploymentId()
    {
        $deploymentId = uniqid();
        $deployment = LTI_Deployment::newInstance();
        $this->assertInstanceOf(LTI_Deployment::class, $deployment->set_deployment_id($deploymentId));
        $this->assertEquals($deploymentId, $deployment->get_deployment_id());
    }
}
