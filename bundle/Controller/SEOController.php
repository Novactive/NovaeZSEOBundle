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
use Ibexa\Bundle\Core\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SEOController extends Controller
{
    /**
     * @Route("/robots.txt", methods={"GET"})
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function robotsAction(): Response
    {
        $response = new Response();
        $response->setSharedMaxAge(86400);


        $robotsParameters = $this->getConfigResolver()->getParameter('robots', 'nova_ezseo');
        $backwardCompatibleRules = $this->getConfigResolver()->getParameter('robots_disallow', 'nova_ezseo');

        $robotRules = [ '*' => []];
        $addRule = function (array|string $paths, bool $allow = true, string $userAgent = '*') use (&$robotRules) {
            if (is_string($paths)) {
                $paths = [$paths];
            }
            foreach ($paths as $path) {
                $robotRules[$userAgent][] = sprintf(
                    '%s: %s',
                    $allow ? 'Allow' : 'Disallow',
                    $path
                );
            }
        };

        if ('dev' !== $this->getParameter('kernel.environment')) {
            $addRule('/', false);
        } else {
            if (\is_array($robotsParameters['allow'])) {
                $addRule($robotsParameters['allow']);
            }

            if (\is_array($robotsParameters['disallow'])) {
                $addRule($robotsParameters['disallow'], false);
            }

            $rules = $robotsParameters['rules'] ?? [];
            foreach ($rules as $rule) {
                foreach ($rule['user_agents'] as $userAgent) {
                    $addRule($rule['allow'], true, $userAgent);
                    $addRule($rule['disallow'], false, $userAgent);
                }
            }

            if (\is_array($backwardCompatibleRules)) {
                foreach ($backwardCompatibleRules as $rule) {
                    $addRule($rule, false);
                }
            }
        }

        $robotsTxt = '';
        foreach ($robotRules as $userAgent => $userAgentRules) {
            if (empty($userAgentRules)) {
                continue;
            }

            $robotsTxt .= "User-agent: $userAgent".PHP_EOL;
            foreach ($userAgentRules as $userAgentRule) {
                $robotsTxt .= $userAgentRule.PHP_EOL;
            }
            $robotsTxt .= PHP_EOL;
        }

        if (\is_array($robotsParameters['sitemap'])) {
            foreach ($robotsParameters['sitemap'] as $sitemapRules) {
                foreach ($sitemapRules as $key => $value) {
                    if ('route' === $key) {
                        $url = $this->generateUrl($value, [], UrlGeneratorInterface::ABSOLUTE_URL);
                        $robotsTxt .= "Sitemap: {$url}".PHP_EOL;
                    }
                    if ('url' === $key) {
                        $robotsTxt .= "Sitemap: {$value}".PHP_EOL;
                    }
                }
            }
        }

        $response->setContent($robotsTxt);
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }

    /**
     * @Route("/google{key}.html", requirements={ "key": "[a-zA-Z0-9]*" }, methods={"GET"})
     */
    public function googleVerifAction(string $key): Response
    {
        if ($this->getConfigResolver()->getParameter('google_verification', 'nova_ezseo') !== $key) {
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
        if (!$this->getConfigResolver()->hasParameter('bing_verification', 'nova_ezseo')) {
            throw new NotFoundHttpException('Bing Verification Key not found');
        }

        $key = $this->getConfigResolver()->getParameter('bing_verification', 'nova_ezseo');

        $xml = new DOMDocument('1.0', 'UTF-8');
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
