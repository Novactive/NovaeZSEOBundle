<?php
/**
 * NovaeZSEOBundle ImportUrlsHelper.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <m.bouchaala@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\Core\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use eZ\Publish\Core\SignalSlot\URLWildcardService;
use Novactive\Bundle\eZSEOBundle\Entity\RedirectImportHistory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImportUrlsHelper
{
    /**
     * @var URLWildcardService
     */
    private $urlWildCardService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Filesystem
     */
    private $fs;

    private $webDirectory;

    /**
     * ImportUrlsHelper constructor.
     * @param URLWildcardService $urlWildcardService
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param LoggerInterface $logger
     * @param Filesystem $fileSystem
     * @param ContainerInterface $container
     */
    public function __construct(
        URLWildcardService $urlWildcardService,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        Filesystem $fileSystem,
        ContainerInterface $container
    ) {
        $this->urlWildCardService = $urlWildcardService;
        $this->entityManager = $entityManager;
        $this->webDirectory = $container->getParameter('kernel.project_dir')."/web/";
        $this->translator = $translator;
        $this->logger = $logger;
        $this->fs = $fileSystem;
    }

    public function importUrlRedirection(String $filePath):array
    {
        $counter = 0;
        $params = $return = [];

        if (($fileToImport = fopen($filePath, 'r')) !== false) {
            $totalImported = 0;
            $totalUrls     = 0;
            //create file log
            $this->fs->dumpFile(
                $this->webDirectory."redirectUrls/logs/redirect_import_urls-".date('d-m-Y').".csv",
                "Source;Destination;Message;Status\n"
            );

            while (($data = fgetcsv($fileToImport, 1000, ";")) !== false) {
                if ($counter == 0) {
                    $counter++;
                    continue;
                } else {
                    if (isset($data[0]) and isset($data[1])) {
                        $source = $data[0]; // source
                        $destination = $data[1]; // destination
                        $totalUrls++;
                        // verify if URL destination exists in source URL
                        $verifResult = $this->checkUrlDestinationExist($destination);

                        if (('' != $source || '' != $destination) && ($source != $destination) && (null === $verifResult['urlExists'])) {
                            // try to save data in table ezurlwildcard
                            $saveResult = $this->saveUrls($source, $destination);
                            if ($saveResult['imported'] == 'OK') {
                                $totalImported++;
                            }
                            $return[] = $saveResult;
                        } else {

                            $msg = $this->translator->trans('nova.import.list.table.exists', [], 'redirect');
                            $status = 'KO';
                            $return[] = [
                                'source' => $source,
                                'destination' => $destination,
                                'msg' => $msg,
                                'imported' => $status,
                            ];
                            $this->fs->appendToFile(
                                $this->webDirectory."redirectUrls/logs/redirect_import_urls-".date('d-m-Y').".csv",
                                "$source;$destination;$msg;$status\n"
                            );
                        }
                    } else {
                        $params['errorType'] = $this->translator->trans(
                            'nova.import.root.form.error.invalid_file',
                            [],
                            'redirect'
                        );
                    }
                }
            }
            $params += [
                'totalImported' => $totalImported,
                'totalUrls' => $totalUrls,
                'return' => $return,
                'fileLog' => $this->webDirectory."redirectUrls/logs/redirect_import_urls-".date('d-m-Y').".csv",
            ];

        }

        return $params;
    }


    public function checkUrlDestinationExist(string $destination):bool
    {
        $urlExists = false;

        try {
            $urlExists = $this->urlWildCardService->translate($destination);

        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return $urlExists;
    }

    public function saveUrls(string $source, string $destination):array
    {
        $return = $errors = [];
        try {
            $result = $this->urlWildCardService->create($source, $destination, 'Redirection');
            if ($result) {
                $return = [
                    'source' => $source,
                    'destination' => $destination,
                    'msg' => $this->translator->trans('nova.import.list.table.info', [], 'redirect'),
                    'imported' => 'OK',
                ];

                $msg = $return['msg'];
                $status = $return['imported'];
                $this->fs->appendToFile(
                    $this->webDirectory."redirectUrls/logs/redirect_import_urls-".date('d-m-Y').".csv",
                    "$source;$destination;$msg;$status\n"
                );
            }
        } catch (\Exception $e) {
            $return = [
                'source' => $source,
                'destination' => $destination,
                'msg' => $e->getMessage(),
                'imported' => 'KO',
            ];
            $msg = $return['msg'];
            $status = $return['imported'];
            $this->fs->appendToFile(
                $this->webDirectory."redirectUrls/logs/redirect_import_urls-".date('d-m-Y').".csv",
                "$source;$destination;$msg;$status\n"
            );
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return $return;
    }

    public function saveFileHistory(File $fileToImport, string $fileLog)
    {
        try {
            $redirectImportHistory = new RedirectImportHistory();
            $redirectImportHistory->setNameFile($fileToImport->getFilename());
            $redirectImportHistory->setDate(new \DateTime());
            $redirectImportHistory->setPath($fileLog);
            $this->entityManager->persist($redirectImportHistory);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }
    }

    public function getLogsHistory():array
    {
        return $this->entityManager->getRepository("NovaeZSEOBundle:RedirectImportHistory")->findAll();
    }

    public function log(string $error)
    {
        $this->logger->log(LogLevel::ERROR, $error);
    }

    public function trans(string $msg):string
    {
        return $this->translator->trans($msg, [], 'redirect');
    }

}
