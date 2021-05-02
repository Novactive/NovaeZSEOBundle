<?php
/**
 * NovaeZSEOBundle MetasCollectionType.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Type representing metas collection field type.
 */
class MetasCollectionType extends AbstractType
{
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 'novaseo_fieldtype_metas_metas';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'allow_add'          => false,
                'allow_delete'       => false,
                'entry_type'         => MetaType::class,
                'entry_options'      => ['required' => false, 'metaConfig' => null],
                'required'           => false,
                'label'              => 'field_definition',
                'translation_domain' => 'fieldtypes',
            ]
        );
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }
}
