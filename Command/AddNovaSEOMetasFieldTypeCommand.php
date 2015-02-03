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

use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter\Date;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use DateTime;

/**
 * Class AddNovaSEOMetasFieldTypeCommand
 */
class AddNovaSEOMetasFieldTypeCommand extends ContainerAwareCommand
{
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
You can select the Content Type via the <info>identier</info>, <info>identifiers</info>, <info>group_identier</info> option.
The identifier will be : <comment>metas</comment>, the Name : <comment>Metas</comment> and the Category: <comment>SEO</comment>
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
        /** @var $repository \eZ\Publish\API\Repository\Repository */
        $repository         = $this->getContainer()->get( "ezpublish.api.repository" );
        $contentTypeService = $repository->getContentTypeService();
        $repository->setCurrentUser( $repository->getUserService()->loadUser( 14 ) );

        $contentTypeGroupIdentifier = $input->getOption( 'group_identifier' ) ? $input->getOption( 'group_identifier' ) : false;
        $contentTypeIdentifiers = $input->getOption( 'identifiers' ) ? explode( ",", $input->getOption( 'identifiers' ) ) : false;
        $contentTypeIdentifier = $input->getOption( 'identifier' ) ? $input->getOption( 'identifier' ) : false;

        try
        {
            $contentTypes = [];
            if ( $contentTypeGroupIdentifier )
            {
                $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier( $contentTypeGroupIdentifier );
                $contentTypes     = $contentTypeService->loadContentTypes( $contentTypeGroup );
            }

            if ( $contentTypeIdentifiers )
            {
                if ( !is_array( $contentTypeIdentifiers ) )
                {
                    $contentTypeIdentifiers = [ $contentTypeIdentifiers ];
                }

                foreach ( $contentTypeIdentifiers as $identifier )
                {
                    $contentTypes[] = $contentTypeService->loadContentTypeByIdentifier( $identifier );
                }
            }
            if ( $contentTypeIdentifier )
            {
                $contentTypes[] = $contentTypeService->loadContentTypeByIdentifier( $contentTypeIdentifier );
            }

            $output->writeln( "<info>Selected Content Type:</info>" );
            foreach ( $contentTypes as $contentType )
            {
                /** @var ContentType $contentType */
                $output->writeln( "\t- {$contentType->getName( $contentType->mainLanguageCode )}" );
            }
            $helper   = $this->getHelper( 'question' );
            $question = new ConfirmationQuestion(
                "\n<question>Are you sure you want to add novaseometas all these Content Type?</question>[no]",
                false
            );
            if ( !$helper->ask( $input, $output, $question ) )
            {
                $output->writeln( "Nothing done." );
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

                $fieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct( 'metas', 'novaseometas' );
                $fieldCreateStruct->names = array_fill_keys( $knowLanguage, 'Metas' );
                $fieldCreateStruct->descriptions = array_fill_keys( $knowLanguage, 'The Metas' );
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
        catch ( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e )
        {
            $output->writeln( "<error>{$e->getMessage()}</error>" );
            return;
        }
        $output->writeln( "FieldType added." );
    }

}
