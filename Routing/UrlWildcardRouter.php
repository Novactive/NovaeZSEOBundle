<?php
/**
 * NovaeZSEOBundle Bundle
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Routing;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\URLWildcardService;
use eZ\Publish\API\Repository\Values\Content\URLWildcard;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Class UrlWildcardRouter
 * @package Novactive\Bundle\eZSEOBundle\Routing
 */
class UrlWildcardRouter implements RequestMatcherInterface, ChainedRouterInterface
{
    const URL_WILDCARD_ROUTE_NAME = 'ez_urlwildcard';

    /** @var \Symfony\Component\Routing\RequestContext */
    protected $requestContext;

    /** @var URLWildcardService */
    protected $urlWildcardService;

    /**
     * UrlWildcardRouter constructor.
     * @param URLWildcardService $urlWildcardService
     */
    public function __construct( URLWildcardService $urlWildcardService )
    {
        $this->urlWildcardService = $urlWildcardService;
    }


    /**
     * Sets the request context.
     */
    public function setContext( RequestContext $context )
    {
        $this->requestContext = $context;
    }

    /**
     * Gets the request context.
     *
     * @return RequestContext The context
     */
    public function getContext()
    {
        return $this->requestContext;
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * Parameters that reference placeholders in the route pattern will substitute them in the
     * path or host. Extra params are added as query string to the URL.
     *
     * When the passed reference type cannot be generated for the route because it requires a different
     * host or scheme than the current one, the method will return a more comprehensive reference
     * that includes the required params. For example, when you call this method with $referenceType = ABSOLUTE_PATH
     * but the route requires the https scheme whereas the current scheme is http, it will instead return an
     * ABSOLUTE_URL with the https scheme and the current host. This makes sure the generated URL matches
     * the route in any case.
     *
     * If there is no route with the given name, the generator must throw the RouteNotFoundException.
     *
     * The special parameter _fragment will be used as the document fragment suffixed to the final URL.
     *
     * @param string $name The name of the route
     * @param mixed $parameters An array of parameters
     * @param int $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     *
     * @throws RouteNotFoundException              If the named route doesn't exist
     * @throws MissingMandatoryParametersException When some parameters are missing that are mandatory for the route
     * @throws InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     */
    public function generate( $name, $parameters = [], $referenceType = self::ABSOLUTE_PATH )
    {
        // TODO: Implement generate() method.
    }

    /**
     * Not supported. Please use matchRequest() instead.
     *
     * @param $pathinfo
     *
     * @throws \RuntimeException
     */
    public function match($pathinfo)
    {
        throw new \RuntimeException("The UrlWildcardRouter doesn't support the match() method. Please use matchRequest() instead.");
    }

    /**
     * Whether this generator supports the supplied $name.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     *
     * @param mixed $name The route "name" which may also be an object or anything
     *
     * @return bool
     */
    public function supports( $name )
    {
        return $name instanceof URLWildcard || $name === self::URL_WILDCARD_ROUTE_NAME;
    }

    /**
     * Convert a route identifier (name, content object etc) into a string
     * usable for logging and other debug/error messages.
     *
     * @param mixed $name
     * @param array $parameters which should contain a content field containing
     *                          a RouteReferrersReadInterface object
     *
     * @return string
     */
    public function getRouteDebugMessage( $name, array $parameters = [] )
    {

        if ($name instanceof RouteObjectInterface) {
            return 'Route with key ' . $name->getRouteKey();
        }

        if ($name instanceof SymfonyRoute) {
            return 'Route with pattern ' . $name->getPath();
        }

        return $name;
    }

    /**
     * Tries to match a request with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @return array An array of parameters
     *
     * @throws NoConfigurationException  If no routing configuration could be found
     * @throws ResourceNotFoundException If no matching resource could be found
     * @throws MethodNotAllowedException If a matching resource was found but the request method is not allowed
     */
    public function matchRequest( Request $request )
    {
        try
        {
            $requestedPath = $request->attributes->get( 'semanticPathinfo', $request->getPathInfo() );
            $urlWildcard = $this->getUrlWildcard($requestedPath);

            $params = array(
                '_route' => self::URL_WILDCARD_ROUTE_NAME,
            );

            // In URLAlias terms, "forward" means "redirect".
            if ($urlWildcard->forward) {
                $params += array(
                    'semanticPathinfo' => '/' . trim($urlWildcard->uri, '/'),
                    'needsRedirect' => true,
                );
            } else {
                $params += array(
                    'semanticPathinfo' => '/' . trim($urlWildcard->uri, '/'),
                    'needsForward' => true,
                );
            }

            return $params;
        }
        catch ( NotFoundException $e )
        {
            throw new ResourceNotFoundException( $e->getMessage(), $e->getCode(), $e );
        }
    }

    /**
     * Returns the UrlWildcard object to use, starting from the request.
     *
     * @param $pathinfo
     * @return \eZ\Publish\API\Repository\Values\Content\URLWildcardTranslationResult
     * @throws NotFoundException if no match found
     */
    protected function getUrlWildcard($pathinfo)
    {
        return $this->urlWildcardService->translate($pathinfo);
    }
}
