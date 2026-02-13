<?php

namespace Novactive\Bundle\eZSEOBundle\Core\Converter;

use Ibexa\Contracts\Core\Repository\ContentTypeService;

class ContentTypesHelper
{
    public function __construct(private readonly ContentTypeService $contentTypeService)
    {
    }

    public function getContentTypesByIdentifier($identifier): array
    {
        if (str_contains((string) $identifier, ',')) {
            $contentTypeArray = explode(',', (string) $identifier);
        } else {
            $contentTypeArray[] = $identifier;
        }

        $contentTypesCollection = [];
        foreach ($contentTypeArray as $contentTypeIdentifier) {
            if (!empty($contentTypeIdentifier)) {
                $contentTypesCollection[] = $this->contentTypeService->loadContentTypeByIdentifier(
                    $contentTypeIdentifier
                );
            }
        }

        return $contentTypesCollection;
    }

    public function getContentTypesByGroup(string $identifier): array
    {
        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier($identifier);

        return $this->contentTypeService->loadContentTypes($contentTypeGroup);
    }
}
