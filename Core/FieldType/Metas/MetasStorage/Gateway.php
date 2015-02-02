<?php
/**
 * NovaeZSEOBundle Gateway
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage;

use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\Core\FieldType\StorageGateway;

/**
 * Abstract gateway class for Nova Metas field type.
 * Handles URL data.
 */
abstract class Gateway extends StorageGateway
{
    /**
     * Stores the tags in the database based on the given field data
     *
     * @param VersionInfo $versionInfo
     * @param Field       $field
     */
    abstract public function storeFieldData( VersionInfo $versionInfo, Field $field );

    /**
     * Gets the tags stored in the field
     *
     * @param VersionInfo $versionInfo
     * @param Field       $field
     */
    abstract public function getFieldData( VersionInfo $versionInfo, Field $field );

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     *
     * @param VersionInfo $versionInfo
     * @param array       $fieldIds
     */
    abstract public function deleteFieldData( VersionInfo $versionInfo, array $fieldIds );
}
