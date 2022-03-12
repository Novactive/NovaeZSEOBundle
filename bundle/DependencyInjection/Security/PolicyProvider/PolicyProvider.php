<?php

/**
 * NovaeZSEOBundle Configuration.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\DependencyInjection\Security\PolicyProvider;

use Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

class PolicyProvider extends YamlPolicyProvider
{
    /**
     * Returns an array of files where the policy configuration lies.
     * Each file path MUST be absolute.
     */
    public function getFiles(): array
    {
        return [
            __DIR__.'/../../../Resources/config/policies.yaml',
        ];
    }
}
