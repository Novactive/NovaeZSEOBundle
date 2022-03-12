<?php

/**
 * NovaeZSEOBundle MetasStorage.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

class MetasStorage extends GatewayBasedStorage
{
    /**
     * @var \Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway
     */
    protected $gateway;

    /**
     * Stores value for $field in an external data source.
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field, array $context): void
    {
        if (empty($field->value->externalData)) {
            return;
        }

        $metas = $this->gateway->loadFieldData($versionInfo, $field);
        if ($metas) {
            $this->gateway->deleteFieldData($versionInfo, [$field->id]);
        }

        $this->gateway->storeFieldData($versionInfo, $field);
    }

    /**
     * Populates $field value property based on the external data.
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field, array $context): void
    {
        $this->gateway->getFieldData($versionInfo, $field);
    }

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds, array $context): void
    {
        $this->gateway->deleteFieldData($versionInfo, $fieldIds);
    }

    /**
     * Checks if field type has external data to deal with.
     */
    public function hasFieldData(): bool
    {
        return true;
    }

    /**
     * Get index data for external data for search backend.
     */
    public function getIndexData(VersionInfo $versionInfo, Field $field, array $context): bool
    {
        return false;
    }
}
