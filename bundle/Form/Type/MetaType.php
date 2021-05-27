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

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\MetaFieldConverter\SeoMetadataFieldTypeRegistry;
use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Type representing meta field type.
 */
class MetaType extends AbstractType
{
    /** @var ConfigResolverInterface */
    protected $configResolver;

    /** @var SeoMetadataFieldTypeRegistry */
    protected $metadataFieldTypeRegistry;
    /**
     * FormMapper constructor.
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        SeoMetadataFieldTypeRegistry $metadataFieldTypeRegistry
    ) {
        $this->configResolver            = $configResolver;
        $this->metadataFieldTypeRegistry = $metadataFieldTypeRegistry;
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
        $metasConfig = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');

        $type    = 'text';
        $options = [
            'label' => false,
        ];
        if (isset($metasConfig[$builder->getName()])) {
            $meta    = $metasConfig[$builder->getName()];
            $type    = $meta['type'];
            $options = array_merge($options, $meta['params']);
        }

        $builder->add('name', HiddenType::class);
        $this->metadataFieldTypeRegistry->mapForm($builder, $options, $type);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Meta::class);
    }
}
