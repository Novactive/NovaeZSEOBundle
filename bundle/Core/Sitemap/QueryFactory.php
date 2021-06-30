<?php

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Core\Sitemap;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery as Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

final class QueryFactory
{
    /**
     * @var ConfigResolver
     */
    private $configResolver;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(ConfigResolverInterface $configResolver, Repository $repository)
    {
        $this->configResolver = $configResolver;
        $this->repository = $repository;
    }

    private function getLocation(int $locationId): ?Location
    {
        return $this->repository->sudo(
            function (Repository $repository) use ($locationId) {
                $locationService = $repository->getLocationService();
                try {
                    return $locationService->loadLocation($locationId);
                } catch (NotFoundException $e) {
                    return null;
                }
            }
        );
    }

    private function getRootLocation(): Location
    {
        return $this->repository->getLocationService()->loadLocation(
            $this->configResolver->getParameter('content.tree_root.location_id')
        );
    }

    public function __invoke(): Query
    {
        $query = new Query();

        // always here, we want visible Contents
        $criterions = [new Criterion\Visibility(Criterion\Visibility::VISIBLE)];

        // do we want to limit per Root Location, but default we don't
        $limitToRootLocation = $this->configResolver->getParameter('limit_to_rootlocation', 'nova_ezseo');
        if (true === $limitToRootLocation) {
            $criterions[] = new Criterion\Subtree($this->getRootLocation()->pathString);
        }

        // Inclusions
        $config = $this->configResolver->getParameter('sitemap_includes', 'nova_ezseo');
        $criterions = array_merge(
            $criterions,
            $this->getCriterionsForConfig(
                $config['contentTypeIdentifiers'],
                $config['locations'],
                $config['subtrees'],
                false
            )
        );

        // Exclusions
        $config = $this->configResolver->getParameter('sitemap_excludes', 'nova_ezseo');
        $criterions = array_merge(
            $criterions,
            $this->getCriterionsForConfig(
                $config['contentTypeIdentifiers'],
                $config['locations'],
                $config['subtrees'],
                true
            )
        );

        $query->query = new Criterion\LogicalAnd($criterions);
        $query->sortClauses = [new SortClause\DatePublished(Query::SORT_DESC)];

        return $query;
    }

    private function getCriterionsForConfig(
        array $contentTypeIdentifiers,
        array $locationIds,
        array $subtreeLocationsId,
        bool $isLogicalNot = false
    ) {
        $contentTypeService = $this->repository->getContentTypeService();
        $criterions = [];

        foreach ($contentTypeIdentifiers as $contentTypeIdentifier) {
            try {
                $contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
            } catch (NotFoundException $exception) {
                continue;
            }
            $criterions[] = new Criterion\ContentTypeIdentifier($contentTypeIdentifier);
        }

        foreach ($subtreeLocationsId as $locationId) {
            $excludedLocation = $this->getLocation($locationId);
            if (null === $excludedLocation) {
                continue;
            }
            $criterions[] = new Criterion\Subtree($excludedLocation->pathString);
        }

        foreach ($locationIds as $locationId) {
            $excludedLocation = $this->getLocation($locationId);
            if (null === $excludedLocation) {
                continue;
            }
            $criterions[] = new Criterion\LocationId($locationId);
        }

        if ($isLogicalNot) {
            return array_map(
                function ($criterion) {
                    return new Criterion\LogicalNot($criterion);
                },
                $criterions
            );
        }

        return $criterions;
    }
}
