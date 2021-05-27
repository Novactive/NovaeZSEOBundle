<?php
/**
 * NovaeZSEOBundle SeoMetadataFieldTypeRegistry.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2021 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\MetaFieldConverter;

use Symfony\Component\Form\FormBuilderInterface;

class SeoMetadataFieldTypeRegistry
{
    /** @var SeoMetadataFieldTypeInterface[] */
    protected $metaFieldTypes;

    /**
     * SeoMetadataFieldTypeRegistry constructor.
     *
     * @param SeoMetadataFieldTypeInterface[] $metaFieldTypes
     */
    public function __construct(iterable $metaFieldTypes)
    {
        foreach ($metaFieldTypes as $metaFieldType) {
            $this->addMetaFieldType($metaFieldType);
        }
    }

    public function addMetaFieldType(SeoMetadataFieldTypeInterface $metaFieldType): void
    {
        $this->metaFieldTypes[] = $metaFieldType;
    }

    public function fromHash($hash): array
    {
        $metas = [];
        foreach ($hash as $hashItem) {
            if (!is_array($hashItem)) {
                continue;
            }
            foreach ($this->metaFieldTypes as $metaFieldType) {
                if (!$metaFieldType->support($hashItem['meta_fieldtype'] ?? SeoMetadataDefaultFieldType::IDENTIFIER)) {
                    continue;
                }
                $metas[] = $metaFieldType->fromHash($hashItem);
            }
        }

        return $metas;
    }

    public function mapForm(FormBuilderInterface &$builder, array $params, string $fieldType)
    {
        foreach ($this->metaFieldTypes as $metaFieldType) {
            if (!$metaFieldType->support($fieldType)) {
                continue;
            }
            $metaFieldType->mapForm($builder, $params);
        }
    }
}
