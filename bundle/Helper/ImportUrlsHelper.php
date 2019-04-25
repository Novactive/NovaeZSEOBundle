<?php

namespace Novactive\Bundle\eZSEOBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\SignalSlot\URLWildcardService;
use Novactive\Bundle\eZSEOBundle\Entity\RedirectImportHistory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Helper for import url.
 */
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
     * @param $webDirectory
     * @param TranslatorInterface $translator
     * @param LoggerInterface $logger
     * @param Filesystem $fileSystem
     */
    public function __construct(
        URLWildcardService $urlWildcardService,
        EntityManagerInterface $entityManager,
        $webDirectory,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        Filesystem $fileSystem
    ) {
        $this->urlWildCardService = $urlWildcardService;
        $this->entityManager = $entityManager;
        $this->webDirectory = $webDirectory;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->fs = $fileSystem;

    }

    /**
     * @param $filePath
     * @return array
     */
    public function importUrlRedirection($filePath)
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

    /**
     * @param $destination
     * @return \eZ\Publish\API\Repository\Values\Content\URLWildcardTranslationResult|null
     */
    public function checkUrlDestinationExist($destination)
    {
        $urlExists = null;

        try {
            $urlExists = $this->urlWildCardService->translate($destination);

        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return $urlExists;
    }

    /**
     * @param $source
     * @param $destination
     * @return array
     */
    public function saveUrls($source, $destination)
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

    /**
     * @param $fileToImport
     * @param $fileLog
     */
    public function saveFileHistory($fileToImport, $fileLog)
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

    /**
     * @return array|RedirectImportHistory[]
     */
    public function getLogsHistory()
    {
        return $this->entityManager->getRepository("NovaeZSEOBundle:RedirectImportHistory")->findAll();
    }

    /**
     * @param $error
     */
    public function log($error)
    {
        $this->logger->log(LogLevel::ERROR, $error);
    }

    /**
     * @param $msg
     * @return string
     */
    public function trans($msg)
    {
        return $this->translator->trans($msg, [], 'redirect');
    }

}
