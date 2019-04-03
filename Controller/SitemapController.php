<?php
/**
 * NovaeZSEOBundle SitemapController
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use DOMDocument;
use DOMElement;
use DateTime;
use eZ\Publish\API\Repository\Values\Content\LocationQuery as Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\API\Repository\Values\Content\Location;

/**
 * Class SitemapController
 */
class SitemapController extends Controller
{
    /**
     * How many in a Sitemap
     *
     * @var int
     */
    const PACKET_MAX = 1000;

    /**
     * Get the common Query
     *
     * @return Query
     */
    protected function getQuery()
    {
        $locationService = $this->get( "ezpublish.api.repository" )->getLocationService();
        $limitToRootLocation = $this->getConfigResolver()->getParameter( 'limit_to_rootlocation', 'nova_ezseo' );
        $excludes        = $this->getConfigResolver()->getParameter( 'sitemap_excludes', 'nova_ezseo' );
        $query           = new Query();
        $criterion[]     = new Criterion\Visibility( Criterion\Visibility::VISIBLE );
        if ( true === $limitToRootLocation ) {
            $criterion[]     = new Criterion\Subtree( $this->getRootLocation()->pathString );
        }
        foreach ( $excludes['contentTypeIdentifiers'] as $contentTypeIdentifier )
        {
            $criterion[] = new Criterion\LogicalNot( new Criterion\ContentTypeIdentifier( $contentTypeIdentifier ) );
        }
        foreach ( $excludes['subtrees'] as $locationId )
        {
            $excludedLocation = $this->getRepository()->sudo(
                function () use ($locationService, $locationId) {
                    try
                    {
                        return $locationService->loadLocation( $locationId );
                    } catch (\Exception $e) {
                        return false;
                    }
                }
            );
            if ($excludedLocation) {
                $criterion[]      = new Criterion\LogicalNot( new Criterion\Subtree( $excludedLocation->pathString ) );
            }
        }
        foreach ( $excludes['locations'] as $locationId )
        {
            $criterion[] = new Criterion\LogicalNot( new Criterion\LocationId( $locationId ) );
        }

        $query->query = new Criterion\LogicalAnd( $criterion );
        $query->sortClauses = [ new SortClause\DatePublished( Query::SORT_DESC) ];

        return $query;
    }

    /**
     * Sitemaps.xml route
     *
     * @Route("/sitemap.xml", name="_novaseo_sitemap_index")
     * @Method("GET")
     *
     * @return Response
     */
    public function indexAction()
    {
        $searchService = $this->get( "ezpublish.api.repository" )->getSearchService();
        $query         = $this->getQuery();
        $query->limit  = 0;
        $resultCount   = $searchService->findLocations( $query )->totalCount;

        // Dom Doc
        $sitemap               = new DOMDocument( "1.0", "UTF-8" );
        $sitemap->formatOutput = true;

        // create an index if we are greater than th PACKET_MAX
        if ( $resultCount > static::PACKET_MAX )
        {
            $root = $sitemap->createElement( "sitemapindex" );
            $root->setAttribute( "xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9" );
            $sitemap->appendChild( $root );

            $this->fillSitemapIndex( $sitemap, $resultCount, $root );

            return new Response( $sitemap->saveXML(), 200, [ "Content-type" => "text/xml" ] );
        }

        // if we are less or equal than the PACKET_SIZE, redo the search with no limit and list directly the urlmap
        $query->limit = $resultCount;
        $results      = $searchService->findLocations( $query );
        $root         = $sitemap->createElement( "urlset" );
        $root->setAttribute( "xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9" );
        $this->fillSitemap( $sitemap, $root, $results );
        $sitemap->appendChild( $root );

        $response = new Response( $sitemap->saveXML(), 200, [ "Content-type" => "text/xml" ] );
        $response->setSharedMaxAge( 24 * 3600 );

        return $response;
    }

    /**
     * @param integer $page
     * @Route("/sitemap-{page}.xml", name="_novaseo_sitemap_page", requirements={"page" = "\d+"}, defaults={"page" = 1})
     *
     * @return Response
     */
    public function pageAction( $page = 1 )
    {
        $sitemap               = new DOMDocument( "1.0", "UTF-8" );
        $root                  = $sitemap->createElement( "urlset" );
        $sitemap->formatOutput = true;
        $root->setAttribute( "xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9" );
        $sitemap->appendChild( $root );
        $query         = $this->getQuery();
        $query->limit  = static::PACKET_MAX;
        $query->offset = static::PACKET_MAX * ( $page - 1 );

        $searchService = $this->get( "ezpublish.api.repository" )->getSearchService();

        $results = $searchService->findLocations( $query );
        $this->fillSitemap( $sitemap, $root, $results );

        $response = new Response( $sitemap->saveXML(), 200, [ "Content-type" => "text/xml" ] );
        $response->setSharedMaxAge( 24 * 3600 );

        return $response;
    }


    /**
     * Fill a sitemap
     *
     * @param DOMDocument  $sitemap
     * @param DOMElement   $root
     * @param SearchResult $results
     */
    protected function fillSitemap( $sitemap, $root, SearchResult $results )
    {
        foreach ( $results->searchHits as $searchHit )
        {
            /**
             * @var SearchHit $searchHit
             * @var Location  $location
             *
             */
            $location = $searchHit->valueObject;
            $url      = $this->generateUrl( $location, [ ], UrlGeneratorInterface::ABSOLUTE_URL );
            $modified = $location->contentInfo->modificationDate->format( "c" );
            $loc      = $sitemap->createElement( "loc", $url );
            $lastmod  = $sitemap->createElement( "lastmod", $modified );
            $urlElt   = $sitemap->createElement( "url" );
            $urlElt->appendChild( $loc );
            $urlElt->appendChild( $lastmod );
            $root->appendChild( $urlElt );
        }
    }

    /**
     * Fill the sitemap index
     *
     * @param DOMDocument $sitemap
     * @param integer     $numberOfResults
     * @param DOMElement  $root
     */
    protected function fillSitemapIndex( $sitemap, $numberOfResults, $root )
    {
        $numberOfPage = (int)ceil( $numberOfResults / static::PACKET_MAX );
        for ( $sitemapNumber = 1; $sitemapNumber <= $numberOfPage; $sitemapNumber++ )
        {
            $sitemapElt       = $sitemap->createElement( "sitemap" );
            $locUrl           = $this->generateUrl( "_novaseo_sitemap_page", [ "page" => $sitemapNumber ], UrlGeneratorInterface::ABSOLUTE_URL );
            $loc              = $sitemap->createElement( "loc", $locUrl );
            $date             = new DateTime();
            $modificationDate = $date->format( 'c' );
            $mod              = $sitemap->createElement( 'lastmod', $modificationDate );
            $sitemapElt->appendChild( $loc );
            $sitemapElt->appendChild( $mod );
            $root->appendChild( $sitemapElt );
        }
    }
}
