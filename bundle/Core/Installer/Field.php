<?php

namespace Novactive\Bundle\eZSEOBundle\Core\Installer;

use DateTime;
use Exception;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

class Field
{
    /**
     * @var ContentTypeService
     */
    private $contentTypeService;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @var string
     */
    private $metaFieldName;

    /**
     * @var string
     */
    private $metaFieldDescription;

    /**
     * @var string
     */
    private $metaFieldGroup;

    public function __construct(
        ContentTypeService $contentTypeService,
        string $metaFieldName,
        string $metaFieldDescription,
        string $metaFieldGroup
    ) {
        $this->contentTypeService = $contentTypeService;
        $this->metaFieldName = $metaFieldName;
        $this->metaFieldDescription = $metaFieldDescription;
        $this->metaFieldGroup = $metaFieldGroup;
    }

    public function addToContentType(string $fieldName, ContentType $contentType): bool
    {
        try {
            $contentTypeDraft = $this->contentTypeService->loadContentTypeDraft($contentType->id);
        } catch (NotFoundException $e) {
            $contentTypeDraft = $this->contentTypeService->createContentTypeDraft($contentType);
        }

        $typeUpdate = $this->contentTypeService->newContentTypeUpdateStruct();
        $typeUpdate->modificationDate = new DateTime();

        $knowLanguage = array_keys($contentType->getDescriptions());

        if (!\in_array($contentType->mainLanguageCode, $knowLanguage)) {
            $knowLanguage[] = $contentType->mainLanguageCode;
        }

        $fieldCreateStruct = $this->contentTypeService->newFieldDefinitionCreateStruct(
            $fieldName,
            'novaseometas'
        );

        $fieldCreateStruct->names = array_fill_keys($knowLanguage, $this->metaFieldName);
        $fieldCreateStruct->descriptions = array_fill_keys($knowLanguage, $this->metaFieldDescription);
        $fieldCreateStruct->fieldGroup = $this->metaFieldGroup;
        $fieldCreateStruct->position = 100;
        $fieldCreateStruct->isTranslatable = true;
        $fieldCreateStruct->isRequired = false;
        $fieldCreateStruct->isSearchable = false;
        $fieldCreateStruct->isInfoCollector = false;

        try {
            $this->contentTypeService->updateContentTypeDraft($contentTypeDraft, $typeUpdate);

            if (null == $contentTypeDraft->getFieldDefinition($fieldName)) {
                $this->contentTypeService->addFieldDefinition($contentTypeDraft, $fieldCreateStruct);
            }

            $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

            return true;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();

            return false;
        }
    }

    public function fieldExists(string $fieldName, ContentType $contentType): bool
    {
        $fieldDefinition = $contentType->getFieldDefinition($fieldName);

        return null !== $fieldDefinition;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
