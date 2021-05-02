<?php
/**
 * NovaeZSEOBundle MetaType.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Form\Type;

use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Type representing meta field type.
 */
class MetaType extends AbstractType
{
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 'novaseo_fieldtype_metas_meta';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $label = false;
        $type  = '';
        if (isset($options['metaConfig'][$builder->getName()])) {
            $meta  = $options['metaConfig'][$builder->getName()];
            $type  = $meta['type'];
            $label = isset($meta['params']) ? $meta['params']['label'] : $label;
        }
        $builder
            ->add('name', HiddenType::class);
            if ($type == 'boolean') {
                $builder->add('content', CheckboxType::class, [
                    'label' => $label,
                    'attr'  => [
                        'class'        => 'form-control',
                        'false_values' => '0'
                    ]
                ]);
            } else {
                $builder->add('content', TextType::class, [
                    'label'      => false,
                    'empty_data' => '',
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Meta::class, 'metaConfig' => null]);
    }
}
