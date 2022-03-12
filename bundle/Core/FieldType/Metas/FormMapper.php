<?php

/**
 * NovaeZSEOBundle FormMapper.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\AdminUi\FieldType\FieldDefinitionFormMapperInterface;
use Ibexa\AdminUi\Form\Data\FieldDefinitionData;
use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Ibexa\Contracts\ContentForms\FieldType\FieldValueFormMapperInterface;
use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Novactive\Bundle\eZSEOBundle\Form\Type\MetasFieldType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormMapper implements FieldDefinitionFormMapperInterface, FieldValueFormMapperInterface
{
    /** @var ConfigResolverInterface */
    protected $configResolver;

    /**
     * FormMapper constructor.
     */
    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    /**
     * "Maps" FieldDefinition form to current FieldType.
     * Gives the opportunity to enrich $fieldDefinitionForm with custom fields for:
     * - validator configuration,
     * - field settings
     * - default value.
     *
     * @param FormInterface       $fieldDefinitionForm form for current FieldDefinition
     * @param FieldDefinitionData $data                underlying data for current FieldDefinition form
     */
    public function mapFieldDefinitionForm(FormInterface $fieldDefinitionForm, FieldDefinitionData $data): void
    {
        $metasConfig = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');

        $aConfigurations = $data->fieldDefinition->fieldSettings['configuration'];
        foreach (array_keys($metasConfig) as $key) {
            if (!isset($aConfigurations[$key])) {
                $aConfigurations[$key] = '';
            }
        }
        $data->fieldSettings['configuration'] = $aConfigurations;

        $fieldDefinitionForm
            ->add(
                'configuration',
                CollectionType::class,
                [
                    'entry_type' => TextType::class,
                    'entry_options' => ['required' => false],
                    'required' => false,
                    'property_path' => 'fieldSettings[configuration]',
                    'label' => 'field_definition.novaseometas.configuration',
                ]
            );
    }

    /**
     * Maps Field form to current FieldType.
     * Allows to add form fields for content edition.
     *
     * @param FormInterface $fieldForm form for the current Field
     * @param FieldData     $data      underlying data for current Field form
     */
    public function mapFieldValueForm(FormInterface $fieldForm, FieldData $data)
    {
        $fieldDefinition = $data->fieldDefinition;
        $formConfig = $fieldForm->getConfig();

        $metasConfig = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');

        if (empty($data->value->metas)) {
            foreach (array_keys($metasConfig) as $key) {
                $data->value->metas[$key] = new Meta($key, '');
            }
        }

        // able to add a meta which is not existant on your draft but on the configuration yml
        foreach ($metasConfig as $key => $arrayValue) {
            if (!isset($data->value->metas[$key])) {
                $data->value->metas[$key] = new Meta($key, '');
            }
        }
        // to avoid an 500 error, unset a meta if you have a draft which is not ISO with your configuration YML.
        // able to not delete an enter on the table nova meta.
        // And it preserve your historic for each version of objet
        foreach ($data->value->metas as $key => $arrayValue) {
            if (!isset($metasConfig[$key])) {
                unset($data->value->metas[$key]);
            }
        }

        $fieldForm
            ->add(
                $formConfig->getFormFactory()->createBuilder()
                           ->create(
                               'value',
                               MetasFieldType::class,
                               [
                                   'required' => $fieldDefinition->isRequired,
                                   'label' => $fieldDefinition->getName($formConfig->getOption('languageCode')),
                               ]
                           )
                           ->setAutoInitialize(false)
                           ->getForm()
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('translation_domain', 'novaseo_content_type');
    }
}
