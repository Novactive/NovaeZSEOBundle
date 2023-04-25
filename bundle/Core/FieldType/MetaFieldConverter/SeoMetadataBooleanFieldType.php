<?php
/**
 * NovaeZSEOBundle SeoMetadataBooleanFieldType.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2021 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\MetaFieldConverter;

use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class SeoMetadataBooleanFieldType extends SeoMetadataDefaultFieldType
{
    public const IDENTIFIER = 'boolean';
    public function fromHash($hash): Meta
    {
        $meta = parent::fromHash($hash);
        $content = $hash['meta_content'] == "1" ? true : false;
        $meta->setContent($content);

        return $meta;
    }

    public function mapForm(FormBuilderInterface &$builder, array $params)
    {
        $option = [
            'class'        => 'form-control',
            'false_values' => '0'
        ];
        $builder->add(
            'content',
            CheckboxType::class,
            array_merge($params, $option)
        );
    }
}