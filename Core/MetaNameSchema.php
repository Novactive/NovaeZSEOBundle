<?php
/**
 * NovaeZSEOBundle MetaNameSchema
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\Parser\FieldType\RichText;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\RichText\Converter as RichTextConverterInterface;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\Repository\Helper\NameSchemaService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\SPI\Variation\VariationHandler;
use eZ\Publish\Core\FieldType\RichText\Value as RichTextValue;
use eZ\Publish\Core\FieldType\Relation\Value as RelationValue;
use eZ\Publish\Core\FieldType\RelationList\Value as RelationListValue;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\Core\FieldType\ImageAsset\Value as ImageAssetValue;
use eZ\Publish\SPI\Persistence\Content\Type as SPIContentType;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\SPI\Persistence\Content\Type\Handler as ContentTypeHandler;
use eZ\Publish\Core\Base\Container\ApiLoader\FieldTypeCollectionFactory;
use eZ\Publish\Core\Repository\Helper\FieldTypeRegistry;
use eZ\Publish\API\Repository\Repository as RepositoryInterface;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\Repository\Helper\ContentTypeDomainMapper;
use eZ\Publish\SPI\Persistence\Content\Language\Handler as ContentLanguageHandler;
use eZ\Publish\Core\FieldType\RelationList\NameableField as RelationListNameableField;

/**
 * Class MetaNameSchema
 */
class MetaNameSchema extends NameSchemaService
{
    /**
     * Prioritized languages
     *
     * @var array
     */
    protected $languages;

    /**
     * Html5 converter
     *
     * @var RichTextConverterInterface
     */
    protected $richTextConverter;

    /**
     * Alias Generator
     *
     * @var VariationHandler
     */
    protected $imageVariationService;

    /**
     * The repository
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * Translation Helper
     *
     * @var TranslationHelper
     */
    protected $translationHelper;

    /**
     * Meta content data max length
     *
     * @var int
     */
    protected $fieldContentMaxLength = 255;

    /**
     * FieldTypeRegistry
     *
     * @var FieldTypeRegistry
     */
    protected $fieldTypeRegistry;

    /**
     * RelationListNameableField
     *
     * @var RelationListNameableField
     */
    protected $relationListNameableField;

    /**
     * MetaNameSchema constructor.
     *
     * @param ContentTypeHandler         $contentTypeHandler
     * @param FieldTypeCollectionFactory $collectionFactory
     * @param RepositoryInterface        $repository
     * @param TranslationHelper          $helper
     * @param array                      $settings
     */
    public function __construct(
        ContentTypeHandler $contentTypeHandler,
        FieldTypeCollectionFactory $collectionFactory,
        ContentLanguageHandler $languageHandler,
        RepositoryInterface $repository,
        TranslationHelper $helper,
        RelationListNameableField $relationListNameableField,
        array $settings
    ) {
        $fieldTypes              = $collectionFactory->getFieldTypes();
        $nameable                = new \eZ\Publish\Core\Repository\Helper\NameableFieldTypeRegistry($fieldTypes);
        $this->fieldTypeRegistry = new FieldTypeRegistry($fieldTypes);
        $settings['limit']       = $this->fieldContentMaxLength;
        $handler                 = new ContentTypeDomainMapper(
            $contentTypeHandler,
            $languageHandler,
            $this->fieldTypeRegistry
        );
        parent::__construct($contentTypeHandler, $handler, $nameable, $settings);
        $this->repository        = $repository;
        $this->translationHelper = $helper;
        $this->relationListNameableField = $relationListNameableField;
    }

    /**
     * Set prioritized languages
     *
     * @param array $languages
     */
    public function setLanguages(array $languages = null)
    {
        $this->languages = $languages;
    }

    /**
     * Set Rich text converter
     *
     * @param RichTextConverterInterface $richTextConverter
     */
    public function setRichTextConverter($richTextConverter)
    {
        $this->richTextConverter = $richTextConverter;
    }

    /**
     * Set the Image Variation Service
     *
     * @param VariationHandler $handler
     */
    public function setImageVariationService(VariationHandler $handler)
    {
        $this->imageVariationService = $handler;
    }

