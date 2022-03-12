<?php

/**
 * NovaeZSEOBundle Gateway.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage;

use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Abstract gateway class for Nova Metas field type.
 * Handles URL data.
 */
abstract class Gateway extends StorageGateway
{
    /**
     * Stores the tags in the database based on the given field data.
     */
    abstract public function storeFieldData(VersionInfo $versionInfo, Field $field): void;

    /**
     * Gets the tags stored in the field.
     */
    abstract public function getFieldData(VersionInfo $versionInfo, Field $field): void;

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     */
    abstract public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): void;

    /**
     * Returns the data for the given $field and $version.
     */
    abstract public function loadFieldData(VersionInfo $versionInfo, Field $field): array;
}
