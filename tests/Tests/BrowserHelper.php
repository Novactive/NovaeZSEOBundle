<?php

/**
 * NovaeZSEOBundle Bundle.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Tests;

use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;

final class BrowserHelper
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $lastRequest;

    /**
     * @var Crawler
     */
    private $lastCrawler;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function request(
        string $method,
        string $uri,
        bool $force = false,
        array $parameters = [],
        array $files = [],
        array $server = [],
        string $content = null,
        bool $changeHistory = true
    ): Crawler {
        $request = [
            'method' => $method,
            'uri' => $uri,
            'parameters' => $parameters,
            'files' => $files,
            'server' => $server,
            'content' => $content,
            'changeHistory' => $changeHistory,
        ];

        if ($request === $this->lastRequest) {
            if (false === $force) {
                return $this->lastCrawler;
            }
        }

        $this->lastRequest = $request;

        $crawler = $this
            ->client
            ->request(
                $method,
                $uri,
                $parameters,
                $files,
                $server,
                $content,
                $changeHistory
            );

        $this->lastCrawler = $crawler;

        return $this->lastCrawler;
    }

    public function get(string $url): Crawler
    {
        return $this->request('GET', $url);
    }

    public function crawler(): Crawler
    {
        $this->client->refreshCrawler();

        return $this->client->getCrawler();
    }

    public function waitFor(string $locator): Crawler
    {
        $this->lastCrawler = $this->client->waitFor($locator);

        return $this->lastCrawler;
    }

    public function wait(int $numberOfSeconds): void
    {
        sleep($numberOfSeconds);
    }
}
