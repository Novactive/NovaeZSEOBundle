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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DOMDocument;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationList;

/**
 * Class SitemapController
 */
class SitemapController extends Controller
{

    /**
     * Sitemaps.xml route
     *
     * @Route("/sitemap.xml")
     * @Method("GET")
     *
     * @return Response
     */
    public function sitemapAction()
    {
        $locationService = $this->get( "ezpublish.api.repository" )->getLocationService();
        $routerService   = $this->get( "ezpublish.urlalias_router" );
        $rootLocationId  = $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' );

        $sitemap               = new DOMDocument( "1.0", "UTF-8" );
        $root                  = $sitemap->createElement( "urlset" );
        $sitemap->formatOutput = true;
        $root->setAttribute( "xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9" );
        $sitemap->appendChild( $root );

        $loadChildrenFunc = function ( $parentLocationId ) use (
            &$loadChildrenFunc,
            $routerService,
            $locationService,
            $sitemap,
            $root
        )
        {
            $location = $locationService->loadLocation( $parentLocationId );
            /** @var Location $location */
            $url = $routerService->generate( $location, [], true );
            $modified = date( "c", $location->contentInfo->modificationDate->getTimestamp() );
            $loc      = $sitemap->createElement( "loc", $url );
            $lastmod  = $sitemap->createElement( "lastmod", $modified );
            $urlElt   = $sitemap->createElement( "url" );
            $urlElt->appendChild( $loc );
            $urlElt->appendChild( $lastmod );
            $root->appendChild( $urlElt );
            $childrenList = $locationService->loadLocationChildren( $location );
            /** @var LocationList $childrenList */
            if ( count( $childrenList->totalCount > 0 ) )
            {
                foreach ( $childrenList->locations as $locationChild )
                {
                    /** @var Location $locationChild */
                    $loadChildrenFunc( $locationChild->id );
                }
            }
        };

        $loadChildrenFunc($rootLocationId);

        $response = new Response();
        $response->setSharedMaxAge( 24 * 3600 );
        $response->headers->set( "Content-Type", "text/xml" );
        $response->setContent( $sitemap->saveXML() );
        return $response;
    }
}
