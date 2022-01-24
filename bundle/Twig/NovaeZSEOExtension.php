<?php
/**
 * NovaeZSEOBundle NovaeZSEOExtension.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Twig;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter;
use Novactive\Bundle\eZSEOBundle\Core\CustomFallbackInterface;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\Value as MetasFieldValue;
use Novactive\Bundle\eZSEOBundle\Core\Meta;
use Novactive\Bundle\eZSEOBundle\Core\MetaNameSchema;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class NovaeZSEOExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * The eZ Publish object name pattern service (extended).
     *
     * @var MetaNameSchema
     */
    protected $metaNameSchema;

    /**
     * ConfigResolver useful to get the config aware of siteaccess.
     *
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * The eZ Publish API.
     *
     * @var Repository
     */
    protected $eZRepository;

    /**
     * Locale Converter.
     *
     * @var LocaleConverter
     */
    protected $localeConverter;

    /**
     * CustomFallBack Service.
     *
     * @var CustomFallbackInterface
     */
    protected $customFallBackService;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    public function __construct(
        Repository $repository,
        MetaNameSchema $nameSchema,
        ConfigResolverInterface $configResolver,
        LocaleConverter $localeConverter
    ) {
        $this->metaNameSchema  = $nameSchema;
        $this->eZRepository    = $repository;
        $this->configResolver  = $configResolver;
        $this->localeConverter = $localeConverter;
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setCustomFallbackService(CustomFallbackInterface $service)
    {
        $this->customFallBackService = $service;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('compute_novaseometas', [$this, 'computeMetas']),
            new TwigFilter('getposixlocale_novaseometas', [$this, 'getPosixLocale']),
            new TwigFilter('fallback_novaseometas', [$this, 'getFallbackedMetaContent']),
        ];
    }

    public function getPosixLocale(string $eZLocale): ?string
    {
        return $this->localeConverter->convertToPOSIX($eZLocale);
    }

    public function getFallbackedMetaContent(ContentInfo $contentInfo, string $metaName): string
    {
        if ($this->customFallBackService instanceof CustomFallbackInterface) {
            return $this->customFallBackService->getMetaContent($metaName, $contentInfo);
        }

        return '';
    }

    /**
     * Compute Metas of the Field thanks to its Content and the Fallback.
     */
    public function computeMetas(Field $field, ContentInfo $contentInfo): string
    {
        $fallback     = false;
        $languages    = $this->configResolver->getParameter('languages');
        $contentType  = $this->eZRepository->getContentTypeService()->loadContentType(
            $contentInfo->contentTypeId
        );
        $content      = $this->eZRepository->getContentService()->loadContentByContentInfo($contentInfo, $languages);
        $contentMetas = $this->innerComputeMetas($content, $field, $contentType, $fallback);
        if ($fallback && !$this->customFallBackService) {
            $rootNode        = $this->eZRepository->getLocationService()->loadLocation(
                $this->configResolver->getParameter('content.tree_root.location_id')
            );
            $rootContent     = $this->eZRepository->getContentService()->loadContentByContentInfo(
                $rootNode->contentInfo,
                $languages
            );
            $rootContentType = $this->eZRepository->getContentTypeService()->loadContentType(
                $rootContent->contentInfo->contentTypeId
            );
            // We need to load the good field too
            $metasIdentifier = $this->configResolver->getParameter('fieldtype_metas_identifier', 'nova_ezseo');
            $rootMetas       = $this->innerComputeMetas($rootContent, $metasIdentifier, $rootContentType, $fallback);
            foreach ($contentMetas as $key => $metaContent) {
                if (\array_key_exists($key, $rootMetas)) {
                    $metaContent->setContent(
                        $metaContent->isEmpty() ? $rootMetas[$key]->getContent() : $metaContent->getContent()
                    );
                }
            }
        }

        return '';
    }

    /**
     * Compute Meta by reference.
     */
    protected function innerComputeMetas(
        Content $content,
        $fieldDefIdentifier,
        ContentType $contentType,
        &$needFallback = false
    ): array {
        if ($fieldDefIdentifier instanceof Field) {
            $metasFieldValue    = $fieldDefIdentifier->value;
            $fieldDefIdentifier = $fieldDefIdentifier->fieldDefIdentifier;
        } else {
            $metasFieldValue = $content->getFieldValue($fieldDefIdentifier);
        }

        if ($metasFieldValue instanceof MetasFieldValue) {
            $metasConfig = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');
            // as the configuration is the last fallback we need to loop on it.
            foreach (array_keys($metasConfig) as $metaName) {
                if ($metasFieldValue->nameExists($metaName)) {
                    $meta = $metasFieldValue->metas[$metaName];
                } else {
                    $meta                              = new Meta($metaName);
                    $metasFieldValue->metas[$metaName] = $meta;
                }

                /** @var Meta $meta */
                if ($meta->isEmpty()) {
                    $meta->setContent($metasConfig[$meta->getName()]['default_pattern']);
                    $fieldDefinition = $contentType->getFieldDefinition($fieldDefIdentifier);
                    $configuration   = $fieldDefinition->getFieldSettings()['configuration'];
                    // but if we need something is the configuration we take it
                    if (isset($configuration[$meta->getName()]) && !empty($configuration[$meta->getName()])) {
                        $meta->setContent($configuration[$meta->getName()]);
                    }
                }
                try {
                    if (!$this->metaNameSchema->resolveMeta($meta, $content, $contentType)) {
                        $needFallback = true;
                    }
                } catch (NotFoundException|UnauthorizedException $exception) {
                    if ($this->logger) {
                        $this->logger->error('[Nova eZ SEO] Error when resolving meta', [
                            'message' => $exception->getMessage(),
                            'contentId' => $content->id,
                        ]);
                    }
                }
            }

            return $metasFieldValue->metas;
        }

        return [];
    }

    public function getName()
    {
        return 'novaezseo_extension';
    }

    public function getGlobals(): array
    {
        $identifier     = $this->configResolver->getParameter('fieldtype_metas_identifier', 'nova_ezseo');
        $fieldtypeMetas = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');
        $metas          = $this->configResolver->getParameter('default_metas', 'nova_ezseo');
        $links          = $this->configResolver->getParameter('default_links', 'nova_ezseo');
        $gatracker      = $this->configResolver->getParameter('google_gatracker', 'nova_ezseo');
        $anonymizeIp    = $this->configResolver->getParameter('google_anonymizeIp', 'nova_ezseo');
        $novaeZseo      = [
            'fieldtype_metas_identifier' => $identifier,
            'fieldtype_metas'            => $fieldtypeMetas,
            'default_metas'              => $metas,
            'default_links'              => $links,
            'google_gatracker'           => '~' !== $gatracker ? $gatracker : null,
            'google_anonymizeIp'         => '~' !== $anonymizeIp ? (bool) $anonymizeIp : true,
        ];

        return ['nova_ezseo' => $novaeZseo];
    }
}
