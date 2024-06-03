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

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\MetaFieldConverter\SeoMetadataFieldTypeRegistry;
use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form Type representing meta field type.
 */
class MetaType extends AbstractType
{
    protected SeoMetadataFieldTypeRegistry $metadataFieldTypeRegistry;
    private ConfigResolverInterface $configResolver;

    /**
     * Constructor.
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        SeoMetadataFieldTypeRegistry $metadataFieldTypeRegistry
    ) {
        $this->metadataFieldTypeRegistry = $metadataFieldTypeRegistry;
        $this->configResolver = $configResolver;
    }

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
        $config = [];
        $type = 'text';
        $options = [
            'label' => false,
            'label_attr' => ['style' => 'display:none'],
        ];

        $novaEzseo = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');
        if (isset($novaEzseo[$builder->getName()])) {
            $config = $novaEzseo[$builder->getName()];
            $type = $config['type'];
            $options = array_merge($options, $config['params']);
        }

        $constraints = $this->getConstraints($config);
        $options['constraints'] = $constraints;

        $builder
            ->add('name', HiddenType::class);
        $this->metadataFieldTypeRegistry->mapForm($builder, $options, $type);
    }

    private function getConstraints(array $config)
    {
        $constraints = [];

        if (isset($config['minLength']) || isset($config['maxLength'])) {
            $constraints[] = new Length([
                'min' => $config['minLength'] ?? null,
                'max' => $config['maxLength'] ?? null,
            ]);
        }

        if (isset($config['required'])) {
            $constraints[] = new NotBlank();
        }

        return $constraints;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Meta::class,
            'cascade_validation' => false,
        ]);
    }
}
