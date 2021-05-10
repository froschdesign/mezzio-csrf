<?php

declare(strict_types=1);

namespace MezzioTest\Csrf;

use Mezzio\Csrf\SessionCsrfGuard;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class SessionCsrfGuardTest extends TestCase
{
    public function setUp()
    {
        $this->session = $this->prophesize(SessionInterface::class);
        $this->guard = new SessionCsrfGuard($this->session->reveal());
    }

    public function keyNameProvider() : array
    {
        return [
            'default' => ['__csrf'],
            'custom'  => ['CSRF'],
        ];
    }

    /**
     * @dataProvider keyNameProvider
     */
    public function testGenerateTokenStoresTokenInSessionAndReturnsIt(string $keyName)
    {
        $expected = '';
        $this->session
            ->set(
                $keyName,
                Argument::that(function ($token) use (&$expected) {
                    $this->assertRegExp('/^[a-f0-9]{32}$/', $token);
                    $expected = $token;
                    return $token;
                })
            )
            ->shouldBeCalled();

        $token = $this->guard->generateToken($keyName);
        $this->assertSame($expected, $token);
    }

    public function tokenValidationProvider() : array
    {
        // @codingStandardsIgnoreStart
        return [
            // case                  => [token,   key,      session token, assertion    ]
            'default-not-found'      => ['token', '__csrf', '',            'assertFalse'],
            'default-found-not-same' => ['token', '__csrf', 'different',   'assertFalse'],
            'default-found-same'     => ['token', '__csrf', 'token',       'assertTrue'],
            'custom-not-found'       => ['token', 'CSRF',   '',            'assertFalse'],
            'custom-found-not-same'  => ['token', 'CSRF',   'different',   'assertFalse'],
            'custom-found-same'      => ['token', 'CSRF',   'token',       'assertTrue'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider tokenValidationProvider
     */
    public function testValidateTokenValidatesProvidedTokenAgainstOneStoredInSession(
        string $token,
        string $csrfKey,
        string $sessionTokenValue,
        string $assertion
    ) {
        $this->session->get($csrfKey, '')->willReturn($sessionTokenValue);
        $this->session->unset($csrfKey)->shouldBeCalled();
        $this->$assertion($this->guard->validateToken($token, $csrfKey));
    }
}