    /**
     * Resolve a Meta Value
     *
     * @param Meta        $meta
     * @param Content     $content
     * @param ContentType $contentType
     *
     * @return boolean
     */
    public function resolveMeta(Meta $meta, Content $content, ContentType $contentType = null)
    {
        if ($contentType === null) {
            $contentType = $this->repository->getContentTypeService()->loadContentType(
                $content->contentInfo->contentTypeId
            );
        }

        $resolveMultilingue = $this->resolve(
            $meta->getContent(),
            $contentType,
            $content->fields,
            $content->versionInfo->languageCodes
        );
        // we don't fallback on the other languages... it would be very bad for SEO to mix the languages
        if ((array_key_exists($this->languages[0], $resolveMultilingue)) &&
            ($resolveMultilingue[$this->languages[0]] != '')
        ) {
            $meta->setContent($resolveMultilingue[$this->languages[0]]);

            return true;
        }
        $meta->setContent("");

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldTitles(array $schemaIdentifiers, $contentType, array $fieldMap, $languageCode)
    {
        $fieldTitles = array ();

        foreach ($schemaIdentifiers as $fieldDefinitionIdentifier) {
            if (isset($fieldMap[$fieldDefinitionIdentifier][$languageCode])) {
                if ($contentType instanceof SPIContentType) {
                    $fieldDefinition = null;
                    foreach ($contentType->fieldDefinitions as $spiFieldDefinition) {
                        if ($spiFieldDefinition->identifier === $fieldDefinitionIdentifier) {
                            $fieldDefinition = $this->contentTypeDomainMapper->buildFieldDefinitionDomainObject(
                                $spiFieldDefinition
                            );
                            break;
                        }
                    }

                    if ($fieldDefinition === null) {
                        $fieldTitles[$fieldDefinitionIdentifier] = '';
                        continue;
                    }
                } elseif ($contentType instanceof ContentType) {
                    $fieldDefinition = $contentType->getFieldDefinition($fieldDefinitionIdentifier);
                } else {
                    throw new InvalidArgumentType('$contentType', 'API or SPI variant of ContentType');
                }

                //eZ XML Text
                if ($fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof RichTextValue) {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleRichTextValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode]
                    );
                    continue;
                }

                //eZ Object Relation
                if ($fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof RelationValue) {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleRelationValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode],
                        $languageCode
                    );
                    continue;
                }

                //eZ Object Relation List
                if ($fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof RelationListValue) {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleRelationListValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode],
                        $fieldDefinition,
                        $languageCode
                    );
                    continue;
                }

                // eZ Image
                if ($fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof ImageValue) {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleImageValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode],
                        $fieldDefinitionIdentifier,
                        $languageCode
                    );
                    continue;
                }

                // eZ Image asset
                if ($fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof ImageAssetValue) {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleImageAssetValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode],
                        $fieldDefinitionIdentifier,
                        $languageCode
                    );
                    continue;
                }

                $fieldType = $this->fieldTypeRegistry->getFieldType($fieldDefinition->fieldTypeIdentifier);
                $fieldTitles[$fieldDefinitionIdentifier] = $fieldType->getName(
                    $fieldMap[$fieldDefinitionIdentifier][$languageCode],
                    $fieldDefinition,
                    $languageCode
                );
            }
        }

        return $fieldTitles;
    }

    /**
     * Get the Variation of the Image ( medium )
     *
     * @param mixed  $value
     * @param string $identifier
     * @param string $languageCode
     * @param string $variationName
     *
     * @return string
     */
    protected function getVariation($value, $identifier, $languageCode, $variationName)
    {
        // @todo: I don't know how to do differently here...
        $field     = new Field(
            [
                'value'              => $value,
                'fieldDefIdentifier' => $identifier,
                'languageCode'       => $languageCode
            ]
        );
        $variation = $this->imageVariationService->getVariation($field, new VersionInfo(), $variationName);

        return $variation->uri;
    }

    /**
     * Get a Text from a Rich text field type
     *
     * @param RichTextValue $value
     *
     * @return string
     */
    protected function handleRichTextValue(RichTextValue $value)
    {
        return trim(strip_tags($this->richTextConverter->convert($value->xml)->saveHTML()));
    }

    /**
     * Get the Relation in text or URL
     *
     * @param RelationValue $value
     * @param string        $languageCode
     *
     * @return string
     */
    protected function handleRelationValue(RelationValue $value, $languageCode)
    {
        if (!$value->destinationContentId) {
            return '';
        }
        $relatedContent = $this->repository->getContentService()->loadContent($value->destinationContentId);
        // @todo: we can probably be better here and handle more than just "image"
        if ($fieldImageValue = $relatedContent->getFieldValue('image')) {
            if ($fieldImageValue->uri) {
                return $this->getVariation($fieldImageValue, "image", $languageCode, "social_network_image");
            }
        }

        return $this->translationHelper->getTranslatedContentName($relatedContent, $languageCode);
    }

    protected function handleRelationListValue(RelationListValue $value, $fieldDefinition, $languageCode)
    {
        return $this->relationListNameableField->getFieldName($value, $fieldDefinition, $languageCode);
    }

    /**
     * Handle a Image attribute
     *
     * @param ImageValue $value
     * @param string     $fieldDefinitionIdentifier
     * @param string     $languageCode
     *
     * @return string
     */
    protected function handleImageValue(ImageValue $value, $fieldDefinitionIdentifier, $languageCode)
    {
        if (!$value->uri) {
            return '';
        }

        return $this->getVariation(
            $value,
            $fieldDefinitionIdentifier,
            $languageCode,
            "social_network_image"
        );
    }

    /**
     * Handle a Image Asset attribute
     *
     * @param ImageAssetValue $value
     * @param string     $fieldDefinitionIdentifier
     * @param string     $languageCode
     *
     * @return string
     */
    protected function handleImageAssetValue(ImageAssetValue $value, $fieldDefinitionIdentifier, $languageCode)
    {
        if (!$value->destinationContentId) {
            return '';
        }

        $content = $this->repository->getContentService()->loadContent($value->destinationContentId);

        foreach ($content->getFields() as $field) {
            if ($field->value instanceof ImageValue) {
                return $this->handleImageValue($field->value, $fieldDefinitionIdentifier, $languageCode);
            }
        }

        return  '';
    }
}
