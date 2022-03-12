<?php

/**
 * NovaeZSEOBundle AddNovaSEOMetasFieldTypeCommand.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Command;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Novactive\Bundle\eZSEOBundle\Core\Converter\ContentTypesHelper;
use Novactive\Bundle\eZSEOBundle\Core\Installer\Field as FieldInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class AddNovaSEOMetasFieldTypeCommand extends Command
{
    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var FieldInstaller
     */
    private $fieldInstaller;

    /**
     * @var ContentTypesHelper
     */
    private $contentTypesHelper;

    /**
     * @var int
     */
    private $adminUserId;

    public function __construct(
        ConfigResolverInterface $configResolver,
        Repository $repository,
        UserService $userService,
        FieldInstaller $fieldInstaller,
        ContentTypesHelper $contentTypesHelper,
        int $adminUserId
    ) {
        $this->configResolver = $configResolver;
        $this->repository = $repository;
        $this->userService = $userService;
        $this->fieldInstaller = $fieldInstaller;
        $this->contentTypesHelper = $contentTypesHelper;
        $this->adminUserId = $adminUserId;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('nova_ezseo:addnovaseometasfieldtype')
            ->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'a content type identifier')
            ->addOption(
                'identifiers',
                null,
                InputOption::VALUE_REQUIRED,
                'some content types identifier, separated by a comma'
            )
            ->addOption('group_identifier', null, InputOption::VALUE_REQUIRED, 'a content type group identifier')
            ->setDescription(
                'Add the novaseometas FieldType to Content Types'
            )->setHelp(
                <<<EOT
The command <info>%command.name%</info> add the FieldType 'novaseometas'.
You can select the Content Type via the <info>identifier</info>, <info>identifiers</info>,
<info>group_identifier</info> option.
    - Identifier will be: <comment>%nova_ezseo.default.fieldtype_metas_identifier%</comment>
    - Name will be: <comment>Metas</comment>
    - Category will be: <comment>SEO</comment>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $contentTypes = [];

        $groupIdentifier = $input->getOption('group_identifier');
        if (!empty($groupIdentifier)) {
            $contentTypes = $this->contentTypesHelper->getContentTypesByGroup($groupIdentifier);
        }

        $identifiers = $input->getOption('identifiers');
        if (!empty($identifiers)) {
            $contentTypes = $this->contentTypesHelper->getContentTypesByIdentifier($identifiers);
        }

        $identifier = $input->getOption('identifier');
        if (!empty($identifier)) {
            $contentTypes = $this->contentTypesHelper->getContentTypesByIdentifier($identifier);
        }

        $output->writeln('<info>Selected Content Type:</info>');
        foreach ($contentTypes as $contentType) {
            /* @var ContentType $contentType */
            $output->writeln("\t- {$contentType->getName($contentType->mainLanguageCode)}");
        }
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "\n<question>Are you sure you want to add novaseometas all these Content Type?</question>[yes]",
            true
        );

        if (!$helper->ask($input, $output, $question)) {
            $io->success('Nothing to do.');

            return 0;
        }

        if (0 === \count($contentTypes)) {
            $io->success('Nothing to do.');

            return 0;
        }

        $fieldName = $this->configResolver->getParameter('fieldtype_metas_identifier', 'nova_ezseo');

        foreach ($contentTypes as $contentType) {
            $io->section("Doing {$contentType->getName()}");
            if ($this->fieldInstaller->fieldExists($fieldName, $contentType)) {
                $io->block('Field exists');
                continue;
            }
            if (!$this->fieldInstaller->addToContentType($fieldName, $contentType)) {
                $io->error(
                    sprintf(
                        'There were errors when adding new field to <info>%s</info> ContentType: <error>%s</error>',
                        $contentType->getName($contentType->mainLanguageCode),
                        $this->fieldInstaller->getErrorMessage()
                    )
                );
                continue;
            }
            $io->block('FieldType added.');
        }

        $io->success('Done.');

        return 0;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->comment('Switching to Admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference(
            $this->userService->loadUser($this->adminUserId)
        );
    }
}
