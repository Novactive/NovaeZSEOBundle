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

use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCaseTrait;

class TestCase extends BaseTestCase
{
    public const CHROME   = 'chrome';
    public const FIREFOX  = 'firefox';
    public const BASE_URI = 'https://127.0.0.1:8000';

    use PantherTestCaseTrait;

    protected function getPantherClient(): PantherClient
    {
        return self::createPantherClient(
            [
                'browser'           => 'chrome',
                'external_base_uri' => getenv('PANTHER_EXTERNAL_BASE_URI') ?:
                    $_SERVER['PANTHER_EXTERNAL_BASE_URI'] ?? self::BASE_URI,
            ]
        );
    }
}
