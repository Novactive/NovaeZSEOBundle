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

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as CoreValue;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Novactive\Bundle\eZSEOBundle\Core\Meta;

class Type extends FieldType
{
    public const IDENTIFIER = 'novaseometas';

    /**
     * @var array
     */
    protected $settingsSchema = [
        'configuration' => [
            'type' => 'hash',
            'default' => [],
        ],
    ];

    /**
     * Validates the fieldSettings of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     */
    public function validateFieldSettings($fieldSettings): array
    {
        $validationErrors = [];

        foreach ($fieldSettings as $settingKey => $settingValue) {
            switch ($settingKey) {
                case 'configuration':
                    if (!\is_array($settingValue)) {
                        $validationErrors[] = new ValidationError(
                            "FieldType '%fieldType%' expects setting '%setting%' to be of type '%type%'",
                            null,
                            [
                                '%fieldType%' => $this->getFieldTypeIdentifier(),
                                '%setting%' => $settingKey,
                                '%type%' => 'hash',
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
     */
    public function getFieldTypeIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     */
    protected function createValueFromInput($inputValue): Value
    {
        if (\is_array($inputValue)) {
            foreach ($inputValue as $index => $inputValueItem) {
                if (!$inputValueItem instanceof Meta) {
                    throw new InvalidArgumentType('$inputValue['.$index.']', Meta::class, $inputValueItem);
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
    protected function checkValueStructure(CoreValue $value): void
    {
        if (!\is_array($value->metas)) {
            throw new InvalidArgumentType('$value->metas', 'array', $value->metas);
        }

        foreach ($value->metas as $index => $meta) {
            if (!$meta instanceof Meta) {
                throw new InvalidArgumentType('$value->metas['.$index.']', Meta::class, $meta);
            }
        }
    }

    /**
     * Returns the empty value for this field type.
     */
    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Returns a human readable string representation from the given $value.
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return $value->__toString();
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     */
    protected function getSortInfo(CoreValue $value): bool
    {
        return false;
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     */
    public function fromHash($hash): Value
    {
        if (!\is_array($hash)) {
            return new Value([]);
        }
        $metas = [];
        foreach ($hash as $hashItem) {
            if (!\is_array($hashItem)) {
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
     */
    public function toHash(SPIValue $value): array
    {
        $hash = [];
        foreach ($value->metas as $meta) {
            /* @var Meta $meta */
            $name = $meta->getName();
            $hash[$name] = [
                'meta_name' => $name,
                'meta_content' => $meta->getContent(),
            ];
        }

        return $hash;
    }

    /**
     * Converts a $value to a persistence value.
     */
    public function toPersistenceValue(SPIValue $value): FieldValue
    {
        return new FieldValue(
            [
                'data' => null,
                'externalData' => $this->toHash($value),
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    /**
     * Converts a persistence $fieldValue to a Value.
     */
    public function fromPersistenceValue(FieldValue $fieldValue): Value
    {
        return $this->fromHash($fieldValue->externalData);
    }

    /**
     * Returns if the given $value is considered empty by the field type.
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return null === $value || $value->metas == $this->getEmptyValue()->metas;
    }
}
