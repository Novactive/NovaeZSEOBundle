<?php

namespace Novactive\Bundle\eZSEOBundle\Converter;

use eZ\Publish\API\Repository\ContentTypeService;

/**
 * ContentTypes helper.
 */
class ContentTypesHelper
{
    /** @var \eZ\Publish\API\Repository\ContentTypeService|ContentTypeService */
    private $contentTypeService;

    /**
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     */
    public function __construct(ContentTypeService $contentTypeService)
    {
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * Returns ContentTypes array by ContentType identifier.
     *
     * @param string $identifier
     *
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType[]
     */
    public function getContentTypesByIdentifier($identifier)
    {
        if (strstr($identifier, ',')) {
            $contentTypeArray = explode(',', $identifier);
        } else {
            $contentTypeArray[] = $identifier;
        }

        $contentTypesCollection = array();
        foreach ($contentTypeArray as $contentTypeIdentifier) {
            if (!empty($contentTypeIdentifier)) {
                $contentTypesCollection[] = $this->contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
            }
        }

        return $contentTypesCollection;
    }

    /**
     * Returns ContentTypes array by ContentType group.
     *
     * @param string $identifier
     *
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType[]
     */
    public function getContentTypesByGroup($identifier)
    {
        $contentTypesCollection = array();

        if ($contentTypeGroupIdentifier = $identifier) {
            $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier($contentTypeGroupIdentifier);
            $contentTypesCollection = $this->contentTypeService->loadContentTypes($contentTypeGroup);
        }

        return $contentTypesCollection;
    }
}
