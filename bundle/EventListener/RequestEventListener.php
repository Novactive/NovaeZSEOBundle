<?php

/**
 * NovaeZSEOBundle RequestEventListener.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <mohamed-larbi.jebari-ext@almaviacx.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\EventListener;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class RequestEventListener implements EventSubscriberInterface
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    private $configResolver;

    /** @var string */
    private $defaultSiteAccess;

    /** @var \Symfony\Component\Routing\RouterInterface */
    private $router;

    public function __construct(ConfigResolverInterface $configResolver, RouterInterface $router, $defaultSiteAccess, LoggerInterface $logger = null)
    {
        $this->configResolver = $configResolver;
        $this->defaultSiteAccess = $defaultSiteAccess;
        $this->router = $router;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequestRedirect', 10],
            ],
        ];
    }

    /**
     * Checks if the request needs to be redirected and return a RedirectResponse in such case.
     * The request attributes "needsSeoRedirect" and "semanticPathinfo" are originally set in the UrlAliasRouter.
     *
     * Note: The event propagation will be stopped to ensure that no response can be set later and override the redirection.
     *
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     *
     * @see \Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter
     */
    public function onKernelRequestRedirect(RequestEvent $event)
    {
        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
            $request = $event->getRequest();
            if ($request->attributes->get('needsSeoRedirect') && $request->attributes->has('semanticPathinfo')) {
                $siteaccess = $request->attributes->get('siteaccess');
                $semanticPathinfo = $request->attributes->get('semanticPathinfo');
                $queryString = $request->getQueryString();
                if (
                    $request->attributes->get('prependSiteaccessOnRedirect', true)
                    && $siteaccess instanceof SiteAccess
                    && $siteaccess->matcher instanceof URILexer
                ) {
                    $semanticPathinfo = $siteaccess->matcher->analyseLink($semanticPathinfo);
                }

                $headers = [];
                if ($request->attributes->has('locationId')) {
                    $headers['X-Location-Id'] = $request->attributes->get('locationId');
                }

                try {
                    $queryParameters = [];
                    $output = [];
                    parse_str(parse_url($semanticPathinfo, PHP_URL_QUERY), $output);
                    $queryParameters += $output;
                    parse_str($queryString, $output);
                    $queryParameters += $output;
                    $semanticPathinfo = trim(explode('?', $semanticPathinfo, 2)[0], '/');
                    $redirectTo = $semanticPathinfo . ($queryParameters ? '?'.http_build_query($queryParameters) : '');
                } catch (\Throwable){
                    $redirectTo = $semanticPathinfo . ($queryString ? "?$queryString" : '');
                }

                $event->setResponse(
                    new RedirectResponse(
                        $redirectTo,
                        301,
                        $headers
                    )
                );
                $event->stopPropagation();

                if (isset($this->logger)) {
                    $this->logger->info(
                        "URLAlias made request to be redirected to $semanticPathinfo",
                        ['pathinfo' => $request->getPathInfo()]
                    );
                }
            }
        }
    }
}
