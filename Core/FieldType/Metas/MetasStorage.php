<?php
/**
 * NovaeZSEOBundle MetasStorage
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas;

use eZ\Publish\Core\FieldType\GatewayBasedStorage;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway\LegacyStorage;

/**
 * Class MetasStorage
 */
class MetasStorage extends GatewayBasedStorage
{
    /**
     * Stores value for $field in an external data source.
     *
     * @param VersionInfo $versionInfo
     * @param Field       $field
     * @param array       $context
     *
     * @return mixed null|true
     */
    public function storeFieldData( VersionInfo $versionInfo, Field $field, array $context )
    {
        if ( empty( $field->value->externalData ) )
        {
            return;
        }

        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway( $context );

        $metas = $gateway->loadFieldData( $versionInfo, $field );
        if ($metas) {
            $gateway->deleteFieldData( $versionInfo, array($field->id) );
        }

        $gateway->storeFieldData( $versionInfo, $field );
    }

    /**
     * Populates $field value property based on the external data.
     *
     * @param VersionInfo $versionInfo
     * @param Field       $field
     * @param array       $context
     */
    public function getFieldData( VersionInfo $versionInfo, Field $field, array $context )
    {
        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway( $context );
        $gateway->getFieldData( $versionInfo, $field );
    }

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     *
     * @param VersionInfo $versionInfo
     * @param array       $fieldIds
     * @param array       $context
     *
     * @return boolean
     */
    public function deleteFieldData( VersionInfo $versionInfo, array $fieldIds, array $context )
    {
        /** @var LegacyStorage $gateway */
        $gateway = $this->getGateway( $context );
        $gateway->deleteFieldData( $versionInfo, $fieldIds );
    }

    /**
     * Checks if field type has external data to deal with
     *
     * @return boolean
     */
    public function hasFieldData()
    {
        return true;
    }

    /**
     * Get index data for external data for search backend
     *
     * @param VersionInfo $versionInfo
     * @param Field       $field
     * @param array       $context
     *
     * @return bool
     */
    public function getIndexData( VersionInfo $versionInfo, Field $field, array $context )
    {
        return false;
    }

}
