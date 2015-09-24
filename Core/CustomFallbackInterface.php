<?php
/**
 * NovaeZSEOBundle Legacy Injection
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;

/**
 * Interface CustomFallbackInterface
 */
interface CustomFallbackInterface
{

    /**
     * Return a value for a meta name
     *
     * @param string $metaName
     *
     * @return string
     */
    public function getMetaContent( $metaName, ContentInfo $contentInfo );

}