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
use Novactive\Bundle\eZSEOBundle\Core\Repository\Values\Content\URLWildcard\Query\Criterion\ExactSourceUrl;
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
    private const PLACEHOLDERS_REGEXP = '(\{(\d+)\})';

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
                ['onKernelRequest', 1000],
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
        $criteria = [
            new SourceUrl('*'),
            new ExactSourceUrl($requestUriFull),
            new ExactSourceUrl($requestUri),
            new ExactSourceUrl($requestedPathFull),
            new ExactSourceUrl($requestedPath),
        ];
        if (!empty(trim($requestUri, '/ '))) {
            $criteria[] = new ExactSourceUrl(trim($requestUri, '/ '));
        }
        if (!empty(trim($requestedPath, '/ '))) {
            $criteria[] = new ExactSourceUrl(trim($requestedPath, '/ '));
        }

        $query->filter = new LogicalOr(
            $criteria
        );
        $query->limit = 100;

        $urlWildcards = $this->wildcardService->findUrlWildcards($query)->items;

        // sort to check exact matches first
        usort($urlWildcards, function (URLWildcard $urlA, URLWildcard $urlB) {
            return strpos($urlA->sourceUrl, '*') <=> strpos($urlB->sourceUrl, '*');
        });

        $urlWildcard = $this->getFirstMatchedUrl(
            [
                $requestUriFull,
                $requestUri,
                $requestedPathFull,
                $requestedPath,
            ],
            $urlWildcards
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
                $uri = $this->match($needle, $urlWildcard);
                if ($uri) {
                    return new UrlWildcard(
                        [
                                               'id' => $urlWildcard->id,
                                               'destinationUrl' => $uri,
                                               'sourceUrl' => $urlWildcard->sourceUrl,
                                               'forward' => $urlWildcard->forward,
                                           ]
                    );
                }
            }
        }

        return null;
    }

    /**
     * Tests if the given url matches against the given url wildcard.
     *
     * if the wildcard matches on the given url this method will return a ready
     * to use destination url, otherwise this method will return <b>NULL</b>.
     *
     * @param array $wildcard
     */
    private function match(string $url, URLWildcard $wildcard): ?string
    {
        if (preg_match($this->compile($wildcard->sourceUrl), $url, $match)) {
            return $this->substitute($wildcard->destinationUrl, $match);
        }

        return null;
    }

    /**
     * Compiles the given url pattern into a regular expression.
     */
    private function compile(string $sourceUrl): string
    {
        return '(^'.str_replace('\\*', '(.*)', preg_quote($sourceUrl)).'$)U';
    }

    /**
     * Substitutes all placeholders ({\d}) in the given <b>$destinationUrl</b> with
     * the values from the given <b>$values</b> array.
     */
    private function substitute(string $destinationUrl, array $values): string
    {
        preg_match_all(self::PLACEHOLDERS_REGEXP, $destinationUrl, $matches);

        foreach ($matches[1] as $match) {
            $destinationUrl = str_replace("{{$match}}", $values[$match], $destinationUrl);
        }

        return $destinationUrl;
    }
}
