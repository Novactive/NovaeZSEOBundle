<?php

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Core\Helper;

use Exception;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverter;
use Ibexa\Core\MVC\Symfony\Routing\Generator\RouteReferenceGenerator;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Ibexa\Migration\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class SiteMapHelper
{
    use LoggerAwareTrait;

    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var RouteReferenceGenerator
     */
    private $routeReferenceGenerator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LocaleConverter
     */
    private $localeConverter;

    /**
     * @var SiteAccessServiceInterface
     */
    private $siteAccessService;

    public function __construct(
        ConfigResolverInterface $configResolver,
        SiteAccessServiceInterface $siteAccessService,
        RouteReferenceGenerator $routeReferenceGenerator,
        RouterInterface $router,
        LocaleConverter $localeConverter,
        ?LoggerInterface $logger = null
    ) {
        $this->configResolver = $configResolver;
        $this->siteAccessService = $siteAccessService;
        $this->routeReferenceGenerator = $routeReferenceGenerator;
        $this->router = $router;
        $this->localeConverter = $localeConverter;
        $this->logger = $logger ?? new NullLogger();
    }

    public function generateLocationUrl(
        int $locationId,
        string $siteAccess = null
    ): ?string {
        try {
            $routeParams['locationId'] = $locationId;
            $routeReference = $this->routeReferenceGenerator->generate(
                UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                $routeParams
            );
            if ($siteAccess) {
                $routeReference->set('siteaccess', $siteAccess);
            }
            $url = $this->router->generate(
                UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                $routeReference->getParams(),
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (Throwable $exception) {
            $this->logger->error('NovaeZSEO: ' . $exception->getMessage());
            $url = null;
        }

        return $url;
    }
    public function generateRouteUrl(
        string $routeName,
        string $siteAccess = null,
        array $parameters = []
    ): ?string {
        try {
            $url = $this->router->generate(
                $routeName,
                [...['siteaccess' => $siteAccess], ...$parameters],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (Throwable $exception) {
            $this->logger->error('NovaeZSEO: ' . $exception->getMessage());
            $url = null;
        }

        return $url;
    }
    public function getSiteAccessesLocationIdLanguages(): array
    {
        $rootLocationLanguages = [];
        $siteAccesses = $this->configResolver->getParameter('translation_siteaccesses');
        foreach ($siteAccesses as $siteAccess) {
            $rootLocationLanguages[$siteAccess] = [
                'rootLocationId' =>  $this->getSiteAccessRootLocationId($siteAccess),
                'mainLanguage' => $this->getSiteAccessMainLanguage($siteAccess),
                'languages' => $this->getSiteAccessLanguages($siteAccess)
            ];
        }

        return $rootLocationLanguages;
    }

    public function getCurrentSiteAccess(): ?string
    {
        return $this->siteAccessService->getCurrent()?->name;
    }

    public function getCurrentSiteAccessRootLocationId(): ?int
    {
        return $this->getSiteAccessRootLocationId($this->getCurrentSiteAccess());
    }
    public function getCurrentSiteAccessMainLanguage(): ?string
    {
        return $this->getSiteAccessMainLanguage($this->getCurrentSiteAccess());
    }

    public function getSiteAccessRootLocationId(string $siteAccess): ?int
    {
        return $this->configResolver->getParameter('content.tree_root.location_id', null, $siteAccess);
    }

    public function getSiteAccessLanguages(string $siteAccess): array
    {
        return (array) $this->configResolver->getParameter('languages', null, $siteAccess);
    }

    public function getSiteAccessMainLanguage(string $siteAccess): string
    {
        $languages = $this->configResolver->getParameter('languages', null, $siteAccess);
        return array_shift($languages);
    }

    public function getHrefLang(string $languageCode): string
    {
        return str_replace(
            '_',
            '-',
            ($this->localeConverter->convertToPOSIX($languageCode) ?? '')
        );
    }

    public function logException(Exception $exception): void
    {
        $this->logger?->error('NovaeZSEO: ' . $exception->getMessage());
    }
}
