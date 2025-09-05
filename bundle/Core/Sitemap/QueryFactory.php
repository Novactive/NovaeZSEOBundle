<?php

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Core\Sitemap;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery as Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

class QueryFactory
{
    public function __construct(
        protected ConfigResolverInterface $configResolver,
        protected Repository $repository
    ) {
    }

    protected function getLocation(int $locationId): ?Location
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

    protected function getRootLocation(): Location
    {
        return $this->repository->getLocationService()->loadLocation(
            $this->configResolver->getParameter('content.tree_root.location_id')
        );
    }

    public function __invoke(): Query
    {
        $query = new Query();
        $criteria = $this->getCriteria();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->sortClauses = [new SortClause\DatePublished(Query::SORT_DESC)];

        return $query;
    }

    /**
     * @return Criterion[]
     */
    public function getCriteria(): array
    {
        // always here, we want visible Contents
        $criteria = [new Criterion\Visibility(Criterion\Visibility::VISIBLE)];

        // do we want to limit per Root Location, but default we don't
        $limitToRootLocation = $this->configResolver->getParameter('limit_to_rootlocation', 'nova_ezseo');
        if (true === $limitToRootLocation) {
            $criteria[] = new Criterion\Subtree($this->getRootLocation()->pathString);
        }

        // Inclusions
        $config = $this->configResolver->getParameter('sitemap_includes', 'nova_ezseo');
        $criteria = array_merge(
            $criteria,
            $this->getCriteriaForIncludeConfig(
                $config['contentTypeIdentifiers'],
                $config['locations'],
                $config['subtrees'],
                $config['object_states'],
            )
        );

        // Exclusions
        $config = $this->configResolver->getParameter('sitemap_excludes', 'nova_ezseo');
        $criteria = array_merge(
            $criteria,
            $this->getCriteriaForExcludeConfig(
                $config['contentTypeIdentifiers'],
                $config['locations'],
                $config['subtrees'],
                $config['object_states'],
            )
        );

        $criteria[] = new Criterion\LanguageCode($this->configResolver->getParameter('languages'), true);

        return $criteria;
    }

    /**
     * @return Criterion[]
     */
    protected function getCriteriaForExcludeConfig(
        array $contentTypeIdentifiers,
        array $locationIds,
        array $subtreeLocationsId,
        array $objectStates,
    ): array {
        $contentTypeService = $this->repository->getContentTypeService();
        $criteria = [];

        foreach ($contentTypeIdentifiers as $contentTypeIdentifier) {
            try {
                $contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
            } catch (NotFoundException $exception) {
                continue;
            }
            $criteria[] = new Criterion\ContentTypeIdentifier($contentTypeIdentifier);
        }

        foreach ($subtreeLocationsId as $locationId) {
            $excludedLocation = $this->getLocation($locationId);
            if (null === $excludedLocation) {
                continue;
            }
            $criteria[] = new Criterion\Subtree($excludedLocation->pathString);
        }

        foreach ($locationIds as $locationId) {
            $excludedLocation = $this->getLocation($locationId);
            if (null === $excludedLocation) {
                continue;
            }
            $criteria[] = new Criterion\LocationId($locationId);
        }

        foreach ($objectStates as $objectStateData) {
            foreach ($objectStateData as $objectStateGroupIdentifier => $objectStateIdentifiers) {
                try {
                    $service = $this->repository->getObjectStateService();
                    $group = $service->loadObjectStateGroupByIdentifier($objectStateGroupIdentifier);
                    foreach ($objectStateIdentifiers as $objectStateIdentifier) {
                        $state = $service->loadObjectStateByIdentifier($group, $objectStateIdentifier);
                        $criteria[] = new Criterion\ObjectStateIdentifier($state->identifier, $group->identifier);
                    }
                } catch (NotFoundException $notFoundException) {
                    continue;
                }
            }
        }

        return array_map(
            function ($criterion) {
                return new Criterion\LogicalNot($criterion);
            },
            $criteria
        );
    }

    /**
     * @return Criterion[]
     */
    protected function getCriteriaForIncludeConfig(
        array $contentTypeIdentifiers,
        array $locationIds,
        array $subtreeLocationsId,
        array $objectStates,
    ): array {
        $criteria = [];

        $validContentTypeIdentifiers = $this->getValidContentTypeIdentifiers($contentTypeIdentifiers);
        if (count($validContentTypeIdentifiers)) {
            $criteria[] = new Criterion\ContentTypeIdentifier($validContentTypeIdentifiers);
        }

        $subtreePaths = $this->getSubtreePathList($subtreeLocationsId);
        if (count($subtreePaths)) {
            $criteria[] = new Criterion\Subtree($subtreePaths);
        }

        $validLocationIds = [];
        foreach ($locationIds as $locationId) {
            $includedLocation = $this->getLocation($locationId);
            if (null === $includedLocation) {
                continue;
            }
            $validLocationIds[] = $locationId;
        }

        if (count($validLocationIds) > 0) {
            $criteria[] = new Criterion\LocationId($validLocationIds);
        }

        foreach ($objectStates as $objectStateData) {
            foreach ($objectStateData as $objectStateGroupIdentifier => $objectStateIdentifiers) {
                $validStateIdentifiers = [];
                try {
                    $service = $this->repository->getObjectStateService();
                    $group = $service->loadObjectStateGroupByIdentifier($objectStateGroupIdentifier);
                    foreach ($objectStateIdentifiers as $objectStateIdentifier) {
                        $state = $service->loadObjectStateByIdentifier($group, $objectStateIdentifier);
                        $validStateIdentifiers[] = $state->identifier;
                    }
                } catch (NotFoundException $notFoundException) {
                    continue;
                }
                if (count($validStateIdentifiers) > 0) {
                    $criteria[] = new Criterion\ObjectStateIdentifier(
                        $validStateIdentifiers,
                        $objectStateGroupIdentifier
                    );
                }
            }
        }

        return $criteria;
    }

    protected function getValidContentTypeIdentifiers(array $contentTypeIdentifiers): array
    {
        $validContentTypeIdentifiers = [];
        foreach ($contentTypeIdentifiers as $contentTypeIdentifier) {
            $contentType = $this->getContentType($contentTypeIdentifier);
            if ($contentType) {
                $validContentTypeIdentifiers[] = $contentType->identifier;
            }
        }

        return $validContentTypeIdentifiers;
    }

    protected function getContentType(string $contentTypeIdentifier): ?ContentType
    {
        try {
            return $this->repository->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);
        } catch (NotFoundException $exception) {
            return null;
        }
    }

    protected function getSubtreePathList(array $subtreeLocationsId): array
    {
        $subtreePaths = [];
        foreach ($subtreeLocationsId as $locationId) {
            $includedLocation = $this->getLocation($locationId);
            if (!$includedLocation) {
                continue;
            }
            $subtreePaths[] = $includedLocation->pathString;
        }

        return $subtreePaths;
    }
}
