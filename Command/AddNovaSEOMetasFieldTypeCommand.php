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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use DateTime;
use eZ\Publish\API\Repository\Repository;

/**
 * Class AddNovaSEOMetasFieldTypeCommand
 */
class AddNovaSEOMetasFieldTypeCommand extends ContainerAwareCommand
{
    /**
     * Repository eZ Publish
     *
     * @var Repository
     */
    protected $eZPublishRepository;

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
            ->setName( 'novae_zseo:addnovaseometasfieldtype' )
            ->addOption( 'identifier', null, InputOption::VALUE_REQUIRED, 'a content type identifier' )
            ->addOption( 'identifiers', null, InputOption::VALUE_REQUIRED, 'some content types identifier, separated by a comma' )
            ->addOption( 'group_identifier', null, InputOption::VALUE_REQUIRED, 'a content type group identifier' )
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $input;// phpmd trick
        $contentTypeService = $this->eZPublishRepository->getContentTypeService();
        $configResolver = $this->getContainer()->get( "ezpublish.config.resolver" );
        try
        {
            $contentTypes = $this->contentTypes;
            if ( count( $contentTypes ) == 0 )
            {
                $output->writeln( "Nothing to do." );
                return;
            }
            foreach ( $contentTypes as $contentType )
            {
                /** @var ContentType $contentType */
                $contentTypeDraft = $contentTypeService->createContentTypeDraft( $contentType );

                $typeUpdate = $contentTypeService->newContentTypeUpdateStruct();
                $typeUpdate->modificationDate = new DateTime();

                $knowLanguage = array_keys( $contentType->getDescriptions() );
                // just in case the mainLanguageCode is used for fallback, we need it
                if ( !in_array( $contentType->mainLanguageCode, $knowLanguage ) )
                {
                    $knowLanguage[] = $contentType->mainLanguageCode;
                }
                $fieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
                    $configResolver->getParameter( 'fieldtype_metas_identifier', 'novae_zseo' ),
                    'novaseometas'
                );
                $fieldCreateStruct->names = array_fill_keys( $knowLanguage, 'Metas' );
                $fieldCreateStruct->descriptions = array_fill_keys( $knowLanguage, 'Metas for Search Engine Optimizations' );
                $fieldCreateStruct->fieldGroup = 'novaseo';
                $fieldCreateStruct->position = 100;
                $fieldCreateStruct->isTranslatable = true;
                $fieldCreateStruct->isRequired = false;
                $fieldCreateStruct->isSearchable = false;
                $fieldCreateStruct->isInfoCollector = false;
                $contentTypeService->updateContentTypeDraft( $contentTypeDraft, $typeUpdate );
                $contentTypeService->addFieldDefinition( $contentTypeDraft, $fieldCreateStruct );
                $contentTypeService->publishContentTypeDraft( $contentTypeDraft );
            }
        }
        catch ( \Exception $e )
        {
            $output->writeln( "<error>{$e->getMessage()}</error>" );
            return;
        }
        $output->writeln( "FieldType added." );
    }

    /**
     * Get the ContentType depending on the input arguments
     *
     * @param InputInterface     $input
     *
     * @return ContentType[]
     */
    protected function getContentTypes( InputInterface $input )
    {
        $contentTypes = [];
        $contentTypeService = $this->eZPublishRepository->getContentTypeService();

        if ( $contentTypeGroupIdentifier = $input->getOption( 'group_identifier' ) )
        {
            $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier( $contentTypeGroupIdentifier );
            $contentTypes     = $contentTypeService->loadContentTypes( $contentTypeGroup );
        }
        if ( $contentTypeIdentifiers = explode( ",", $input->getOption( 'identifiers' ) ) )
        {
            foreach ( $contentTypeIdentifiers as $identifier )
            {
                if ( $identifier != "" )
                {
                    $contentTypes[] = $contentTypeService->loadContentTypeByIdentifier( $identifier );
                }
            }
        }
        if ( $contentTypeIdentifier = $input->getOption( 'identifier' ) )
        {
            $contentTypes[] = $contentTypeService->loadContentTypeByIdentifier( $contentTypeIdentifier );
        }
        return $contentTypes;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact( InputInterface $input, OutputInterface $output )
    {
        $contentTypes = $this->getContentTypes( $input );
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
        $this->eZPublishRepository = $this->getContainer()->get( "ezpublish.api.repository" );
        $this->eZPublishRepository->setCurrentUser( $this->eZPublishRepository->getUserService()->loadUser( 14 ) );
    }
}
