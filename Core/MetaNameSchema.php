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

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\Repository\NameSchemaService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\FieldType\XmlText\Converter\Html5 as Html5Converter;
use eZ\Publish\SPI\Variation\VariationHandler;
use eZ\Publish\Core\FieldType\XmlText\Value as XmlTextValue;
use eZ\Publish\Core\FieldType\Relation\Value as RelationValue;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;

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
     * @var Html5Converter
     */
    protected $html5Converter;

    /**
     * Alias Generator
     *
     * @var VariationHandler
     */
    protected $imageVariationService;

    /**
     * Meta content data max length
     *
     * @var int
     */
    protected $fieldContentMaxLength = 255;

    /**
     * Set prioritized languages
     *
     * @param array $languages
     */
    public function setLanguages( array $languages = null )
    {
        $this->languages = $languages;
    }

    /**
     * Set HTML Converter
     *
     * @param Html5Converter $converter
     */
    public function setHtml5Converter( Html5Converter $converter )
    {
        $this->html5Converter = $converter;
    }

    /**
     * Set the Image Variation Service
     *
     * @param VariationHandler $handler
     */
    public function setImageVariationService( VariationHandler $handler )
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
    public function resolveMeta( Meta $meta, Content $content, ContentType $contentType = null )
    {

        $this->settings = array(
            'limit' => $this->fieldContentMaxLength,
            'sequence' => '...',
        );

        if ( $contentType === null )
        {
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
        if ( ( array_key_exists( $this->languages[0], $resolveMultilingue ) ) &&
             ( $resolveMultilingue[$this->languages[0]] != '' )
        )
        {
            $meta->setContent( $resolveMultilingue[$this->languages[0]] );
            return true;
        }
        $meta->setContent( "" );
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldTitles( array $schemaIdentifiers, ContentType $contentType, array $fieldMap, $languageCode )
    {
        $fieldTitles = array();

        foreach ( $schemaIdentifiers as $fieldDefinitionIdentifier )
        {
            if ( isset( $fieldMap[$fieldDefinitionIdentifier][$languageCode] ) )
            {
                $fieldDefinition = $contentType->getFieldDefinition( $fieldDefinitionIdentifier );
                $fieldType       = $this->repository->getFieldTypeService()->getFieldType(
                    $fieldDefinition->fieldTypeIdentifier
                );
                // eZ XML Text
                if ( $fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof XmlTextValue )
                {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleXmlTextValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode]
                    );
                    continue;
                }

                //eZ Object Relation
                if ( $fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof RelationValue )
                {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleRelationValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode], $languageCode
                    );
                    continue;
                }

                // eZ Image
                if ( $fieldMap[$fieldDefinitionIdentifier][$languageCode] instanceof ImageValue )
                {
                    $fieldTitles[$fieldDefinitionIdentifier] = $this->handleImageValue(
                        $fieldMap[$fieldDefinitionIdentifier][$languageCode], $fieldDefinitionIdentifier, $languageCode
                    );
                    continue;
                }

                $fieldTitles[$fieldDefinitionIdentifier] = $fieldType->getName(
                    $fieldMap[$fieldDefinitionIdentifier][$languageCode]
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
    protected function getVariation( $value, $identifier, $languageCode, $variationName )
    {
        // @todo: I don't know how to do differently here...
        $field     = new Field(
            [
                'value'              => $value,
                'fieldDefIdentifier' => $identifier,
                'languageCode'       => $languageCode
            ]
        );
        $variation = $this->imageVariationService->getVariation( $field, new VersionInfo(), $variationName );

        return $variation->uri;
    }

    /**
     * Get a Text from a XML
     *
     * @param XmlTextValue $value
     *
     * @return string
     */
    protected function handleXmlTextValue( XmlTextValue $value )
    {
        return trim( strip_tags( $this->html5Converter->convert( $value->xml ) ) );
    }

    /**
     * Get the Relation in text or URL
     *
     * @param RelationValue $value
     * @param string        $languageCode
     *
     * @return string
     */
    protected function handleRelationValue( RelationValue $value, $languageCode )
    {
        if ( !$value->destinationContentId )
        {
            return '';
        }
        $relatedContent = $this->repository->getContentService()->loadContent( $value->destinationContentId );
        // @todo: we can probably be better here and handle more than just "image"
        if ( $fieldImageValue = $relatedContent->getFieldValue( 'image' ) )
        {
            if ( $fieldImageValue->uri )
            {
                return $this->getVariation( $fieldImageValue, "image", $languageCode, "social_network_image" );
            }
        }
        return '';
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
    protected function handleImageValue( ImageValue $value, $fieldDefinitionIdentifier, $languageCode )
    {
        if ( !$value->uri )
        {
            return '';
        }
        return $this->getVariation(
            $value,
            $fieldDefinitionIdentifier,
            $languageCode,
            "social_network_image"
        );
    }
}
