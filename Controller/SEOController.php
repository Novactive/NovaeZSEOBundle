<?php
/**
 * NovaeZSEOBundle SEOController
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

/**
 * Class SEOController
 */
class SEOController extends Controller
{
    /**
     * Robots.txt route
     *
     * @Route("/robots.txt")
     * @Method("GET")
     * @return Response
     */
    public function robotsAction()
    {
        $response = new Response();
        $response->setSharedMaxAge( 24 * 3600 );
        $robots = [ "User-agent: *" ];

        if ( $this->get( 'kernel' )->getEnvironment() != "prod" )
        {
            $robots[] = "Disallow: /";
        }
        $rules = $this->getConfigResolver()->getParameter( 'robots_disallow', 'novae_zseo' );
        if ( is_array( $rules ) )
        {
            foreach ( $rules as $rule )
            {
                $robots[] = "Disallow: {$rule}";
            }
        }
        $response->setContent( implode( "\n", $robots ) );
        $response->headers->set( "Content-Type", "text/plain" );
        return $response;
    }

    /**
     * Google Verification route
     *
     * @param string $key
     * @Route("/google{key}.html", requirements={ "key": "[a-zA-Z0-9]*" })
     * @Method("GET")
     * @throws NotFoundHttpException
     * @return Response
     */
    public function googleVerifAction( $key )
    {
        if ( $this->getConfigResolver()->getParameter( 'google_verification', 'novae_zseo' ) != $key )
        {
            throw new NotFoundHttpException( "Google Verification Key not found" );
        }
        $response = new Response();
        $response->setSharedMaxAge( 24 * 3600 );
        $response->setContent( "google-site-verification: google{$key}.html" );
        return $response;
    }

    /**
     * Bing Verification route
     *
     * @Route("/BingSiteAuth.xml")
     * @Method("GET")
     * @throws NotFoundHttpException
     * @return Response
     */
    public function bingVerifAction()
    {
        if ( !$this->getConfigResolver()->hasParameter( 'bing_verification', 'novae_zseo' ) )
        {
            throw new NotFoundHttpException( "Bing Verification Key not found" );
        }
        $key = $this->getConfigResolver()->getParameter( 'bing_verification', 'novae_zseo' );
        $xml = new DOMDocument("1.0", "UTF-8");
        $xml->formatOutput = true;
        $root = $xml->createElement("users");
        $root->appendChild($xml->createElement("user", $key));
        $xml->appendChild($root);
        $response = new Response($xml->saveXML());
        $response->setSharedMaxAge( 24 * 3600 );
        $response->headers->set("Content-Type", "text/xml");
        return $response;
    }
}
}
