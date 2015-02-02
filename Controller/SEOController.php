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
        $rules = $this->getConfigResolver()->getParameter( 'disallow', 'novaseo' );
        foreach ( $rules as $rule )
        {
            $robots[] = "Disallow: {$rule}";
        }

        $response->setContent( implode( "\n", $robots ) );

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
        if ( $this->getConfigResolver()->getParameter( 'google_verification', 'novaseo' ) != $key )
        {
            throw new NotFoundHttpException( "Google Verification Key not found" );
        }
        $response = new Response();
        $response->setSharedMaxAge( 24 * 3600 );
        $response->setContent( "google-site-verification: google{$key}.html" );
        return $response;
    }
}
