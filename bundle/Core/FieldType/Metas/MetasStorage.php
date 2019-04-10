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
     *
     *
     * @return mixed null|true
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field, array $context)
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
    public function getFieldData(VersionInfo $versionInfo, Field $field, array $context)
    {
        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway($context);
        $gateway->getFieldData($versionInfo, $field);
    }

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     *
     *
     * @return bool
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds, array $context)
    {
        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway($context);
        $gateway->deleteFieldData($versionInfo, $fieldIds);
    }

    /**
     * Checks if field type has external data to deal with.
     *
     * @return bool
     */
    public function hasFieldData()
    {
        return true;
    }

    /**
     * Get index data for external data for search backend.
     *
     *
     * @return bool
     */
    public function getIndexData(VersionInfo $versionInfo, Field $field, array $context)
    {
        return false;
    }
}
