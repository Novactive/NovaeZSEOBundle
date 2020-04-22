<?php
/**
 * NovaeZSEOBundle SEOController.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Controller;

use DOMDocument;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SEOController extends Controller
{

    private $kernel;
    private $configResolver;

    public function __construct (
        KernelInterface $kernel,
        ConfigResolverInterface $configResolver
    )
    {
        $this->kernel = $kernel;
        $this->configResolver = $configResolver;
    }

    /**
     * @Route("/robots.txt", methods={"GET"})
     */
    public function robotsAction(): Response
    {
        $response = new Response();
        $response->setSharedMaxAge(86400);
        $robots = ['User-agent: *'];

        $robotsRules             = $this->configResolver->getParameter('robots', 'nova_ezseo');
        $backwardCompatibleRules = $this->configResolver->getParameter('robots_disallow', 'nova_ezseo');

        if (\is_array($robotsRules['sitemap'])) {
            foreach ($robotsRules['sitemap'] as $sitemapRules) {
                foreach ($sitemapRules as $key => $value) {
                    if ('route' === $key) {
                        $url      = $this->generateUrl($value, [], UrlGeneratorInterface::ABSOLUTE_URL);
                        $robots[] = "Sitemap: {$url}";
                    }
                    if ('url' === $key) {
                        $robots[] = "Sitemap: {$value}";
                    }
                }
            }
        }
        if (\is_array($robotsRules['allow'])) {
            foreach ($robotsRules['allow'] as $rule) {
                $robots[] = "Allow: {$rule}";
            }
        }
        if ('prod' !== $this->kernel->getEnvironment()) {
            $robots[] = 'Disallow: /';
        }

        if (\is_array($robotsRules['disallow'])) {
            foreach ($robotsRules['disallow'] as $rule) {
                $robots[] = "Disallow: {$rule}";
            }
        }

        if (\is_array($backwardCompatibleRules)) {
            foreach ($backwardCompatibleRules as $rule) {
                $robots[] = "Disallow: {$rule}";
            }
        }

        $response->setContent(implode("\n", $robots));
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }

    /**
     * @Route("/google{key}.html", requirements={ "key": "[a-zA-Z0-9]*" }, methods={"GET"})
     */
    public function googleVerifAction(string $key): Response
    {
        if ($this->getParameter('google_verification', 'nova_ezseo') !== $key) {
            throw new NotFoundHttpException('Google Verification Key not found');
        }
        $response = new Response();
        $response->setSharedMaxAge(86400);
        $response->setContent("google-site-verification: google{$key}.html");

        return $response;
    }

    /**
     * @Route("/BingSiteAuth.xml", methods={"GET"})
     */
    public function bingVerifAction(): Response
    {
        if (!$this->configResolver->hasParameter('bing_verification', 'nova_ezseo')) {
            throw new NotFoundHttpException('Bing Verification Key not found');
        }

        $key = $this->configResolver->getParameter('bing_verification', 'nova_ezseo');

        $xml               = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('users');
        $root->appendChild($xml->createElement('user', $key));
        $xml->appendChild($root);

        $response = new Response($xml->saveXML());
        $response->setSharedMaxAge(86400);
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
}
