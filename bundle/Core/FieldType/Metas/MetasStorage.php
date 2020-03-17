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

use eZ\Publish\Core\FieldType\GatewayBasedStorage;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway\LegacyStorage;

class MetasStorage extends GatewayBasedStorage
{
    /**
     * Stores value for $field in an external data source.
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field, array $context): void
    {
        if (empty($field->value->externalData)) {
            return;
        }

        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway($context);

        $metas = $gateway->loadFieldData($versionInfo, $field);
        if ($metas) {
            $gateway->deleteFieldData($versionInfo, [$field->id]);
        }

        $gateway->storeFieldData($versionInfo, $field);
    }

    /**
     * Populates $field value property based on the external data.
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field, array $context): void
    {
        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway($context);
        $gateway->getFieldData($versionInfo, $field);
    }

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds, array $context): void
    {
        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway($context);
        $gateway->deleteFieldData($versionInfo, $fieldIds);
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
