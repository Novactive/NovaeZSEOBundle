<?php

namespace Novactive\Bundle\eZSEOBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Novactive\Bundle\eZSEOBundle\Installer\Field;
use Novactive\Bundle\eZSEOBundle\Converter\FieldConverter;
use Novactive\Bundle\eZSEOBundle\Converter\ContentTypesHelper;

/**
 * Converts xrow field type data to Nova eZ SEO Bundle format (requires legacy bridge).
 *
 * @author Rafał Toborek <rafal.toborek@ez.no>
 */
class ConvertXrow2NovaCommand extends ContainerAwareCommand
{
    /** @var \eZ\Publish\API\Repository\Repository */
    private $repository;

    /** @var \eZ\Publish\API\Repository\UserService */
    private $userService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \Novactive\Bundle\eZSEOBundle\Installer\Field */
    private $fieldInstaller;

    /** @var \Novactive\Bundle\eZSEOBundle\Converter\ContentTypesHelper */
    private $contentTypesHelper;

    /** @var \Novactive\Bundle\eZSEOBundle\Converter\FieldConverter */
    private $xrow2nova;

    /** @var int */
    private $adminUserId;

    /** @var string */
    private $metasFieldNameIdentifier;

    /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentType[] */
    private $contentTypes;

    /**
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \Novactive\Bundle\eZSEOBundle\Converter\FieldConverter $xrow2nova
     * @param \Novactive\Bundle\eZSEOBundle\Installer\Field $fieldInstaller
     * @param \Novactive\Bundle\eZSEOBundle\Converter\ContentTypesHelper $contentTypesHelper
     * @param int $adminUserId
     */
    public function __construct(
        Repository $repository,
        UserService $userService,
        ContentTypeService $contentTypeService,
        FieldConverter $xrow2nova,
        Field $fieldInstaller,
        ContentTypesHelper $contentTypesHelper,
        $adminUserId
    ) {
        $this->repository = $repository;
        $this->userService = $userService;
        $this->contentTypeService = $contentTypeService;
        $this->fieldInstaller = $fieldInstaller;
        $this->xrow2nova = $xrow2nova;
        $this->contentTypesHelper = $contentTypesHelper;
        $this->adminUserId = $adminUserId;

        parent::__construct();
    }

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

    /**
     * @param string $value
     */
    public function setMetasFieldNameIdentifier($value)
    {
        $this->metasFieldNameIdentifier = $value;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->repository->setCurrentUser($this->userService->loadUser($this->adminUserId));
    }

    protected function configure()
    {
        $this->setName('novae_zseo:convertxrow')
            ->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'a content type identifier')
            ->addOption('identifiers', null, InputOption::VALUE_REQUIRED, 'some content types identifier, separated by a comma')
            ->addOption('group_identifier', null, InputOption::VALUE_REQUIRED, 'a content type group identifier')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'limit of objects processed in each loop', 25)
            ->addOption('xrow_name', null, InputOption::VALUE_REQUIRED, 'name of the existing xrow field name', 'metadata')
            ->setDescription('Converts existing xrow field type data to Nova eZ SEO metas.')
            ->setHelp(
                "The <info>%command.name%</info> command converts existing xrow field type data to Nova eZ SEO bundle format.\r\n".
                "You can select the ContentType via the <info>identifier</info>, <info>identifiers</info> or <info>group_identifier</info> option."
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $contentTypesCollection = array();

        $groupIdentifier = $input->getOption('group_identifier');
        if (!empty($groupIdentifier)) {
            $contentTypesCollection = $this->contentTypesHelper->getContentTypesByGroup($groupIdentifier);
        }

        $identifiers = $input->getOption('identifiers');
        if (!empty($identifiers)) {
            $contentTypesCollection = $this->contentTypesHelper->getContentTypesByIdentifier($identifiers);
        }

        $identifier = $input->getOption('identifier');
        if (!empty($identifier)) {
            $contentTypesCollection = $this->contentTypesHelper->getContentTypesByIdentifier($identifier);
        }

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

        $xrowFieldName = $input->getOption('xrow_name');
        $contentTypeIdentifiers = array();

        // add new field type to existing ContentTypes
        foreach ($this->contentTypes as $contentType) {

            // xrow field is missing
            if (!$this->fieldInstaller->fieldExists($xrowFieldName, $contentType)) {
                $output->writeln(sprintf('xrow <info>%s</info> field is missing in <info>%s</info> ContentType',
                    $xrowFieldName,
                    $contentType->getName($contentType->mainLanguageCode))
                );

                continue;
            }

            $contentTypeIdentifiers[] = $contentType->identifier;

            if (!$this->fieldInstaller->fieldExists($this->metasFieldNameIdentifier, $contentType)) {
                if (!$this->fieldInstaller->addToContentType($this->metasFieldNameIdentifier, $contentType)) {
                    $output->writeln(sprintf(
                        'There were errors when adding new field to <info>%s</info> ContentType: <error>%s</error>',
                        $contentType->getName($contentType->mainLanguageCode),
                        $this->fieldInstaller->getErrorMessage()
                    ));

                    return;
                }

                $output->writeln(sprintf('New field was added to <info>%s</info> ContentType', $contentType->getName($contentType->mainLanguageCode)));
            }
        }

        if (empty($contentTypeIdentifiers)) {
            $output->writeln(sprintf(
                'Conversion canceled, xrow <info>%s</info> field is missing in selected ContentTypes',
                $xrowFieldName
            ));

            return;
        }

        $this->xrow2nova->setOutput($output);
        $this->xrow2nova->setLegacyKernel($this->getLegacyKernel());

        $this->xrow2nova->convert(
            $contentTypeIdentifiers,
            $xrowFieldName,
            (int)$input->getOption('limit')
        );

        $output->writeln('Operation completed.');
    }
}
