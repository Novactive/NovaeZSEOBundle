<?php

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Listener;

use Ibexa\Contracts\Core\Repository\URLWildcardService;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion\LogicalOr;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion\SourceUrl;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\URLWildcardQuery;
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
    public function __construct(
        protected URLWildcardService $wildcardService,
        protected ConfigResolverInterface $configResolver,
        protected RouterInterface $router,
        protected $defaultSiteAccess,
        protected ?LoggerInterface $logger = null
    ) {
        $this->configResolver = $configResolver;
        $this->defaultSiteAccess = $defaultSiteAccess;
        $this->router = $router;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 0],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        // Manage full url : http://host.com/uri
        $requestedPath = $request->getPathInfo();
        $requestUri = urldecode($request->getRequestUri());
        $requestedPathFull = $request->getSchemeAndHttpHost().$requestedPath;
        $requestUriFull = $request->getSchemeAndHttpHost().$requestUri;

        $query = new URLWildcardQuery();
        $query->filter = new LogicalOr(
            [
                new SourceUrl($requestedPath),
            ]
        );

        $urlWildcards = $this->wildcardService->findUrlWildcards($query);
        $urlWildcard = $this->getFirstMatchedUrl(
            [
                $requestUriFull,
                $requestUri,
                $requestedPathFull,
                $requestedPath,
            ],
            $urlWildcards->items
        );

        if (!$urlWildcard) {
            return;
        }

        $prependSiteaccessOnRedirect = true;
        if (
            0 === strpos($urlWildcard->destinationUrl, 'http://') ||
            'https://' === substr($urlWildcard->destinationUrl, 0, 8)
        ) {
            $destinationUrl = trim($urlWildcard->destinationUrl, '/');
            $prependSiteaccessOnRedirect = false;
        } else {
            $destinationUrl = '/'.trim($urlWildcard->destinationUrl, '/');
        }

        // In URLAlias terms, "forward" means "redirect".
        if ($urlWildcard->forward) {
            $this->onKernelRequestRedirect(
                $event,
                $request,
                $destinationUrl,
                $prependSiteaccessOnRedirect
            );
        } else {
            $this->onKernelRequestForward(
                $event,
                $request,
                $destinationUrl
            );
        }
    }

    protected function onKernelRequestForward(RequestEvent $event, Request $request, string $destinationUrl)
    {
        $request->attributes->remove('needsForward');
        $forwardRequest = Request::create(
            $destinationUrl,
            $request->getMethod(),
            'POST' === $request->getMethod() ? $request->request->all() : $request->query->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );

        if ($request->attributes->has('forwardRequestHeaders')) {
            foreach ($request->attributes->get('forwardRequestHeaders') as $headerName => $headerValue) {
                $forwardRequest->headers->set($headerName, $headerValue);
            }
            $request->attributes->remove('forwardRequestHeaders');
        }

        $forwardRequest->attributes->add($request->attributes->all());

        // Not forcing HttpKernelInterface::SUB_REQUEST on purpose since we're very early here
        // and we need to bootstrap essential stuff like sessions.
        $event->setResponse($event->getKernel()->handle($forwardRequest));
        $event->stopPropagation();

        if (isset($this->logger)) {
            $this->logger->info(
                "URLAlias made request to be forwarded to $destinationUrl",
                ['pathinfo' => $request->getPathInfo()]
            );
        }
    }

    /**
     * Checks if the request needs to be redirected and return a RedirectResponse in such case.
     *
     * Note: The event propagation will be stopped to ensure that no response can be set later
     * and override the redirection.
     *
     * @see \Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter
     */
    protected function onKernelRequestRedirect(
        RequestEvent $event,
        Request $request,
        string $destinationUrl,
        bool $prependSiteaccessOnRedirect = true
    ) {
        $siteaccess = $request->attributes->get('siteaccess');
        if (
            $prependSiteaccessOnRedirect
            && $siteaccess instanceof SiteAccess
            && $siteaccess->matcher instanceof URILexer
        ) {
            $destinationUrl = $siteaccess->matcher->analyseLink($destinationUrl);
        }

        $headers = [];
        if ($request->attributes->has('locationId')) {
            $headers['X-Location-Id'] = $request->attributes->get('locationId');
        }
        $event->setResponse(
            new RedirectResponse(
                $destinationUrl,
                301,
                $headers
            )
        );
        $event->stopPropagation();

        if (isset($this->logger)) {
            $this->logger->info(
                "URLAlias made request to be redirected to $destinationUrl",
                ['pathinfo' => $request->getPathInfo()]
            );
        }
    }

    /**
     * @param string[]      $needles
     * @param URLWildcard[] $urlWildcards
     *
     * @return \Novactive\Bundle\eZSEOBundle\Core\URLWildcard|null
     */
    protected function getFirstMatchedUrl(array $needles, array $urlWildcards): ?URLWildcard
    {
        foreach ($needles as $needle) {
            foreach ($urlWildcards as $urlWildcard) {
                if ($urlWildcard->sourceUrl === $needle) {
                    return $urlWildcard;
                }
            }
        }

        return null;
    }
}
