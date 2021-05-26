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
    protected $metaData;
    /**
     * FormMapper constructor.
     */
    public function __construct(ConfigResolverInterface $configResolver, SeoMetadataFieldTypeRegistry $metaData)
    {
        $this->configResolver = $configResolver;
        $this->metaData = $metaData;
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
            'label'      => false,
            'empty_data' => '',
        ];
        if (isset($metasConfig[$builder->getName()])) {
            $meta    = $metasConfig[$builder->getName()];
            $type    = $meta['type'];
            $options = !empty($meta['params']) ? $meta['params'] : $options;
        }

        $builder->add('name', HiddenType::class);
        $this->metaData->mapForm($builder, $options, $type);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Meta::class);
    }
}
