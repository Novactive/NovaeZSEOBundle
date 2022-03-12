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
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Core\Helper\FieldHelper;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Novactive\Bundle\eZSEOBundle\Core\Sitemap\QueryFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends Controller
{
    /** @var FieldHelper */
    private $fieldHelper;

    /** @var VariationHandler */
    protected $imageVariationService;

    /**
     * How many in a Sitemap.
     *
     * @var int
     */
    public const PACKET_MAX = 1000;

    public function __construct(FieldHelper $fieldHelper, VariationHandler $imageVariationService)
    {
        $this->fieldHelper = $fieldHelper;
        $this->imageVariationService = $imageVariationService;
    }

    /**
     * @Route("/sitemap.xml", name="_novaseo_sitemap_index", methods={"GET"})
     */
    public function indexAction(QueryFactory $queryFactory): Response
    {
        $searchService = $this->getRepository()->getSearchService();
        $query = $queryFactory();
        $query->limit = 0;
        $resultCount = $searchService->findLocations($query)->totalCount;

        // Dom Doc
        $sitemap = new DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;

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
        $sitemap = new DOMDocument('1.0', 'UTF-8');
        $root = $sitemap->createElement('urlset');
        $sitemap->formatOutput = true;
        $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $root->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        $sitemap->appendChild($root);
        $query = $queryFactory();
        $query->limit = static::PACKET_MAX;
        $query->offset = static::PACKET_MAX * ($page - 1);
        $searchService = $this->getRepository()->getSearchService();

        $results = $searchService->findLocations($query);
        $this->fillSitemap($sitemap, $root, $results);

        $response = new Response($sitemap->saveXML(), 200, ['Content-type' => 'text/xml']);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    /**
     * Fill a sitemap.
     */
    protected function fillSitemap(DOMDocument $sitemap, DOMElement $root, SearchResult $results): void
    {
        foreach ($results->searchHits as $searchHit) {
            /**
             * @var SearchHit
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
                if ($this->has('logger')) {
                    $this->get('logger')->error('NovaeZSEO: '.$exception->getMessage());
                }
                continue;
            }

            if (0 != strpos($url, 'view/content/')) {
                continue;
            }

            $modified = $location->contentInfo->modificationDate->format('c');
            $loc = $sitemap->createElement('loc', $url);
            $lastmod = $sitemap->createElement('lastmod', $modified);
            $urlElt = $sitemap->createElement('url');

            // Inject the image tags if config is enabl

            $displayImage = $this->getConfigResolver()->getParameter('display_images_in_sitemap', 'nova_ezseo');
            if (true === $displayImage) {
                $content = $this->getRepository()->getContentService()->loadContentByContentInfo(
                    $location->contentInfo
                );
                foreach ($content->getFields() as $field) {
                    $fieldTypeIdentifier = $content->getContentType()->getFieldDefinition(
                        $field->fieldDefIdentifier
                    )->fieldTypeIdentifier;

                    if ('ezimage' !== $fieldTypeIdentifier) {
                        continue;
                    }

                    if ($this->fieldHelper->isFieldEmpty($content, $field->fieldDefIdentifier)) {
                        continue;
                    }
                    $variation = $this->imageVariationService->getVariation($field, new VersionInfo(), 'original');
                    $imageContainer = $sitemap->createElement('image:image');
                    $imageLoc = $sitemap->createElement('image:loc', $variation->uri);
                    $imageContainer->appendChild($imageLoc);
                    $urlElt->appendChild($imageContainer);
                }
            }

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
                if ($this->has('logger')) {
                    $this->get('logger')->error('NovaeZSEO: '.$exception->getMessage());
                }
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
}
