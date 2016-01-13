<?php

namespace Novactive\Bundle\eZSEOBundle\Command;

use Exception;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Novactive\Bundle\eZSEOBundle\Core;

/**
 * Converts xrow field type data to Nova eZ SEO Bundle format (requires legacy bridge).
 *
 * @author Rafał Toborek <rafal.toborek@ez.no>
 */
class ConvertXRow2NovaCommand extends ContainerAwareCommand
{
    /**
     * Default ID for Administrator user.
     *
     * @var int
     */
    const ADMIN_USER_ID = 14;

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

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var |eZ\Publish\API\Repository\Repository */
    private $repository;

    /** @var \eZ\Publish\API\Repository\UserService */
    private $userService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \eZ\Publish\API\Repository\SearchService */
    private $searchService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /**
     * Returns legacy kernel object.
     *
     * We cannot use legacy kernel injected by Symfony because of the inactive scope exception:
     * `You cannot create a service ("request") of an inactive scope ("request").`
     *
     * @return \eZ\Publish\Core\MVC\Legacy\Kernel
     */
    private function getLegacyKernel()
    {
        $legacyKernel = $this->getContainer()->get('ezpublish_legacy.kernel');

        return $legacyKernel();
    }

    protected function configure()
    {
        $this->setName('novae_zseo:convertxrow')
            ->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'a content type identifier')
            ->addOption('identifiers', null, InputOption::VALUE_REQUIRED, 'some content types identifier, separated by a comma')
            ->addOption('group_identifier', null, InputOption::VALUE_REQUIRED, 'a content type group identifier')
            ->setDescription('Converts existing xrow field type data to Nova eZ SEO metas.')
            ->setHelp(
                "The <info>%command.name%</info> command converts existing xrow field type data to Nova eZ SEO bundle format.\r\n".
                "You can select the ContentType via the <info>identifier</info>, <info>identifiers</info> or <info>group_identifier</info> option."
            );
    }

    /**
     * Gets ContentTypes depending on the input arguments.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return ContentType[]
     */
    protected function getContentTypes(InputInterface $input)
    {
        $contentTypesCollection = [];

        if ($contentTypeGroupIdentifier = $input->getOption('group_identifier')) {
            $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier($contentTypeGroupIdentifier);
            $contentTypesCollection = $this->contentTypeService->loadContentTypes($contentTypeGroup);
        }

        if ($contentTypeIdentifiers = explode(',', $input->getOption('identifiers'))) {
            foreach ($contentTypeIdentifiers as $identifier) {
                if (!empty($identifier)) {
                    $contentTypesCollection[] = $this->contentTypeService->loadContentTypeByIdentifier($identifier);
                }
            }
        }

        if ($contentTypeIdentifier = $input->getOption('identifier')) {
            $contentTypesCollection[] = $this->contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
        }

        return $contentTypesCollection;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $contentTypesCollection = $this->getContentTypes($input);

        $output->writeln('<info>Selected ContentTypes:</info>');

        foreach ($contentTypesCollection as $contentType) {
            $output->writeln(sprintf("\t- %s", $contentType->getName($contentType->mainLanguageCode)));
        }

        $question = new ConfirmationQuestion(
            "\r\n<question>Are you sure you want to convert all xrow field types included in these ContentTypes?</question>[yes]",
            true
        );

        if (!$this->getHelper('question')->ask($input, $output, $question)) {
            return;
        }

        $this->contentTypes = $contentTypesCollection;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (empty($this->contentTypes)) {
            $output->writeln('No ContentTypes were selected.');

            return;
        }

        $contentTypeIdentifiers = [];

        // add new field type to existing ContentTypes
        foreach ($this->contentTypes as $contentType) {
            $contentTypeIdentifiers[] = $contentType->identifier;

            $metasFieldDefinition = $contentType->getFieldDefinition($this->configResolver->getParameter('fieldtype_metas_identifier', 'novae_zseo'));

            // add `metas` field definition to selected ContentType only if it not exists
            if (empty($metasFieldDefinition)) {
                $contentTypeDraft = $this->contentTypeService->createContentTypeDraft($contentType);

                $typeUpdate = $this->contentTypeService->newContentTypeUpdateStruct();
                $typeUpdate->modificationDate = new DateTime();

                $knowLanguage = array_keys($contentType->getDescriptions());

                if (!in_array($contentType->mainLanguageCode, $knowLanguage)) {
                    $knowLanguage[] = $contentType->mainLanguageCode;
                }

                $fieldCreateStruct = $this->contentTypeService->newFieldDefinitionCreateStruct(
                    $this->configResolver->getParameter('fieldtype_metas_identifier', 'novae_zseo'),
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

                $this->contentTypeService->updateContentTypeDraft($contentTypeDraft, $typeUpdate);
                $this->contentTypeService->addFieldDefinition($contentTypeDraft, $fieldCreateStruct);
                $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

                $output->writeln(sprintf('New field was added to <info>%s</info> ContentType', $contentType->getName($contentType->mainLanguageCode)));
            }
        }

        // find and convert all related content objects
        $query = new Query();

        $query->query = new Criterion\ContentTypeIdentifier($contentTypeIdentifiers);
        $query->performCount = false;

        $searchResults = $this->searchService->findContent($query);

        // prepare search results
        $contentArray = array();
        foreach ($searchResults->searchHits as $searchHit) {
            $contentArray[$searchHit->valueObject->id] = $searchHit->valueObject;
        }

        $output->writeln(sprintf("\r\nStatus:\t<info>%d</info> content objects found", count($contentArray)));

        // retrieve xrow field data using legacy extension
        $xrowValues = $this->getLegacyKernel()->runCallback(function () use ($contentArray) {
            $values = array();

            foreach ($contentArray as $content) {
                foreach ($content->versionInfo->languageCodes as $languageCode) {
                    $object = \eZContentObject::fetch($content->id, $languageCode);
                    $attr = $object->attribute('data_map');

                    if (!empty($attr['metadata'])) {
                        $values[$content->id][$languageCode] = $attr['metadata']->DataText;
                    }
                }
            }

            return $values;
        });
        $output->writeln(sprintf("\t<info>%d</info> xrow fields retrieved\r\n", count($xrowValues)));

        // add new draft with converted xrow data for every content object
        $conversionCounter = 0;
        $conversionTotal = count($xrowValues);

        $output->writeln($conversionTotal > 0 ? 'Conversion started:' : 'Nothing to convert.');

        foreach ($xrowValues as $contentId => $value) {
            $output->writeln(
                sprintf(
                    "\t%d/%d: <info>%s</info> (content ID: <info>%d</info>)",
                    ++$conversionCounter,
                    $conversionTotal,
                    $contentArray[$contentId]->contentInfo->name,
                    $contentId
                )
            );

            $metasFieldData = array();
            foreach ($value as $language => $metaData) {
                $xmlParser = xml_parser_create();
                xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);

                // prepare new field structure
                if (xml_parse_into_struct($xmlParser, $value[$language], $valueArray) > 0) {
                    foreach ($valueArray as $element) {
                        // some of the xrow field data keys are not needed, just skip them
                        if (!in_array($element['tag'], array('MetaData', 'change', 'priority', 'sitemap_use'))) {
                            $metasFieldAttribute = new \Novactive\Bundle\eZSEOBundle\Core\Meta();

                            $metasFieldAttribute->setName($element['tag']);
                            // max 256 chars due to database column limitation
                            $metasFieldAttribute->setContent(empty($element['value']) ? '' : substr($element['value'], 0, 255));

                            $metasFieldData[$language][$element['tag']] = $metasFieldAttribute;
                        }
                    }
                }

                xml_parser_free($xmlParser);
            }

            // publish data with the new field structure
            foreach ($metasFieldData as $language => $metaData) {
                if (!empty($value)) {
                    $output->writeln(sprintf("\t\tupdating: <info>%s</info>", $language));
                    try {
                        $translatedContent = $this->contentService->loadContent($contentId, array($language));

                        $contentDraft = $this->contentService->createContentDraft($translatedContent->contentInfo);
                        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
                        $contentUpdateStruct->setField($this->configResolver->getParameter('fieldtype_metas_identifier', 'novae_zseo'), $metaData, $language);
                        $contentDraft = $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);

                        $this->contentService->publishVersion($contentDraft->versionInfo);
                    } catch (Exception $e) {
                        $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                    }
                }
            }
        }

        $output->writeln('Operation completed.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->repository->setCurrentUser($this->userService->loadUser(self::ADMIN_USER_ID));
    }

    /**
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        Repository $repository,
        UserService $userService,
        ContentTypeService $contentTypeService,
        SearchService $searchService,
        ContentService $contentService
    ) {
        $this->configResolver = $configResolver;
        $this->repository = $repository;
        $this->userService = $userService;
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;
        $this->contentService = $contentService;

        parent::__construct();
    }
}
