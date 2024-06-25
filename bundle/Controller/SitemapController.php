<?php

/**
 * NovaeZSEOBundle SitemapController.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Controller;

use DateTime;
use DOMDocument;
use DOMElement;
use Ibexa\Bundle\Core\Controller;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\Helper\FieldHelper;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Novactive\Bundle\eZSEOBundle\Core\Helper\SiteMapHelper;
use Novactive\Bundle\eZSEOBundle\Core\Sitemap\QueryFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class SitemapController extends Controller
{
    /** @var FieldHelper */
    private $fieldHelper;

    /** @var VariationHandler */
    protected $imageVariationService;

    /** @var RouterInterface */
    protected $router;

    /** @var SiteMapHelper */
    protected $siteMapHelper;

    /** @var Repository */
    private $repository;

    /**
     * How many in a Sitemap.
     *
     * @var int
     */
    public const PACKET_MAX = 1000;

    public function __construct(
        FieldHelper $fieldHelper,
        VariationHandler $imageVariationService,
        RouterInterface $router,
        SiteMapHelper $siteMapHelper,
        Repository $repository
    ) {
        $this->fieldHelper = $fieldHelper;
        $this->imageVariationService = $imageVariationService;
        $this->router = $router;
        $this->siteMapHelper = $siteMapHelper;
        $this->repository = $repository;
    }

    /**
     * @Route("/sitemap.xml", name="_novaseo_sitemap_index", methods={"GET"})
     */
    public function indexAction(QueryFactory $queryFactory): Response
    {
        $isMultisiteAccess = $this->getConfigResolver()
            ->getParameter('multi_siteaccess_sitemap', 'nova_ezseo') ?? false;
        // Dom Doc
        $sitemap = new DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;
        // Create an index for multi site
        if ($isMultisiteAccess) {
            $root = $sitemap->createElement('sitemapindex');
            $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $sitemap->appendChild($root);

            $this->fillSitemapMultiSiteIndex($sitemap, $root, $queryFactory);
        } else {
            $searchService = $this->getRepository()->getSearchService();
            $query = $queryFactory();
            $query->limit = 0;
            $resultCount = $searchService->findLocations($query)->totalCount;
        // create an index if we are greater than th PACKET_MAX
            if ($resultCount > static::PACKET_MAX) {
                $root = $sitemap->createElement('sitemapindex');
                $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
                $sitemap->appendChild($root);

                $this->fillSitemapIndex($sitemap, $resultCount, $root);
            } else {
                // if we are less or equal than the PACKET_SIZE, redo the search with no limit and list directly the urlmap
                $query->limit = $resultCount;
                $results = $searchService->findLocations($query);
                $root = $sitemap->createElement('urlset');
                $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
                $root->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
                $this->fillSitemap($sitemap, $root, $results);
                $sitemap->appendChild($root);
            }
        }
        $response = new Response($sitemap->saveXML(), 200, ['Content-type' => 'text/xml']);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    /**
     * @Route("/sitemap-{page}.xml", name="_novaseo_sitemap_page", requirements={"page" = "\d+"},
     *                                                             defaults={"page" = 1},
     *                                                             methods={"GET"})
     */
    public function pageAction(QueryFactory $queryFactory, int $page = 1): Response
    {
        $isMultisiteAccess = $this->getConfigResolver()
            ->getParameter('multi_siteaccess_sitemap', 'nova_ezseo') ?? false;

        $sitemap = new DOMDocument('1.0', 'UTF-8');
        $root = $sitemap->createElement('urlset');
        $sitemap->formatOutput = true;
        $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $root->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

        // Create an index for multi site
        if ($isMultisiteAccess) {
            $this->multisiteAccessPage($sitemap, $root, $queryFactory, $page);
        } else {
            $sitemap->appendChild($root);
            $query = $queryFactory();
            $query->limit = static::PACKET_MAX;
            $query->offset = static::PACKET_MAX * ($page - 1);
            $searchService = $this->getRepository()->getSearchService();
            $results = $searchService->findLocations($query);
            $this->fillSitemap($sitemap, $root, $results);
        }

        $response = new Response($sitemap->saveXML(), 200, ['Content-type' => 'text/xml']);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    public function multisiteAccessPage(
        DOMDocument $sitemap,
        DOMElement $root,
        QueryFactory $queryFactory,
        int $page = 1
    ): void {
        $schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9" .
            " http://www.w3.org/1999/xhtml http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd".
            " http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1".
            " http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" .
            " http://www.google.com/schemas/sitemap-video/1.1";
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $root->setAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
        $root->setAttribute('xsi:schemaLocation', $schemaLocation);
        $sitemap->appendChild($root);
        //Get The site Access of selected Host and local
        $currentSiteAccess = $this->siteMapHelper->getCurrentSiteAccess();
        if ($currentSiteAccess) {
            $rootLocationId = $this->siteMapHelper->getCurrentSiteAccessRootLocationId();
            $mainLanguage =  $this->siteMapHelper->getCurrentSiteAccessMainLanguage();
            $query = $queryFactory(
                $rootLocationId,
                [$mainLanguage],
                false
            );
            $query->limit = static::PACKET_MAX;
            $query->offset = static::PACKET_MAX * ($page - 1);
            $searchService = $this->getRepository()->getSearchService();

            $results = $searchService->findLocations($query);
            $this->fillMultiLanguagesSitemap($sitemap, $root, $results);
        }
    }

    /**
     * Fill a sitemap.
     */
    protected function fillSitemap(DOMDocument $sitemap, DOMElement $root, SearchResult $results): void
    {
        foreach ($results->searchHits as $searchHit) {
            /**
             * @var Location  $location
             */
            $location = $searchHit->valueObject;
            try {
                $url = $this->generateUrl(
                    UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                    ['locationId' => $location->id],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } catch (\Exception $exception) {
                $this->siteMapHelper->logException($exception);
                continue;
            }

            if (0 != strpos($url, 'view/content/')) {
                continue;
            }

            $modified = $location->contentInfo->modificationDate->format('c');
            $loc = $sitemap->createElement('loc', $url);
            $lastmod = $sitemap->createElement('lastmod', $modified);
            $urlElt = $sitemap->createElement('url');

            // Inject the image tags if config is enabled
            $this->injectImageTag($location, $sitemap, $urlElt);

            $urlElt->appendChild($loc);
            $urlElt->appendChild($lastmod);
            $root->appendChild($urlElt);
        }
    }

    /**
     * Fill the sitemap index.
     */
    protected function fillSitemapIndex(DOMDocument $sitemap, int $numberOfResults, DOMElement $root): void
    {
        $numberOfPage = (int) ceil($numberOfResults / static::PACKET_MAX);
        for ($sitemapNumber = 1; $sitemapNumber <= $numberOfPage; ++$sitemapNumber) {
            $sitemapElt = $sitemap->createElement('sitemap');

            try {
                $locUrl = $this->generateUrl(
                    '_novaseo_sitemap_page',
                    ['page' => $sitemapNumber],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } catch (\Exception $exception) {
                $this->siteMapHelper->logException($exception);
                continue;
            }

            $loc = $sitemap->createElement('loc', $locUrl);
            $date = new DateTime();
            $modificationDate = $date->format('c');
            $mod = $sitemap->createElement('lastmod', $modificationDate);
            $sitemapElt->appendChild($loc);
            $sitemapElt->appendChild($mod);
            $root->appendChild($sitemapElt);
        }
    }
    protected function fillSitemapMultiSiteIndex(
        DOMDocument $sitemap,
        DOMElement $root,
        QueryFactory $queryFactory
    ): void {
        $siteMapHelper = $this->siteMapHelper;
        $this->repository->sudo(static function (Repository $repository) use (
            $sitemap,
            $root,
            $queryFactory,
            $siteMapHelper
        ) {
            $siteAccesses = $siteMapHelper->getSiteAccessesLocationIdLanguages();
            foreach ($siteAccesses as $siteAccess => $rootLocationLanguages) {
                $rootLocationId = $rootLocationLanguages['rootLocationId'];
                $query = $queryFactory(
                    $rootLocationId,
                    [$rootLocationLanguages['mainLanguage']],
                    false
                );
                $query->limit = 0;
                $numberOfResults = $repository->getSearchService()->findLocations($query)->totalCount;
                $numberOfPage = (int) ceil($numberOfResults / static::PACKET_MAX);
                for ($sitemapNumber = 1; $sitemapNumber <= $numberOfPage; ++$sitemapNumber) {
                    $sitemapElt = $sitemap->createElement('sitemap');
                    $locUrl = $siteMapHelper->generateRouteUrl(
                        '_novaseo_sitemap_page',
                        $siteAccess,
                        ['page' => $sitemapNumber]
                    );
                    $loc = $sitemap->createElement('loc', $locUrl);
                    $date = new DateTime();
                    $modificationDate = $date->format('c');
                    $mod = $sitemap->createElement('lastmod', $modificationDate);
                    $sitemapElt->appendChild($loc);
                    $sitemapElt->appendChild($mod);
                    $root->appendChild($sitemapElt);
                }
            }
        });
    }

    /**
     * Fill a sitemap.
     */
    protected function fillMultiLanguagesSitemap(
        DOMDocument $sitemap,
        DOMElement $root,
        SearchResult $results
    ): void {
        $currentSiteAccess = $this->siteMapHelper->getCurrentSiteAccess();
        foreach ($results->searchHits as $searchHit) {
            /**
             * @var Location  $location
             */
            $location = $searchHit->valueObject;

            $mainLanguageUrl = $this->siteMapHelper->generateLocationUrl($location->id, $currentSiteAccess);
            if (null === $mainLanguageUrl || 0 != strpos($mainLanguageUrl, 'view/content/')) {
                continue;
            }

            $modified = $location->contentInfo->modificationDate->format('c');
            $loc = $sitemap->createElement('loc', $mainLanguageUrl);
            $lastmod = $sitemap->createElement('lastmod', $modified);
            $urlElt = $sitemap->createElement('url');

            $urlElt->appendChild($loc);
            // Inject the image tags if config is enabled
            $this->injectImageTag($location, $sitemap, $urlElt);
            // Inject the alternate lang tags if config is enabled
            $this->injectAlternateLangTag($location, $sitemap, $urlElt);

            $urlElt->appendChild($lastmod);
            $root->appendChild($urlElt);
        }
    }

    public function injectAlternateLangTag($location, DOMDocument $sitemap, DOMElement $root): void
    {
        $isMultiLanguages = $this->getConfigResolver()->getParameter('multi_languages_sitemap', 'nova_ezseo');
        if ($isMultiLanguages) {
            try {
                $siteAccesses = $this->getConfigResolver()->getParameter('translation_siteaccesses');
                $languagesCodes = [];
                $contentInfo = $location->contentInfo;
                $contentLanguages = $this->repository->getContentService()
                    ->loadVersionInfo($contentInfo)->getLanguages();
                foreach ($contentLanguages as $language) {
                    $languagesCodes[] = $language->languageCode;
                }
                foreach ($siteAccesses as $siteAccess) {
                    $siteAccessMainLanguage = $this->siteMapHelper->getSiteAccessMainLanguage($siteAccess);
                    if (!in_array($siteAccessMainLanguage, $languagesCodes)) {
                        continue;
                    }

                    $url = $this->siteMapHelper->generateLocationUrl($location->id, $siteAccess);
                    $hreflang = $this->siteMapHelper->getHrefLang($siteAccessMainLanguage);
                    if (null === $url || 0 != strpos($url, 'view/content/')) {
                        continue;
                    }

                    $xhtml = $sitemap->createElement('xhtml:link');
                    $xhtml->setAttribute('rel', 'alternate');
                    $xhtml->setAttribute('hreflang', $hreflang);
                    $xhtml->setAttribute('href', $url);
                    $root->appendChild($xhtml);
                }
            } catch (Throwable $e) {
                $this->siteMapHelper->logException($e);
            }
        }
    }

    public function injectImageTag($location, DOMDocument $sitemap, DOMElement $root): void
    {
        $displayImage = $this->getConfigResolver()->getParameter('display_images_in_sitemap', 'nova_ezseo');

        if (true === $displayImage) {
            try {
                $content = $this->getRepository()->getContentService()->loadContentByContentInfo(
                    $location->contentInfo
                );
            } catch (Throwable $exception) {
                return;
            }
            foreach ($content->getFields() as $field) {
                $fieldTypeIdentifier = $content->getContentType()->getFieldDefinition(
                    $field->fieldDefIdentifier
                )->fieldTypeIdentifier;

                if ('ezimage' !== $fieldTypeIdentifier && 'ezimageasset' !== $fieldTypeIdentifier) {
                    continue;
                }

                if ($this->fieldHelper->isFieldEmpty($content, $field->fieldDefIdentifier)) {
                    continue;
                }
                try {
                    $variation = $this->imageVariationService->getVariation(
                        $field,
                        $content->getVersionInfo(),
                        'original'
                    );

                    $imageContainer = $sitemap->createElement('image:image');
                    $imageLoc = $sitemap->createElement('image:loc', $variation->uri);
                    $imageContainer->appendChild($imageLoc);
                    $root->appendChild($imageContainer);
                } catch (Throwable $exception) {
                    $this->siteMapHelper->logException($exception);
                    continue;
                }
            }
        }
    }
}
