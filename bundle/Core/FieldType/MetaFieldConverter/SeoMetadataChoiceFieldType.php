<?php
/**
 * NovaeZSEOBundle SeoMetadataChoiceFieldType.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2021 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\MetaFieldConverter;

use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class SeoMetadataChoiceFieldType extends SeoMetadataDefaultFieldType
{

    public function support(string $fieldType): bool
    {
        return 'select' === $fieldType;
    }

    public function fromHash($hash): Meta
    {
        $meta = new Meta();
        $meta->setName($hash['meta_name']);
        $meta->setFieldType($hash['meta_fieldtype']);
        $content = $hash['meta_content'];
        $meta->setContent($content);

        return $meta;
    }

    public function mapForm(FormBuilderInterface &$builder, array $params)
    {
        $builder->add(
            'content',
            ChoiceType::class,
            $params
        );
    }
}
