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

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface as LocaleConverter;
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
     * The Ibexa Platform object name pattern service (extended).
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
     * The Ibexa Platform API.
     *
     * @var Repository
     */
    protected $ibexaRepository;

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

    public function __construct(
        Repository $repository,
        MetaNameSchema $nameSchema,
        ConfigResolverInterface $configResolver,
        LocaleConverter $localeConverter
    ) {
        $this->metaNameSchema = $nameSchema;
        $this->ibexaRepository = $repository;
        $this->configResolver = $configResolver;
        $this->localeConverter = $localeConverter;
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

    public function getPosixLocale(string $ibexaLocale): ?string
    {
        return $this->localeConverter->convertToPOSIX($ibexaLocale);
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
    // @param $content: use type Content rather than ContentInfo, the last one is @deprecated
    public function computeMetas(Field $field, $content): string
    {
        $fallback = false;
        $languages = $this->configResolver->getParameter('languages');

        if ($content instanceof ContentInfo) {
            try {
                $content = $this->ibexaRepository->getContentService()->loadContentByContentInfo($content, $languages);
            } catch (NotFoundException | UnauthorizedException $e) {
                return '';
            }
        } elseif (!($content instanceof Content)) {
            throw new InvalidArgumentType('$content', 'Content of ContentType');
        }

        $contentMetas = $this->innerComputeMetas($content, $field, $fallback);

        if ($fallback && !$this->customFallBackService) {
            $rootContent = $this->ibexaRepository->getLocationService()->loadLocation(
                $this->configResolver->getParameter('content.tree_root.location_id')
            )->getContent();

            // We need to load the good field too
            $metasIdentifier = $this->configResolver->getParameter('fieldtype_metas_identifier', 'nova_ezseo');
            $rootMetas = $this->innerComputeMetas($rootContent, $metasIdentifier, $fallback);

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
        $field,
        &$needFallback = false
    ): array {
        if ($field instanceof Field) {
            $metasFieldValue = $field->value;
            $fieldDefIdentifier = $field->fieldDefIdentifier;
        } else {
            $metasFieldValue = $content->getFieldValue($field);
            $fieldDefIdentifier = $field;
        }

        if ($metasFieldValue instanceof MetasFieldValue) {
            $metasConfig = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');
            // as the configuration is the last fallback we need to loop on it.
            foreach (array_keys($metasConfig) as $metaName) {
                if ($metasFieldValue->nameExists($metaName)) {
                    $meta = $metasFieldValue->metas[$metaName];
                } else {
                    $meta = new Meta($metaName);
                    $metasFieldValue->metas[$metaName] = $meta;
                }

                /** @var Meta $meta */
                if ($meta->isEmpty()) {
                    $meta->setContent($metasConfig[$meta->getName()]['default_pattern']);
                    $fieldDefinition = $content->getContentType()->getFieldDefinition($fieldDefIdentifier);
                    $configuration = $fieldDefinition->getFieldSettings()['configuration'];
                    // but if we need something is the configuration we take it
                    if (isset($configuration[$meta->getName()]) && !empty($configuration[$meta->getName()])) {
                        $meta->setContent($configuration[$meta->getName()]);
                    }
                }
                if (!$this->metaNameSchema->resolveMeta($meta, $content)) {
                    $needFallback = true;
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
        $identifier = $this->configResolver->getParameter('fieldtype_metas_identifier', 'nova_ezseo');
        $fieldtypeMetas = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');
        $metas = $this->configResolver->getParameter('default_metas', 'nova_ezseo');
        $links = $this->configResolver->getParameter('default_links', 'nova_ezseo');
        $gatracker = $this->configResolver->getParameter('google_gatracker', 'nova_ezseo');
        $anonymizeIp = $this->configResolver->getParameter('google_anonymizeIp', 'nova_ezseo');
        $novaeZseo = [
            'fieldtype_metas_identifier' => $identifier,
            'fieldtype_metas' => $fieldtypeMetas,
            'default_metas' => $metas,
            'default_links' => $links,
            'google_gatracker' => '~' !== $gatracker ? $gatracker : null,
            'google_anonymizeIp' => '~' !== $anonymizeIp ? (bool) $anonymizeIp : true,
        ];

        return ['nova_ezseo' => $novaeZseo];
    }
}
