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
use Override;
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
    /**
     * Constructor.
     */
    public function __construct(
        protected ConfigResolverInterface $configResolver,
        protected SeoMetadataFieldTypeRegistry $metadataFieldTypeRegistry
    ) {
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'novaseo_fieldtype_metas_meta';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $config = [];
        $type = 'text';
        $options = [
            'label' => ucfirst($builder->getName()),
            'label_attr' => ['style' => 'font-weight:700'],
        ];

        $novaEzseo = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');
        if (isset($novaEzseo[$builder->getName()])) {
            $config = $novaEzseo[$builder->getName()];
            $type = $config['type'] ?? $type;
            if ('select' === $type) {
                $options = array_merge($options, $config['params']);
            }
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
            $constraints[] = new Length(min: $config['minLength'] ?? null, max: $config['maxLength'] ?? null);
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
