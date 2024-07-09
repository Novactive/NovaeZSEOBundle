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
use Ibexa\Contracts\Core\Repository\URLWildcardService;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\Generator\RouteReferenceGenerator;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\MVC\Symfony\Routing\UrlWildcardRouter as BaseUrlWildcardRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;

class UrlWildcardRouter extends BaseUrlWildcardRouter
{
    /** @var URLWildcardService */
    private $wildcardService;

    /** @var RouteReferenceGenerator */
    private $routeReferenceGenerator;
    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @required
     */
    public function setWildcardService(URLWildcardService $wildcardService): void
    {
        $this->wildcardService = $wildcardService;
    }

    /**
     * @required
     */
    public function setRouteReferenceGenerator(RouteReferenceGenerator $routeReferenceGenerator): void
    {
        $this->routeReferenceGenerator = $routeReferenceGenerator;
    }

    /**
     * @required
     */
    public function setConfigResolverInterface(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @required
     */
    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }

    public function matchRequest(Request $request): array
    {
        // Manage full url : http://host.com/uri
        $requestedPath = $request->attributes->get('semanticPathinfo', $request->getPathInfo());
        try {
            // Manage full url with site Access  : http://host.com/{siteAccess}/uri
            if (
                $request->attributes->has('siteaccess') &&
                $request->attributes->get('siteaccess') instanceof SiteAccess
            ) {
                /** @var SiteAccess $siteAccess */
                $siteAccess = $request->attributes->get('siteaccess');
                $locationId = $this->configResolver
                    ->getParameter('content.tree_root.location_id', null, $siteAccess->name);
                $routeReference = $this->routeReferenceGenerator->generate(
                    UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                    [
                    'siteaccess' => $siteAccess->name,
                    'locationId' => $locationId,
                    ]
                );
                $requestUriFull = $this->router->generate(
                        $routeReference->getRoute(),
                        $routeReference->getParams(),
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ).$requestedPath;
            } else {
                $requestUriFull = $request->getSchemeAndHttpHost().$requestedPath;
            }
            $urlWildcard = $this->wildcardService->translate($requestUriFull);
        } catch (Exception $e) {
            try {
                // Manage full url : /uri
                $urlWildcard = $this->wildcardService->translate($requestedPath);
            } catch (Exception $e) {
                throw new ResourceNotFoundException($e->getMessage(), $e->getCode(), $e);
            }
        }

        $params = [
            '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
        ];
        //External URL
        if (0 === strpos($urlWildcard->uri, 'http://') || 'https://' === substr($urlWildcard->uri, 0, 8)) {
            $params += ['semanticPathinfo' => trim($urlWildcard->uri, '/')];
            $params += ['prependSiteaccessOnRedirect' => false];
        } else {
            $params += ['semanticPathinfo' => '/'.trim($urlWildcard->uri, '/')];
        }

        // In URLAlias terms, "forward" means "redirect".
        if ($urlWildcard->forward) {
            $params += ['needsRedirect' => true];
        } else {
            $params += ['needsForward' => true];
        }

        return $params;
    }
}
