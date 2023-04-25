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
    public const IDENTIFIER = 'select';

    public function mapForm(FormBuilderInterface &$builder, array $params)
    {
        $builder->add(
            'content',
            ChoiceType::class,
            $params
        );
    }
}