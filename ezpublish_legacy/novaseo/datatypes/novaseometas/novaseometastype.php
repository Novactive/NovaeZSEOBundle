<?php
/**
 * NovaeZSEOBundle Legacy Type
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\Type;
use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\Value as FieldValue;

/**
 * Class NovaSeoMetasType
 */
class NovaSeoMetasType extends eZDataType
{

    const TABLE = "novaseo_meta";

    /**
     * Constructor
     */
    function __construct()
    {
        parent::eZDataType(
            Type::IDENTIFIER,
            ezpI18n::tr( 'extension/novaseo/text', "Nova SEO Metas", 'Datatype name' )
        );
    }

    /**
     * Validate post data, these are then used by
     * {@link NovaSeoMetasType::fetchObjectAttributeHTTPInput()}
     *
     * @param eZHTTPTool               $http
     * @param string                   $base
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return eZInputValidator
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable(
            "{$base}_data_novaseometas_{$contentObjectAttribute->attribute(
                'contentclass_attribute_identifier'
            )}_keyvalue_{$contentObjectAttribute->attribute( 'id' )}"
        ) )
        {
            $metasKv = $http->postVariable(
                "{$base}_data_novaseometas_{$contentObjectAttribute->attribute(
                    'contentclass_attribute_identifier'
                )}_keyvalue_{$contentObjectAttribute->attribute( 'id' )}"
            );
            //@todo: Maybe check some errors here
            unset( $metasKv );
            //$contentObjectAttribute->setValidationError( $mess );
            //$contentObjectAttribute->setHasValidationError();
            //return eZInputValidator::STATE_INVALID;
        }

        return eZInputValidator::STATE_ACCEPTED;
    }

    /**
     * Set parameters from post data, expects post data to be validated by
     * {@link eZNovaSeoMetasType::validateObjectAttributeHTTPInput()}
     *
     * @param eZHTTPTool               $http
     * @param string                   $base
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return boolean
     */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $metas = [];
        if ( $http->hasPostVariable( 'PublishButton' ) )
        {
            if ( $http->hasPostVariable(
                "{$base}_data_novaseometas_{$contentObjectAttribute->attribute(
                    'contentclass_attribute_identifier'
                )}_keyvalue_{$contentObjectAttribute->attribute( 'id' )}"
            ) )
            {
                $metasKv = $http->postVariable(
                    "{$base}_data_novaseometas_{$contentObjectAttribute->attribute(
                        'contentclass_attribute_identifier'
                    )}_keyvalue_{$contentObjectAttribute->attribute( 'id' )}"
                );
                foreach ( $metasKv as $metaKey => $metaValue )
                {
                    $meta = new Meta();
                    $meta->setName( $metaKey );
                    $meta->setContent( $metaValue );
                    $metas[] = $meta;
                }
            }
        }
        $contentObjectAttribute->setContent( new FieldValue( $metas ) );

        return true;
    }

    /**
     * Stores the content, as set by {@link NovaSeoMetasType::fetchObjectAttributeHTTPInput()}
     * or {@link NovaSeoMetasType::initializeObjectAttribute()}
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return bool
     */
    function storeObjectAttribute( $contentObjectAttribute )
    {
        $metas = $contentObjectAttribute->content();
        $db    = eZDB::instance();
        $db->begin();
        $this->deleteStoredObjectAttribute( $contentObjectAttribute );
        /** @var FieldValue $metas */
        foreach ( $metas->metas as $meta )
        {
            $db->query(
                "INSERT INTO " . self::TABLE . " SET
                    objectattribute_id = {$contentObjectAttribute->attribute( 'id' )},
                    objectattribute_version= {$contentObjectAttribute->attribute( 'version' )},
                    meta_name = \"" . $db->escapeString( $meta->getName() ) . "\",
                    meta_content = \"" . $db->escapeString( $meta->getContent() ) . "\"
            "
            );
        }
        $db->commit();

        return true;
    }

    /**
     * Init attribute ( also handles version to version copy, and attribute to attribute copy )
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null                 $currentVersion
     * @param eZContentObjectAttribute $originalContentObjectAttribute
     */
    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $metas = $originalContentObjectAttribute->content();
            /** @var FieldValue $metas */
            if ( $metas instanceof FieldValue )
            {
                $contentObjectAttribute->setContent( $metas );
                $this->storeObjectAttribute( $contentObjectAttribute );
            }
        }
    }

    /**
     * Return content (xxx object), either stored one or a new empty one based on
     * if attribute has data or not
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return array
     */
    function objectAttributeContent( $contentObjectAttribute )
    {
        if ( $contentObjectAttribute->attribute( 'id' ) )
        {
            $db         = eZDB::instance();
            $metasArray = $db->arrayQuery(
                "SELECT * FROM " . self::TABLE . " WHERE
                        objectattribute_id = {$contentObjectAttribute->attribute( 'id' )} AND
                        objectattribute_version= {$contentObjectAttribute->attribute( 'version' )}
                        "
            );
            $metas      = [];
            foreach ( $metasArray as $row )
            {
                $meta = new Meta();
                $meta
                    ->setName( $row['meta_name'] )
                    ->setContent( $row['meta_content'] );
                $metas[] = $meta;
            }

            return new FieldValue( $metas );
        }
        return new FieldValue( [] );
    }

    /**
     * Indicates if attribute has content or not
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return bool
     */
    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $db         = eZDB::instance();
        $metasArray = $db->arrayQuery(
            "SELECT * FROM " . self::TABLE . " WHERE
                    objectattribute_id = {$contentObjectAttribute->attribute( 'id' )},
                    objectattribute_version= {$contentObjectAttribute->attribute( 'version' )}
                    "
        );

        return count( $metasArray ) > 0;
    }

    /**
     * Delete map data when attribute (version) is removed
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null                 $version
     */
    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        if ( $contentObjectAttribute->attribute( 'id' ) )
        {
            $db = eZDB::instance();
            $db->query(
                "DELETE FROM " . self::TABLE .
                " WHERE objectattribute_id = {$contentObjectAttribute->attribute( 'id' )} AND
                        objectattribute_version= {$contentObjectAttribute->attribute( 'version' )}
                        "
            );
        }
        unset( $version ); //CodeSniffer tricks..
    }

    /**
     * Generate meta data of attribute
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return string
     */
    function metaData( $contentObjectAttribute )
    {
        return static::toString( $contentObjectAttribute );
    }

    /**
     * Indicates that datatype is searchable {@link NovaSeoMetasType::metaData()}
     *
     * @return bool
     */
    function isIndexable()
    {
        return false;
    }

    /**
     * Returns sort value for attribute
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return string
     */
    function sortKey( $contentObjectAttribute )
    {
        return null;
    }

    /**
     * Tells what kind of sort value is returned, see {@link NovaSeoMetasType::sortKey()}
     *
     * @return string
     */
    function sortKeyType()
    {
        return 'string';
    }

    /**
     * Return string data for cosumption by {@link NovaSeoMetasType::fromString()}
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     *
     * @return string
     */
    function toString( $contentObjectAttribute )
    {
        return null;
    }

    /**
     * Store data from string format as created in  {@link NovaSeoMetasType::toString()}
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param string                   $string
     */
    function fromString( $contentObjectAttribute, $string )
    {
    }

    /**
     * Generate title of attribute
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param string|null              $name
     *
     * @return string
     */
    function title( $contentObjectAttribute, $name = null )
    {
        unset( $name ); //CodeSniffer tricks..
        return (string)$contentObjectAttribute->content();
    }
}

eZDataType::register( Type::IDENTIFIER, 'NovaSeoMetasType' );
