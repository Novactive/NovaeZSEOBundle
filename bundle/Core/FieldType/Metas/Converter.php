<?php

/**
 * NovaeZSEOBundle Converter.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas;

use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter as LegacyConverter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;

class Converter implements LegacyConverter
{
    /**
     * Factory for current class.
     *
     * @note Class should instead be configured as service if it gains dependencies.
     */
    public static function create(): Converter
    {
        return new self();
    }

    /**
     * Converts data from $value to $storageFieldValue.
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue): void
    {
    }

    /**
     * Converts data from $value to $fieldValue.
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue): void
    {
    }

    /**
     * Converts field definition data in $fieldDef into $storageFieldDef.
     */
    public function toStorageFieldDefinition(FieldDefinition $fieldDef, StorageFieldDefinition $storageDef): void
    {
        $fieldSettings = $fieldDef->fieldTypeConstraints->fieldSettings;

        if (isset($fieldSettings['configuration'])) {
            $storageDef->dataText5 = json_encode($fieldSettings['configuration']);
        }
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef.
     */
    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef): void
    {
        $fieldDef->fieldTypeConstraints->fieldSettings = new FieldSettings(
            [
                'configuration' => json_decode($storageDef->dataText5, true),
            ]
        );
    }

    /**
     * Returns the name of the index column in the attribute table.
     */
    public function getIndexColumn(): bool
    {
        return false;
    }
}
