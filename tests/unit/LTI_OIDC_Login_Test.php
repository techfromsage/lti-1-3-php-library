<?php

namespace IMSGlobal\LTI\Tests\unit;

use IMSGlobal\LTI\Cookie;
use IMSGlobal\LTI\LTI_OIDC_Login;
use IMSGlobal\LTI\Redirect;
use IMSGlobal\LTI\Tests\unit\helpers\DummyDatabase;
use IMSGlobal\LTI\Tests\unit\helpers\InMemoryCache;

class LTI_OIDC_Login_Test extends TestBase
{
    private $launchUrl = 'https://example.com/lti1p3/launch';

    public function testNewInstance()
    {
        $this->assertInstanceOf(LTI_OIDC_Login::class, LTI_OIDC_Login::newInstance(
            new DummyDatabase()
        ));
    }

    public function testDoOidcLoginRedirectEmptyLaunchUrl()
    {
        $this->setExpectedException('IMSGlobal\LTI\OIDC_Exception', 'No launch URL configured');

        LTI_OIDC_Login::newInstance(new DummyDatabase())->do_oidc_login_redirect('');
    }

    public function testValidateOidcLoginNoIssuer()
    {
        $this->setExpectedException('IMSGlobal\LTI\OIDC_Exception', 'Could not find issuer');
        LTI_OIDC_Login::newInstance(new DummyDatabase())->do_oidc_login_redirect(
            $this->launchUrl,
            []
        );
    }

    public function testValidateOidcLoginNoPasswordHint()
    {
        $this->setExpectedException('IMSGlobal\LTI\OIDC_Exception', 'Could not find login hint');
        LTI_OIDC_Login::newInstance(new DummyDatabase())->do_oidc_login_redirect(
            $this->launchUrl,
            ['iss' => 'aaa']
        );
    }

    public function testValidateOidcLoginRegistrationNotFound()
    {
        $this->setExpectedException('IMSGlobal\LTI\OIDC_Exception', 'Could not find registration details');

        /** @var DummyDatabase|\PHPUnit_Framework_MockObject_MockObject */
        $registrationDatabase = $this->getMockBuilder(DummyDatabase::class)
            ->setMethods(['find_registration_by_issuer'])
            ->getMock();

        $registrationDatabase->expects($this->atLeastOnce())
            ->method('find_registration_by_issuer')
            ->with('xyz', null)
            ->willReturn(null);

        LTI_OIDC_Login::newInstance($registrationDatabase)->do_oidc_login_redirect(
            $this->launchUrl,
            [
                'iss' => 'xyz',
                'login_hint' => 'password123'
            ]
        );
    }

    public function testDoOidcLoginRedirect()
    {
        /** @var InMemoryCache|\PHPUnit_Framework_MockObject_MockObject */
        $cache = $this->getMockBuilder(InMemoryCache::class)
            ->setMethods(['cache_nonce'])
            ->getMock();

        $cache->expects($this->once())->method('cache_nonce')->with(
            $this->stringStartsWith('nonce-')
        );

        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['set_cookie'])
            ->getMock();

        $cookie->expects($this->once())->method('set_cookie')->with(
            $this->stringStartsWith('lti1p3_state-'),
            $this->stringStartsWith('state-')
        )->willReturn($cookie);

        $redirect = LTI_OIDC_Login::newInstance(
            new DummyDatabase(),
            $cache,
            $cookie
        )->do_oidc_login_redirect(
            $this->launchUrl,
            [
                'iss' => 'aaa',
                'login_hint' => 'password123',
                'lti_message_hint' => 'my message hint'
            ]
        );

        $this->assertInstanceOf(Redirect::class, $redirect);

        $redirectUrl = parse_url($redirect->get_redirect_url());

        $this->assertEquals('https', $redirectUrl['scheme']);
        $this->assertEquals('example.com', $redirectUrl['host']);
        $this->assertEquals('/lti1p3/aaa/12345/auth_login_url', $redirectUrl['path']);
        parse_str($redirectUrl['query'], $query);
        $this->assertEquals('openid', $query['scope']);
        $this->assertEquals('id_token', $query['response_type']);
        $this->assertEquals('form_post', $query['response_mode']);
        $this->assertEquals('none', $query['prompt']);
        $this->assertEquals('12345', $query['client_id']);
        $this->assertEquals($this->launchUrl, $query['redirect_uri']);
        $this->assertStringStartsWith('state-', $query['state']);
        $this->assertStringStartsWith('nonce-', $query['nonce']);
        $this->assertEquals('password123', $query['login_hint']);
        $this->assertEquals('my message hint', $query['lti_message_hint']);
    }

    /**
     * @dataProvider clientIdProvider
     * @param array $request The request array to use for the test
     */
    public function testClientIdIsSetOnOidcLogin(array $request)
    {
        /** @var InMemoryCache|\PHPUnit_Framework_MockObject_MockObject */
        $cache = $this->getMockBuilder(InMemoryCache::class)
            ->setMethods(['cache_nonce'])
            ->getMock();

        $cache->expects($this->once())->method('cache_nonce')->with(
            $this->stringStartsWith('nonce-')
        );

        /** @var Cookie|\PHPUnit_Framework_MockObject_MockObject $cookie */
        $cookie = $this->getMockBuilder(Cookie::class)
            ->setMethods(['set_cookie'])
            ->getMock();

        $cookie->expects($this->once())->method('set_cookie')->with(
            $this->stringStartsWith('lti1p3_state-'),
            $this->stringStartsWith('state-')
        )->willReturn($cookie);

        $redirect = LTI_OIDC_Login::newInstance(
            new DummyDatabase(),
            $cache,
            $cookie
        )->do_oidc_login_redirect(
            $this->launchUrl,
            $request
        );

        $expectedId = isset($request['client_id']) ? $request['client_id'] : $request['aud'];

        $this->assertInstanceOf(Redirect::class, $redirect);

        $redirectUrl = parse_url($redirect->get_redirect_url());

        parse_str($redirectUrl['query'], $query);
        $this->assertEquals($expectedId, $query['client_id']);
    }

    public function clientIdProvider()
    {
        return [
            'clientId as client_id' => [
                [
                    'iss' => 'aaa',
                    'login_hint' => 'password123',
                    'lti_message_hint' => 'my message hint',
                    'client_id' => '12345'
                ]
            ],
            'clientId as aud' => [
                [
                    'iss' => 'bbb',
                    'login_hint' => 'password123',
                    'lti_message_hint' => 'my message hint',
                    'aud' => 'aud_12345'
                ]
            ],
            'clientId and aud present' => [
                [
                    'iss' => 'ccc',
                    'login_hint' => 'password123',
                    'lti_message_hint' => 'my message hint',
                    'aud' => 'aud_12345',
                    'client_id' => '12345'
                ]
            ]
        ];
    }
}
