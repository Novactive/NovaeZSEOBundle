<?php
/**
 * NovaeZSEOBundle NovaeZSEOExtension
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Twig;

use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\Value as MetasFieldValue;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Novactive\Bundle\eZSEOBundle\Core\MetaNameSchema;
use Novactive\Bundle\eZSEOBundle\Core\Meta;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter;
use Novactive\Bundle\eZSEOBundle\Core\CustomFallbackInterface;
/**
 * Class NovaeZSEOExtension
 */
class NovaeZSEOExtension extends \Twig_Extension
{
    /**
     * The eZ Publish object name pattern service (extended)
     *
     * @var MetaNameSchema
     */
    protected $metaNameSchema;

    /**
     * ConfigResolver useful to get the config aware of siteaccess
     *
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * The eZ Publish API
     *
     * @var Repository
     */
    protected $eZRepository;

    /**
     * Locale Converter
     *
     * @var LocaleConverter
     */
    protected $localeConverter;


    /**
     * CustomFallBack Service
     *
     * @var CustomFallbackInterface
     */
    protected $customFallBackService;

    /**
     * Constructor
     *
     * @param Repository              $repository
     * @param MetaNameSchema          $nameSchema
     * @param ConfigResolverInterface $configResolver
     * @param LocaleConverter         $localeConverter
     */
    public function __construct(
        Repository $repository,
        MetaNameSchema $nameSchema,
        ConfigResolverInterface $configResolver,
        LocaleConverter $localeConverter
    )
    {
        $this->metaNameSchema  = $nameSchema;
        $this->eZRepository    = $repository;
        $this->configResolver  = $configResolver;
        $this->localeConverter = $localeConverter;
    }

    /**
     * Set the CustomFallback Service
     *
     * @param CustomFallbackInterface $service
     */
    public function setCustomFallbackService( CustomFallbackInterface $service )
    {
        $this->customFallBackService = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter( 'compute_novaseometas', [ $this, 'computeMetas' ] ),
            new \Twig_SimpleFilter( 'getposixlocale_novaseometas', [ $this, 'getPosixLocale' ] ),
            new \Twig_SimpleFilter( 'fallback_novaseometas', [ $this, 'getFallbackedMetaContent' ] )
        ];
    }

    /**
     * Get PosixLocale
     *
     * @param string $eZLocale
     *
     * @return null|string
     */
    public function getPosixLocale( $eZLocale )
    {
        return $this->localeConverter->convertToPOSIX( $eZLocale );
    }


    public function getFallbackedMetaContent( ContentInfo $contentInfo, $metaName )
    {
        if ( $this->customFallBackService instanceof CustomFallbackInterface )
        {
            return $this->customFallBackService->getMetaContent( $metaName, $contentInfo );
        }
        return '';
    }

    /**
     * Compute Metas of the Field thanks to its Content and the Fallback
     *
     * @param Field       $field
     * @param ContentInfo $contentInfo
     *
     * @return string
     */
    public function computeMetas( Field $field, ContentInfo $contentInfo )
    {
        $fallback = false;
        $languages = $this->configResolver->getParameter( 'languages' );
        $contentType = $this->eZRepository->getContentTypeService()->loadContentType(
            $contentInfo->contentTypeId
        );
        $content = $this->eZRepository->getContentService()->loadContentByContentInfo( $contentInfo, $languages );
        $contentMetas = $this->innerComputeMetas( $content, $field, $contentType, $fallback );
        if ( $fallback && !$this->customFallBackService)
        {
            $rootNode = $this->eZRepository->getLocationService()->loadLocation(
                $this->configResolver->getParameter( "content.tree_root.location_id" )
            );
            $rootContent = $this->eZRepository->getContentService()->loadContentByContentInfo( $rootNode->contentInfo, $languages );
            $rootContentType = $this->eZRepository->getContentTypeService()->loadContentType(
                $rootContent->contentInfo->contentTypeId
            );
            // We need to load the good field too
            $metasIdentifier = $this->configResolver->getParameter( 'fieldtype_metas_identifier', 'novae_zseo' );
            $rootMetas = $this->innerComputeMetas( $rootContent, $metasIdentifier, $rootContentType, $fallback );
            foreach ( $contentMetas as $key => $metaContent )
            {
                if ( array_key_exists( $key, $rootMetas ) )
                {
                    $metaContent->setContent( $metaContent->isEmpty() ? $rootMetas[$key]->getContent() : $metaContent->getContent() );
                }
            }
        }
        return '';
    }

    /**
     * Compute Meta by reference
     *
     * @param Content      $content
     * @param string|Field $fieldDefIdentifier
     * @param ContentType  $contentType
     * @param bool         $needFallback
     *
     * @return Meta[]
     */
    protected function innerComputeMetas( Content $content, $fieldDefIdentifier, ContentType $contentType, &$needFallback = false )
    {
        if ( $fieldDefIdentifier instanceof Field )
        {
            $metasFieldValue    = $fieldDefIdentifier->value;
            $fieldDefIdentifier = $fieldDefIdentifier->fieldDefIdentifier;
        }
        else
        {
            $metasFieldValue = $content->getFieldValue( $fieldDefIdentifier );
        }

        if ( $metasFieldValue instanceof MetasFieldValue )
        {
            $metasConfig = $this->configResolver->getParameter( 'fieldtype_metas', 'novae_zseo' );
            // as the configuration is the last fallback we need to loop on it.
            foreach ( $metasConfig as $metaName => $metasSettings )
            {
                if ( $metasFieldValue->nameExists( $metaName ) )
                {
                    $meta = $metasFieldValue->metas[$metaName];
                }
                else
                {
                    $meta = new Meta( $metaName );
                    $metasFieldValue->metas[$metaName] = $meta;
                }

                /** @var Meta $meta */
                if ( $meta->isEmpty() )
                {
                    $meta->setContent( $metasConfig[$meta->getName()]['default_pattern'] );
                    $fieldDefinition = $contentType->getFieldDefinition( $fieldDefIdentifier );
                    $configuration   = $fieldDefinition->getFieldSettings()['configuration'];
                    // but if we need something is the configuration we take it
                    if ( isset($configuration[$meta->getName()]) && !empty($configuration[$meta->getName()])  )
                    {
                        $meta->setContent( $configuration[$meta->getName()] );
                    }
                }
                if ( !$this->metaNameSchema->resolveMeta( $meta, $content, $contentType ) )
                {
                    $needFallback = true;
                }
            }
            return $metasFieldValue->metas;
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'novaezseo_extension';
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        $identifier = $this->configResolver->getParameter( "fieldtype_metas_identifier", "novae_zseo" );
        $metas      = $this->configResolver->getParameter( "default_metas", "novae_zseo" );
        $links      = $this->configResolver->getParameter( "default_links", "novae_zseo" );
        $gatracker  = $this->configResolver->getParameter( "google_gatracker", "novae_zseo" );
        $novaeZseo  = [
            "fieldtype_metas_identifier" => $identifier,
            "default_metas"              => $metas,
            "default_links"              => $links,
            "google_gatracker"           => $gatracker != "~" ? $gatracker : null
        ];

        return [ 'novae_zseo' => $novaeZseo ];
    }
}
