<?php

namespace Novactive\Bundle\eZSEOBundle\Converter;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use eZ\Bundle\EzPublishLegacyBundle\Controller;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Novactive\Bundle\eZSEOBundle\Core\Meta;

/**
 * Converts xrow field type data to Nova eZ SEO Bundle format (requires legacy bridge).
 *
 * @author Rafał Toborek <rafal.toborek@ez.no>
 */
class Xrow2NovaConverter extends Controller implements FieldConverter
{
    /** @var \eZ\Publish\API\Repository\SearchService */
    private $searchService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var string */
    private $xrowFieldNameIdentifier;

    /** @var string */
    private $metasFieldNameIdentifier;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    private $output;

    /** @var string */
    private $errorMessage;

    /** @var \eZ\Publish\Core\MVC\Legacy\Kernel */
    private $legacyKernel;

    /**
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     */
    public function __construct(
        SearchService $searchService,
        ContentService $contentService
    ) {
        $this->searchService = $searchService;
        $this->contentService = $contentService;
    }

    /**
     * @param \eZ\Publish\Core\MVC\Legacy\Kernel $legacyKernel
     */
    public function setLegacyKernel($legacyKernel)
    {
        $this->legacyKernel = $legacyKernel;
    }

    /**
     * @param string $value
     */
    public function setMetasFieldNameIdentifier($value)
    {
        $this->metasFieldNameIdentifier = $value;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $value
     */
    public function setOutput(OutputInterface $value)
    {
        $this->output = $value;
    }

    /**
     * @param string $text
     */
    private function writeOutput($text)
    {
        if (!empty($this->output)) {
            $this->output->writeln($text);
        }
    }

    /**
     * Converts content objects in specified portions.
     *
     * @param array $contentTypeIdentifiers
     * @param string $fieldName
     * @param int $limit
     *
     * @return bool
     */
    public function convert($contentTypeIdentifiers = array(), $fieldName, $limit)
    {
        $this->xrowFieldNameIdentifier = $fieldName;
        $offset = 0;

        do {
            // fetch portion of related content objects
            $contentArray = $this->getAllByContentTypes($contentTypeIdentifiers, $offset, $limit);
            $offset = $offset + $limit;

            $this->writeOutput(sprintf("\r\nStatus:\t<info>%d</info> content objects found", count($contentArray)));

            // retrieve xrow field data using legacy extension
            $xrowValues = $this->getXrowValues($contentArray);
            $this->writeOutput(sprintf("\t<info>%d</info> xrow fields retrieved\r\n", count($xrowValues)));

            $conversionCounter = 0;
            $conversionTotal = count($xrowValues);

            if ($conversionTotal == 0) {
                $this->writeOutput('Nothing to convert.');

                return false;
            }

            $this->writeOutput('Conversion started: ');

            foreach ($xrowValues as $contentId => $value) {
                $this->writeOutput(
                    sprintf(
                        "\t%d/%d: <info>%s</info> (content ID: <info>%d</info>)",
                        ++$conversionCounter,
                        $conversionTotal,
                        $contentArray[$contentId]->contentInfo->name,
                        $contentId
                    )
                );

                // convert xrow to nova structure
                $metasFieldData = $this->convertXrowToNova($value);

                // update content with the new field structure
                foreach ($metasFieldData as $language => $metaData) {
                    if (!empty($value)) {
                        $this->writeOutput(sprintf("\t\tupdating: <info>%s</info>", $language));

                        if (!$this->updateContent($contentId, $metaData, $language)) {
                            $this->writeOutput(sprintf('<error>%s</error>', $this->errorMessage));
                        }
                    }
                }
            }
        } while (!empty($contentArray));

        return true;
    }

    /**
     * Fetches all content objects based on $contentTypeIdentifiers.
     *
     * @param string[] $contentTypeIdentifiers
     * @param int $offset
     * @param int $limit
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    private function getAllByContentTypes($contentTypeIdentifiers, $offset, $limit)
    {
        $query = new Query();

        $query->query = new Criterion\ContentTypeIdentifier($contentTypeIdentifiers);
        $query->performCount = false;
        $query->limit = $limit;
        $query->offset = $offset;

        $searchResults = $this->searchService->findContent($query);

        // prepare search results
        $contentArray = array();
        foreach ($searchResults->searchHits as $searchHit) {
            $contentArray[$searchHit->valueObject->id] = $searchHit->valueObject;
        }

        return $contentArray;
    }

    /**
     * Retrieves xrow attribute values using legacy kernel.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content[] $contentArray
     *
     * @return array
     */
    private function getXrowValues($contentArray)
    {
        //$xrowValues = $this->getLegacyKernel()->runCallback(function () use ($contentArray) {
            $xrowValues = $this->legacyKernel->runCallback(function () use ($contentArray) {
            $values = array();

            foreach ($contentArray as $content) {
                foreach ($content->versionInfo->languageCodes as $languageCode) {
                    $object = \eZContentObject::fetch($content->id);
                    $dataMap = $object->fetchDataMap(false, $languageCode);

                    if (!empty($dataMap[$this->xrowFieldNameIdentifier])) {
                        $values[$content->id][$languageCode] = $dataMap[$this->xrowFieldNameIdentifier]->DataText;
                    }
                }
            }

            return $values;
        });

        return $xrowValues;
    }

    /**
     * Converts existing xrow structure to `metas` format.
     *
     * @param array $data
     *
     * @return array
     */
    private function convertXrowToNova($data)
    {
        $metasFieldData = array();

        foreach ($data as $language => $metaData) {
            $xmlParser = xml_parser_create();
            xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);

            // prepare new field structure
            if (xml_parse_into_struct($xmlParser, $data[$language], $valueArray) > 0) {
                foreach ($valueArray as $element) {
                    // some of the xrow field data kes are not needed, just skip them
                    if (!in_array($element['tag'], array('MetaData', 'change', 'priority', 'sitemap_use'))) {
                        $metasFieldAttribute = new Meta();

                        $metasFieldAttribute->setName($element['tag']);
                        // max 256 chars due to database column limitation
                        $metasFieldAttribute->setContent(empty($element['value']) ? '' : substr($element['value'], 0, 255));

                        $metasFieldData[$language][$element['tag']] = $metasFieldAttribute;
                    }
                }
            }

            xml_parser_free($xmlParser);
        }

        return $metasFieldData;
    }

    /**
     * Updates and publish content by adding `metas` field.
     *
     * @param mixed $contentId
     * @param \Novactive\Bundle\eZSEOBundle\Core\Meta $metaData
     * @param string $language
     *
     * @return bool
     */
    private function updateContent($contentId, $metaData, $language)
    {
        $result = true;

        $translatedContent = $this->contentService->loadContent($contentId, array($language));
        $publishedDate = $translatedContent->contentInfo->publishedDate;

        $contentDraft = $this->contentService->createContentDraft($translatedContent->contentInfo);
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField($this->metasFieldNameIdentifier, $metaData, $language);

        $updatedContentDraft = null;

        try {
            $updatedContentDraft = $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            $result = false;
        }

        if ($updatedContentDraft) {
            $updatedContent = $this->contentService->publishVersion($contentDraft->versionInfo);

            // sets original publication date
            $metadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();
            $metadataUpdateStruct->publishedDate = $publishedDate;
            $this->contentService->updateContentMetadata($updatedContent->contentInfo, $metadataUpdateStruct);
        } else {
            $this->contentService->deleteVersion($contentDraft->versionInfo);
        }

        return $result;
    }
}
