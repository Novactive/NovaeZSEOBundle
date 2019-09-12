<?php
/**
 * NovaeZSEOBundle Bundle.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use Exception;
use eZ\Publish\API\Repository\URLWildcardService;
use eZ\Publish\API\Repository\Values\Content\URLWildcard;
use eZ\Publish\API\Repository\Values\Content\URLWildcardTranslationResult;
use RuntimeException;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

class UrlWildcardRouter implements RequestMatcherInterface, ChainedRouterInterface
{
    const URL_WILDCARD_ROUTE_NAME = 'ez_urlwildcard';

    /**
     * @var RequestContext
     */
    protected $requestContext;

    /**
     * @var URLWildcardService
     */
    protected $urlWildcardService;

    public function __construct(URLWildcardService $urlWildcardService)
    {
        $this->urlWildcardService = $urlWildcardService;
    }

    public function setContext(RequestContext $context): void
    {
        $this->requestContext = $context;
    }

    public function getContext(): RequestContext
    {
        return $this->requestContext;
    }

    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        return '';
    }

    public function match($pathinfo)
    {
        throw new RuntimeException(
            "The UrlWildcardRouter doesn't support the match() method. Please use matchRequest() instead."
        );
    }

    public function supports($name): bool
    {
        return $name instanceof URLWildcard || self::URL_WILDCARD_ROUTE_NAME === $name;
    }

    public function getRouteDebugMessage($name, array $parameters = []): string
    {
        if ($name instanceof RouteObjectInterface) {
            return 'Route with key '.$name->getRouteKey();
        }

        if ($name instanceof SymfonyRoute) {
            return 'Route with pattern '.$name->getPath();
        }

        return $name;
    }

    public function matchRequest(Request $request): ?array
    {
        try {
            $requestedPath = $request->attributes->get('semanticPathinfo', $request->getPathInfo());
            $urlWildcard   = $this->getUrlWildcard($requestedPath);

            $params = [
                '_route' => self::URL_WILDCARD_ROUTE_NAME,
            ];

            // In URLAlias terms, "forward" means "redirect".
            if ($urlWildcard->forward) {
                $params += [ 'needsRedirect' => true ];
            } else {
                $params += [ 'needsForward' => true ];
            }

            if (substr($urlWildcard->uri, 0, 7) === "http://" || substr($urlWildcard->uri, 0, 8) === "https://") {
                $params += [ 'semanticPathinfo' => trim($urlWildcard->uri, '/') ];
            } else {
                $params += [ 'semanticPathinfo' => '/'.trim($urlWildcard->uri, '/') ];
            }

            return $params;
        } catch (Exception $e) {
            throw new ResourceNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function getUrlWildcard($pathinfo): URLWildcardTranslationResult
    {
        return $this->urlWildcardService->translate($pathinfo);
    }
}
