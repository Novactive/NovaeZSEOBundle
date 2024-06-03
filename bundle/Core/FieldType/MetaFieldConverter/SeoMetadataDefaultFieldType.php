<?php

/**
 * NovaeZSEOBundle SeoMetadataDefaultFieldType.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2021 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\MetaFieldConverter;

use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SeoMetadataDefaultFieldType implements SeoMetadataFieldTypeInterface
{
    public const IDENTIFIER = 'text';

    public function support(string $fieldType): bool
    {
        return static::IDENTIFIER === $fieldType;
    }

    public function fromHash($hash): Meta
    {
        $meta = new Meta();
        $meta->setName($hash['meta_name']);
        $meta->setFieldType(self::IDENTIFIER);
        $content = $hash['meta_content'];
        $meta->setContent($content);

        return $meta;
    }

    public function mapForm(FormBuilderInterface &$builder, array $params)
    {
        $params['empty_data'] = '';
        $builder->add(
            'content',
            TextType::class,
            $params
        );
    }
}
