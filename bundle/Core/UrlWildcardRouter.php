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
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\MVC\Symfony\Routing\UrlWildcardRouter as BaseUrlWildcardRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class UrlWildcardRouter extends BaseUrlWildcardRouter
{
    /** @var URLWildcardService */
    private $wildcardService;

    public function setWildcardService(URLWildcardService $wildcardService): void
    {
        $this->wildcardService = $wildcardService;
    }

    public function matchRequest(Request $request): array
    {
        try {
            $requestedPath = $request->attributes->get('semanticPathinfo', $request->getPathInfo());
            $urlWildcard = $this->wildcardService->translate($requestedPath);

            $params = [
                '_route' => UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            ];

            if (0 === strpos($urlWildcard->uri, 'http://') || 'https://' === substr($urlWildcard->uri, 0, 8)) {
                $params += ['semanticPathinfo' => trim($urlWildcard->uri, '/')];
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
        } catch (Exception $e) {
            throw new ResourceNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
