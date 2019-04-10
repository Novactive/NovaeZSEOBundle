<?php
/**
 * NovaeZSEOBundle MetasType.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\Core\FieldType\FieldType;
use eZ\Publish\Core\FieldType\ValidationError;
use eZ\Publish\Core\FieldType\Value as CoreValue;
use eZ\Publish\SPI\FieldType\Value as SPIValue;
use eZ\Publish\SPI\Persistence\Content\FieldValue;
use Novactive\Bundle\eZSEOBundle\Core\Meta;

class Type extends FieldType
{
    const IDENTIFIER = 'novaseometas';

    /**
     * @var array
     */
    protected $settingsSchema = [
        'configuration' => [
            'type'    => 'hash',
            'default' => [],
        ],
    ];

    /**
     * Validates the fieldSettings of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     *
     * @return \eZ\Publish\SPI\FieldType\ValidationError[]
     */
    public function validateFieldSettings($fieldSettings)
    {
        $validationErrors = [];

        foreach ($fieldSettings as $settingKey => $settingValue) {
            switch ($settingKey) {
                case 'configuration':
                    if (!is_array($settingValue)) {
                        $validationErrors[] = new ValidationError(
                            "FieldType '%fieldType%' expects setting '%setting%' to be of type '%type%'",
                            null,
                            [
                                '%fieldType%' => $this->getFieldTypeIdentifier(),
                                '%setting%'   => $settingKey,
                                '%type%'      => 'hash',
                            ],
                            "[$settingKey]"
                        );
                    }
                    break;
                default:
                    $validationErrors[] = new ValidationError(
                        "Setting '%setting%' is unknown",
                        null,
                        [
                            '%setting%' => $settingKey,
                        ],
                        "[$settingKey]"
                    );
            }
        }

        return $validationErrors;
    }

    /**
     * Return the FieldType identifier ( Legacy DataTypeString ).
     *
     * @return string
     */
    public function getFieldTypeIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     *
     * @return Value the potentially converted and structurally plausible value
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_array($inputValue)) {
            foreach ($inputValue as $index => $inputValueItem) {
                if (!$inputValueItem instanceof Meta) {
                    throw new InvalidArgumentType(
                        '$inputValue['.$index.']',
                        '\\Novactive\\Bundle\\SEOBundle\\API\\Repository\\Values\\Metas\\Meta',
                        $inputValueItem
                    );
                }
            }
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws InvalidArgumentType if the value does not match the expected structure
     */
    protected function checkValueStructure(CoreValue $value)
    {
        if (!is_array($value->metas)) {
            throw new InvalidArgumentType(
                '$value->metas',
                'array',
                $value->metas
            );
        }

        foreach ($value->metas as $index => $meta) {
            if (!$meta instanceof Meta) {
                throw new InvalidArgumentType(
                    '$value->metas['.$index.']',
                    '\\Novactive\\Bundle\\SEOBundle\\API\\Repository\\Values\\Metas\\Meta',
                    $meta
                );
            }
        }
    }

    /**
     * Returns the empty value for this field type.
     *
     * @return Value
     */
    public function getEmptyValue()
    {
        return new Value();
    }

    /**
     * Returns a human readable string representation from the given $value.
     *
     * @param Value $value
     *
     * @return string
     */
    public function getName(SPIValue $value)
    {
        return $value->__toString();
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param Value $value
     *
     * @return bool
     */
    protected function getSortInfo(CoreValue $value)
    {
        return false;
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     *
     *
     * @return Value
     */
    public function fromHash($hash)
    {
        if (!is_array($hash)) {
            return new Value([]);
        }
        $metas = [];
        foreach ($hash as $hashItem) {
            if (!is_array($hashItem)) {
                continue;
            }
            $meta = new Meta();
            $meta->setName($hashItem['meta_name']);
            $meta->setContent($hashItem['meta_content']);
            $metas[] = $meta;
        }

        return new Value($metas);
    }

    /**
     * Converts the given $value into a plain hash format.
     *
     * @param Value $value
     *
     * @return array
     */
    public function toHash(SPIValue $value)
    {
        $hash = [];
        foreach ($value->metas as $meta) {
            /* @var Meta $meta */
            $hash[$meta->getName()] = [
                'meta_name'    => $meta->getName(),
                'meta_content' => $meta->getContent(),
            ];
        }

        return $hash;
    }

    /**
     * Converts a $value to a persistence value.
     *
     * @param Value $value
     *
     * @return FieldValue
     */
    public function toPersistenceValue(SPIValue $value)
    {
        return new FieldValue(
            [
                'data'         => null,
                'externalData' => $this->toHash($value),
                'sortKey'      => $this->getSortInfo($value),
            ]
        );
    }

    /**
     * Converts a persistence $fieldValue to a Value.
     *
     *
     * @return Value
     */
    public function fromPersistenceValue(FieldValue $fieldValue)
    {
        return $this->fromHash($fieldValue->externalData);
    }

    /**
     * Returns if the given $value is considered empty by the field type.
     *
     * @param Value $value
     *
     * @return bool
     */
    public function isEmptyValue(SPIValue $value)
    {
        return null === $value || $value->metas == $this->getEmptyValue()->metas;
    }
}
