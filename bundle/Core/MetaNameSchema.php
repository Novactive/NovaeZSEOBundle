<?php

/**
 * NovaeZSEOBundle MetaNameSchema.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use eZ\Publish\API\Repository\Repository as RepositoryInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\Core\FieldType\FieldTypeRegistry;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\Core\FieldType\ImageAsset\Value as ImageAssetValue;
use eZ\Publish\Core\FieldType\Relation\Value as RelationValue;
use eZ\Publish\Core\FieldType\RelationList\Type as RelationListType;
use eZ\Publish\Core\FieldType\RelationList\Value as RelationListValue;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\Repository\Helper\NameSchemaService;
use eZ\Publish\Core\Repository\Mapper\ContentTypeDomainMapper;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\SPI\Persistence\Content\Language\Handler as ContentLanguageHandler;
use eZ\Publish\SPI\Persistence\Content\Type as SPIContentType;
use eZ\Publish\SPI\Persistence\Content\Type\Handler as ContentTypeHandler;
use eZ\Publish\SPI\Variation\VariationHandler;
use EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value as RichTextValue;
use EzSystems\EzPlatformRichText\eZ\RichText\Converter as RichTextConverterInterface;

class MetaNameSchema extends NameSchemaService
{
    /**
     * @var RichTextConverterInterface
     */
    protected $richTextConverter;

    /**
     * @var VariationHandler
     */
    protected $imageVariationService;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var int
     */
    protected $fieldContentMaxLength = 255;

    /**
     * @var FieldTypeRegistry
     */
    protected $fieldTypeRegistry;

    /**
     * @var RelationListType
     */
    private $relationListField;

    /**
     * @var ConfigResolverInterface
     */
    private $configurationResolver;

    public function __construct(
        ContentTypeHandler $contentTypeHandler,
        FieldTypeRegistry $fieldTypeRegistry,
        ContentLanguageHandler $languageHandler,
        RepositoryInterface $repository,
        TranslationHelper $translationHelper,
        ConfigResolverInterface $configurationResolver,
        array $settings = []
    ) {
        $this->fieldTypeRegistry = $fieldTypeRegistry;
        $settings['limit'] = $this->fieldContentMaxLength;
        $handler = new ContentTypeDomainMapper(
            $contentTypeHandler,
            $languageHandler,
            $this->fieldTypeRegistry
        );

        parent::__construct($contentTypeHandler, $handler, $fieldTypeRegistry, $settings);

        $this->repository = $repository;
        $this->translationHelper = $translationHelper;
        $this->relationListField = $this->fieldTypeRegistry->getFieldType('ezobjectrelationlist');
        $this->configurationResolver = $configurationResolver;
    }

    public function setRichTextConverter(RichTextConverterInterface $richTextConverter): void
    {
        $this->richTextConverter = $richTextConverter;
    }

    public function setImageVariationService(VariationHandler $handler): void
    {
        $this->imageVariationService = $handler;
    }

    // @param ContentType|null $contentType: @deprecated argument.
    public function resolveMeta(Meta $meta, Content $content, ContentType $contentType = null): bool
    {
        $languages = $this->configurationResolver->getParameter('languages');

        $resolveMultilingue = $this->resolve(
            $meta->getContent(),
            $content->getContentType(),
            $content->fields,
            $content->versionInfo->languageCodes
        );
        // we don't fallback on the other languages... it would be very bad for SEO to mix the languages
        if (
            (\array_key_exists($languages[0], $resolveMultilingue)) &&
            ('' !== $resolveMultilingue[$languages[0]])
        ) {
            $meta->setContent($resolveMultilingue[$languages[0]]);

            return true;
        }
        $meta->setContent('');

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getFieldTitles(
        array $schemaIdentifiers,
        $contentType,
        array $fieldMap,
        $languageCode
    ): array {
        $fieldTitles = [];

        foreach ($schemaIdentifiers as $fieldDefinitionIdentifier) {
            if (isset($fieldMap[$fieldDefinitionIdentifier][$languageCode])) {
                if ($contentType instanceof SPIContentType) {
                    $fieldDefinition = null;
                    foreach ($contentType->fieldDefinitions as $spiFieldDefinition) {
                        if ($spiFieldDefinition->identifier === $fieldDefinitionIdentifier) {
                            $fieldDefinition = $this->contentTypeDomainMapper->buildFieldDefinitionDomainObject(
                                $spiFieldDefinition,
                                $languageCode
                            );
                            break;
                        }
                    }

                    if (null === $fieldDefinition) {
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

    protected function getVariation(
        ImageValue $value,
        string $identifier,
        string $languageCode,
        string $variationName
    ): string {
        $field = new Field(
            [
                'value' => $value,
                'fieldDefIdentifier' => $identifier,
                'languageCode' => $languageCode,
            ]
        );

        $variation = $this->imageVariationService->getVariation($field, new VersionInfo(), $variationName);

        return $variation->uri;
    }

    /**
     * Get a Text from a Rich text field type.
     */
    protected function handleRichTextValue(RichTextValue $value): string
    {
        return trim(strip_tags($this->richTextConverter->convert($value->xml)->saveHTML()));
    }

    /**
     * Get the Relation in text or URL.
     */
    protected function handleRelationValue(RelationValue $value, string $languageCode): string
    {
        if (!$value->destinationContentId) {
            return '';
        }
        $relatedContent = $this->repository->getContentService()->loadContent($value->destinationContentId);
        // @todo: we can probably be better here and handle more than just "image"
        $fieldImageValue = $relatedContent->getFieldValue('image');
        if ($fieldImageValue) {
            if ($fieldImageValue->uri) {
                return $this->getVariation(
                    $fieldImageValue,
                    'image',
                    $languageCode,
                    'social_network_image'
                );
            }
        }

        return $this->translationHelper->getTranslatedContentName($relatedContent, $languageCode);
    }

    protected function handleRelationListValue(RelationListValue $value, $fieldDefinition, $languageCode): string
    {
        return $this->relationListField->getName($value, $fieldDefinition, $languageCode);
    }

    /**
     * Handle a Image attribute.
     */
    protected function handleImageValue(ImageValue $value, $fieldDefinitionIdentifier, $languageCode): string
    {
        if (!$value->uri) {
            return '';
        }

        return $this->getVariation(
            $value,
            $fieldDefinitionIdentifier,
            $languageCode,
            'social_network_image'
        );
    }

    /**
     * Handle a Image Asset attribute.
     */
    protected function handleImageAssetValue(ImageAssetValue $value, $fieldDefinitionIdentifier, $languageCode): string
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

        return '';
    }
}
