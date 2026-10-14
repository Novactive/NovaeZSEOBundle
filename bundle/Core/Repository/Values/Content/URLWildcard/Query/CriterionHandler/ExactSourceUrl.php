<?php

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Core\Repository\Values\Content\URLWildcard\Query\CriterionHandler;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion as BaseCriterion;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Query\CriteriaConverter;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Query\CriterionHandler;
use Novactive\Bundle\eZSEOBundle\Core\Repository\Values\Content\URLWildcard\Query\Criterion;

class ExactSourceUrl implements CriterionHandler
{
    public function accept(BaseCriterion $criterion): bool
    {
        return $criterion instanceof Criterion\ExactSourceUrl;
    }

    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        BaseCriterion $criterion
    ) {
        /* @var Criterion\ExactSourceUrl $criterion */
        return $queryBuilder->expr()->eq(
            'source_url',
            $queryBuilder->createNamedParameter(
                $criterion->sourceUrl,
                ParameterType::STRING,
                ':source_url_'.rand(0, 9999)
            )
        );
    }
}
