<?php
/**
 * NovaeZSEOBundle AddNovaSEOMetasFieldTypeCommand
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use Novactive\Bundle\eZSEOBundle\Installer\Field;
use Novactive\Bundle\eZSEOBundle\Converter\ContentTypesHelper;

/**
 * Class AddNovaSEOMetasFieldTypeCommand
 */
class AddNovaSEOMetasFieldTypeCommand extends Command
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \eZ\Publish\API\Repository\Repository */
    private $repository;

    /** @var \eZ\Publish\API\Repository\UserService */
    private $userService;

    /** @var \Novactive\Bundle\eZSEOBundle\Installer\Field */
    private $fieldInstaller;

    /** @var \Novactive\Bundle\eZSEOBundle\Converter\ContentTypesHelper */
    private $contentTypesHelper;

    /** @var int */
    private $adminUserId;

    /**
     * List of the ContentType we'll manage
     *
     * @var ContentType[]
     */
    protected $contentTypes;

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('novae_zseo:addnovaseometasfieldtype')
            ->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'a content type identifier')
            ->addOption('identifiers', null, InputOption::VALUE_REQUIRED, 'some content types identifier, separated by a comma')
            ->addOption('group_identifier', null, InputOption::VALUE_REQUIRED, 'a content type group identifier')
            ->setDescription(
                'Add the novaseometas FieldType to Content Types'
            )->setHelp(
                <<<EOT
The command <info>%command.name%</info> add the FieldType 'novaseometas'.
You can select the Content Type via the <info>identifier</info>, <info>identifiers</info>, <info>group_identifier</info> option.
    - Identifier will be: <comment>%novae_zseo.default.fieldtype_metas_identifier%</comment>
    - Name will be: <comment>Metas</comment>
    - Category will be: <comment>SEO</comment>
EOT
            );
    }

    /**
     * Execute the Command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input;// phpmd trick
        $contentTypes = $this->contentTypes;
        if (count($contentTypes) == 0) {
            $output->writeln("Nothing to do.");

            return;
        }

        $fieldName = $this->configResolver->getParameter('fieldtype_metas_identifier', 'novae_zseo');

        foreach ($contentTypes as $contentType) {
            if (!$this->fieldInstaller->fieldExists($fieldName, $contentType)) {
                if (!$this->fieldInstaller->addToContentType($fieldName, $contentType)) {
                    $output->writeln(sprintf(
                        'There were errors when adding new field to <info>%s</info> ContentType: <error>%s</error>',
                        $contentType->getName($contentType->mainLanguageCode),
                        $this->fieldInstaller->getErrorMessage()
                    ));

                    return;
                }
                $output->writeln('FieldType added');
            }
            $output->writeln('FieldType already exists');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact( InputInterface $input, OutputInterface $output )
    {
        $contentTypes = array();

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

        $output->writeln( "<info>Selected Content Type:</info>" );
        foreach ( $contentTypes as $contentType )
        {
            /** @var ContentType $contentType */
            $output->writeln( "\t- {$contentType->getName( $contentType->mainLanguageCode )}" );
        }
        $helper   = $this->getHelper( 'question' );
        $question = new ConfirmationQuestion(
            "\n<question>Are you sure you want to add novaseometas all these Content Type?</question>[yes]",
            true
        );
        if ( !$helper->ask( $input, $output, $question ) )
        {
            return;
        }
        $this->contentTypes = $contentTypes;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        $input;// phpmd trick
        $output;// phpmd trick

        $this->repository->setCurrentUser($this->userService->loadUser($this->adminUserId));
    }

    /**
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \Novactive\Bundle\eZSEOBundle\Installer\Field $fieldInstaller
     * @param \Novactive\Bundle\eZSEOBundle\Converter\ContentTypesHelper $contentTypesHelper
     * @param int $adminUserId
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        Repository $repository,
        UserService $userService,
        Field $fieldInstaller,
        ContentTypesHelper $contentTypesHelper,
        $adminUserId
    ) {
        $this->configResolver = $configResolver;
        $this->repository = $repository;
        $this->userService = $userService;
        $this->fieldInstaller = $fieldInstaller;
        $this->contentTypesHelper = $contentTypesHelper;
        $this->adminUserId = $adminUserId;

        parent::__construct();
    }
}
