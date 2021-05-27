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

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;

class SeoMetadataFieldTypeRegistry
{
    /** @var SeoMetadataFieldTypeInterface[] */
    protected $metaFieldTypes;

    /** @var ConfigResolverInterface */
    protected $configResolver;

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

    /**
     * @required
     * @param ConfigResolverInterface $configResolver
     */
    public function setConfigResolver( ConfigResolverInterface $configResolver ): void
    {
        $this->configResolver = $configResolver;
    }

    public function addMetaFieldType(SeoMetadataFieldTypeInterface $metaFieldType): void
    {
        $this->metaFieldTypes[] = $metaFieldType;
    }

    public function fromHash($hash): array
    {
        $metasConfig = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');

        $metas = [];
        foreach ($hash as $hashItem) {
            if (!is_array($hashItem)) {
                continue;
            }
            $fieldConfig = $metasConfig[$hashItem['meta_name']] ?? null;
            $fieldType = $fieldConfig['type'] ?? SeoMetadataDefaultFieldType::IDENTIFIER;
            foreach ($this->metaFieldTypes as $metaFieldType) {
                if (!$metaFieldType->support($fieldType)) {
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
