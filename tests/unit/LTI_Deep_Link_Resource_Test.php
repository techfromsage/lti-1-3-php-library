<?php

namespace IMSGlobal\LTI\Tests\unit;

use IMSGlobal\LTI\LTI_Deep_Link_Resource;
use PHPUnit_Framework_ExpectationFailedException;

class LTI_Deep_Link_Resource_Test extends TestBase {
    public function testNewInstance()
    {
        $this->assertInstanceOf('IMSGlobal\LTI\LTI_Deep_Link_Resource', LTI_Deep_Link_Resource::newInstance());
    }    

    /**      
     * Tests the setter/getter for deep link resource type
     * 
     * @param string $type Deep link resource type
     * 
     * @throws PHPUnit_Framework_ExpectationFailedException 
     * 
     * @dataProvider deepLinkResourceTypeProvider
     */
    public function testType($type)
    {
        $deepLinkResource = LTI_Deep_Link_Resource::newInstance();

        // default
        $this->assertEquals('ltiResourceLink', $deepLinkResource->get_type());

        $this->assertInstanceOf('IMSGlobal\LTI\LTI_Deep_Link_Resource', $deepLinkResource->set_type($type));
        $this->assertEquals($type, $deepLinkResource->get_type());
    }

    public function deepLinkResourceTypeProvider()
    {
        return [
            ['file'],
            ['image'],
            ['html'],
            ['link'],
            ['ltiResourceLink']
        ];
    }

    /**
     * Tests the setter/getter for deep link resource target
     * 
     * @param string $target Deep link target value
     * 
     * @throws PHPUnit_Framework_ExpectationFailedException 
     * 
     * @dataProvider deepLinkTargetProvider
     */
    public function testTarget($target)
    {
        $deepLinkResource = LTI_Deep_Link_Resource::newInstance();

        // default
        $this->assertEquals('iframe', $deepLinkResource->get_target());

        $this->assertInstanceOf('IMSGlobal\LTI\LTI_Deep_Link_Resource', $deepLinkResource->set_target($target));
        $this->assertEquals($target, $deepLinkResource->get_target());        
    }

    public function deepLinkTargetProvider()
    {
        return [
            ['embed'],
            ['window'], 
            ['target: self'],
            ['iframe'],
            ['window']
        ];
    }

    public function testPropertyGettersAndSetters()
    {
        $deepLinkResource = LTI_Deep_Link_Resource::newInstance();
        
        $this->assertEmpty($deepLinkResource->get_title());
        $this->assertInstanceOf('IMSGlobal\LTI\LTI_Deep_Link_Resource', $deepLinkResource->set_title('A title'));
        $this->assertEquals('A title', $deepLinkResource->get_title());

        $this->assertEmpty($deepLinkResource->get_url());
        $this->assertInstanceOf(
            'IMSGlobal\LTI\LTI_Deep_Link_Resource', 
            $deepLinkResource->set_url('https://example.com/resource/123')
        );
        $this->assertEquals('https://example.com/resource/123', $deepLinkResource->get_url());        

        // Default
        $this->assertEquals([], $deepLinkResource->get_custom_params());
        $this->assertInstanceOf(
            'IMSGlobal\LTI\LTI_Deep_Link_Resource', 
            $deepLinkResource->set_custom_params(['foo' => 'bar', 'bar' => 'baz'])
        );
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $deepLinkResource->get_custom_params());
    }

    public function testToArray()
    {
        $title = 'Resource title';
        $url = 'https://example.com/resources/1234';
        $customParameters = ['foo' => 'bar', 'bar' => 'baz'];
        $expectedArray = [
            'type' => 'ltiResourceLink',
            'title' => $title,
            'url' => $url,
            'presentation' => [
                'documentTarget' => 'iframe'
            ],        
            'custom' => $customParameters
        ];

        $deepLinkResource = LTI_Deep_Link_Resource::newInstance();
        $deepLinkResource->set_title($title);
        $deepLinkResource->set_url($url);
        $deepLinkResource->set_custom_params($customParameters);

        $this->assertEquals($expectedArray, $deepLinkResource->to_array());
    }
}