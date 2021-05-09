<?php

/**
 * @see       https://github.com/mezzio/mezzio-csrf for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-csrf/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-csrf/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Csrf;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Create a CSRF guard and inject it as a request attribute.
 *
 * Uses the provided CsrfGuardFactoryInterface implementation to create a
 * CsrfGuardInterface instance and inject it into the request provided to the
 * delegate.
 *
 * Later middleware can then access the CsrfGuardInterface instance in order to
 * either generate or validate a token.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    public const GUARD_ATTRIBUTE = 'csrf';

    /** @var string */
    private $attributeKey;

    /** @var CsrfGuardFactoryInterface */
    private $guardFactory;

    public function __construct(
        CsrfGuardFactoryInterface $guardFactory,
        string $attributeKey = self::GUARD_ATTRIBUTE
    ) {
        $this->guardFactory = $guardFactory;
        $this->attributeKey = $attributeKey;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $guard = $this->guardFactory->createGuardFromRequest($request);
        return $handler->handle($request->withAttribute($this->attributeKey, $guard));
    }
}
