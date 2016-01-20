<?php

namespace Novactive\Bundle\eZSEOBundle\Installer;

use Exception;
use DateTime;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;

class Field
{
    /**
     * Default `metas` field type description.
     *
     * @todo move to configuration
     * @var string
     */
    const METAS_FIELD_DESCRIPTION = 'Metas for Search Engine Optimizations';

    /**
     * Default `metas` field name.
     *
     * @todo move to configuration
     * @var string
     */
    const METAS_FIELD_NAME = 'Metas';

    /**
     * Default `metas` group name.
     *
     * @todo move to configuration
     * @var string
     */
    const METAS_FIELD_GROUP = 'novaseo';

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var string */
    private $errorMessage;

    /**
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     */
    public function __construct(
        ContentTypeService $contentTypeService
    ) {
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * Adds `metas` field to existing ContentType.
     *
     * @param string $fieldName
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return bool
     */
    public function addToContentType($fieldName, ContentType $contentType)
    {
        try {
            $contentTypeDraft = $this->contentTypeService->loadContentTypeDraft($contentType->id);
        } catch (NotFoundException $e) {
            $contentTypeDraft = $this->contentTypeService->createContentTypeDraft($contentType);
        }

        $typeUpdate = $this->contentTypeService->newContentTypeUpdateStruct();
        $typeUpdate->modificationDate = new DateTime();

        $knowLanguage = array_keys($contentType->getDescriptions());

        if (!in_array($contentType->mainLanguageCode, $knowLanguage)) {
            $knowLanguage[] = $contentType->mainLanguageCode;
        }

        $fieldCreateStruct = $this->contentTypeService->newFieldDefinitionCreateStruct(
            $fieldName,
            'novaseometas'
        );

        $fieldCreateStruct->names = array_fill_keys($knowLanguage, self::METAS_FIELD_NAME);
        $fieldCreateStruct->descriptions = array_fill_keys($knowLanguage, self::METAS_FIELD_DESCRIPTION);
        $fieldCreateStruct->fieldGroup = self::METAS_FIELD_GROUP;
        $fieldCreateStruct->position = 100;
        $fieldCreateStruct->isTranslatable = true;
        $fieldCreateStruct->isRequired = false;
        $fieldCreateStruct->isSearchable = false;
        $fieldCreateStruct->isInfoCollector = false;

        try {
            $this->contentTypeService->updateContentTypeDraft($contentTypeDraft, $typeUpdate);

            if ($contentTypeDraft->getFieldDefinition($fieldName) == null) {
                $this->contentTypeService->addFieldDefinition($contentTypeDraft, $fieldCreateStruct);
            }

            $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

            return true;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();

            return false;
        }
    }

    /**
     * Checks if `metas` field exists in specified ContentType.
     *
     * @param string $fieldName
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return bool
     */
    public function fieldExists($fieldName, ContentType $contentType)
    {
        $fieldDefinition = $contentType->getFieldDefinition($fieldName);

        if (empty($fieldDefinition)) {
            return false;
        }

        return true;
    }

    /**
     * Returns error message.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
