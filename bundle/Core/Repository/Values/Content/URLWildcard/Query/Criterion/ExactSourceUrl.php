<?php

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Core\Repository\Values\Content\URLWildcard\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion\Matcher;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

class ExactSourceUrl extends Matcher
{
    public string $sourceUrl;

    public function __construct(string $sourceUrl)
    {
        if ('' === $sourceUrl) {
            throw new InvalidArgumentException('sourceUrl', 'URLWildcard source url cannot be empty.');
        }

        $this->sourceUrl = $sourceUrl;
    }
}
