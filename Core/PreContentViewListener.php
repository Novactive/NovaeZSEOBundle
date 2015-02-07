<?php
/**
 * NovaeZSEOBundle PreContentViewListener
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\Value as MetasFieldValue;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

/**
 * Class PreContentViewListener
 */
class PreContentViewListener
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
     * Constructor
     *
     * @param Repository              $repository
     * @param MetaNameSchema          $nameSchema
     * @param ConfigResolverInterface $configResolver
     */
    public function __construct( Repository $repository, MetaNameSchema $nameSchema, ConfigResolverInterface $configResolver )
    {
        $this->metaNameSchema  = $nameSchema;
        $this->eZRepository = $repository;
        $this->configResolver = $configResolver;
    }

    /**
     * Pre Content Listener
     *
     * @param PreContentViewEvent $event
     */
    public function onPreContentView( PreContentViewEvent $event )
    {
        $contentView = $event->getContentView();
        $content     = $contentView->getParameter( "content" );
        $rootContent = null;

        $contentMetas = $this->getMetas( $content, $fallback );

        if ( $fallback )
        {
            $rootNode = $this->eZRepository->getLocationService()->loadLocation(
                $this->configResolver->getParameter( "content.tree_root.location_id" )
            );
            $rootContent = $this->eZRepository->getContentService()->loadContentByContentInfo( $rootNode->contentInfo );
            $rootMetas = $this->getMetas( $rootContent );

            foreach ( $contentMetas as $key => $meta )
            {
                $meta->setContent( $meta->isEmpty() ? $rootMetas[$key]->getContent() : $meta->getContent() );
            }
        }
        $contentView->addParameters( [ 'seoMetas' => $contentMetas ] );
    }

    /**
     * Get an array of Meta for a content
     *
     * @param Content $content
     * @param bool    $needFallback
     *
     * @return Meta[]
     */
    protected function getMetas( Content $content, &$needFallback = false )
    {
        $field = $content->getField( $this->configResolver->getParameter( 'fieldtype_metas_identifier', 'novae_zseo' ) );
        if ( ( $field ) && ( $field->value instanceof MetasFieldValue ) )
        {
            $metaConfig = $this->configResolver->getParameter( 'fieldtype_metas', 'novae_zseo' );
            $metasFieldValue = $field->value;
            $contentType = null;
            foreach ( $metasFieldValue->metas as $meta )
            {
                /** @var Meta $meta */
                if ( $meta->isEmpty() )
                {
                    $meta->setContent( $metaConfig[$meta->getName()]['default_pattern'] );

                    // check the Content Type Default Value
                    $contentType     = $this->eZRepository->getContentTypeService()->loadContentType(
                        $content->contentInfo->contentTypeId
                    );
                    $fieldDefinition = $contentType->getFieldDefinition( $field->fieldDefIdentifier );
                    $configuration   = $fieldDefinition->getFieldSettings()['configuration'];
                    // but if we need something is the configuration we take it

                    if ( $configuration[$meta->getName()] )
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
}
