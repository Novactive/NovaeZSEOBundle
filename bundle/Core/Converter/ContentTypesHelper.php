<?php

namespace Novactive\Bundle\eZSEOBundle\Core\Converter;

use eZ\Publish\API\Repository\ContentTypeService;

class ContentTypesHelper
{
    /**
     * @var ContentTypeService
     */
    private $contentTypeService;

    public function __construct(ContentTypeService $contentTypeService)
    {
        $this->contentTypeService = $contentTypeService;
    }

    public function getContentTypesByIdentifier($identifier): array
    {
        if (false !== strpos($identifier, ',')) {
            $contentTypeArray = explode(',', $identifier);
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
        $contentTypesCollection = [];

        if ($contentTypeGroupIdentifier = $identifier) {
            $contentTypeGroup       = $this->contentTypeService->loadContentTypeGroupByIdentifier(
                $contentTypeGroupIdentifier
            );
            $contentTypesCollection = $this->contentTypeService->loadContentTypes($contentTypeGroup);
        }

        return $contentTypesCollection;
    }
}
