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

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Ibexa\Contracts\Core\Repository\URLWildcardService;
use Ibexa\Core\IO\IOService;
use Novactive\Bundle\eZSEOBundle\Entity\RedirectImportHistory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /**
     * @var IOService
     */
    private $ioService;

    /**
     * @var string
     */
    private $cacheDirectory;

    public function __construct(
        IOService $ioService,
        URLWildcardService $urlWildcardService,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        Filesystem $fileSystem,
        string $cacheDirectory
    ) {
        $this->urlWildCardService = $urlWildcardService;
        $this->entityManager = $entityManager;
        $this->cacheDirectory = $cacheDirectory;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->fs = $fileSystem;
        $this->ioService = $ioService;
    }

    public function importUrlRedirection(string $filePath): array
    {
        $counter = 0;
        $params = $return = [];

        $fileToImport = fopen($filePath, 'r');
        if (false !== $fileToImport) {
            $totalImported = 0;
            $totalUrls = 0;
            $filename = 'redirectUrls/report/redirect_import_urls-'.date('d-m-Y-H-i-s').'.csv';
            $filePath = $this->cacheDirectory.$filename;
            $this->fs->dumpFile($filePath, "Source;Destination;Message;Status\n");

            while (false !== ($data = fgetcsv($fileToImport, 1000, ';'))) {
                if (0 == $counter) {
                    ++$counter;
                    continue;
                }

                if (isset($data[0]) and isset($data[1])) {
                    $source = $data[0]; // source
                    $destination = $data[1]; // destination
                    ++$totalUrls;
                    // verify if URL destination exists in source URL
                    $verifResult = $this->checkUrlDestinationExist($destination);

                    if (
                        ('' != $source || '' != $destination)
                        && ($source != $destination)
                        && (null === $verifResult['urlExists'])
                    ) {
                        // try to save data in table ezurlwildcard
                        $saveResult = $this->saveUrls($filePath, $source, $destination);
                        if ('OK' === $saveResult['imported']) {
                            ++$totalImported;
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
                            $filePath,
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

            if (!isset($params['errorType'])) {
                try {
                    $uploadedFileStruct = $this->ioService->newBinaryCreateStructFromLocalFile($filePath);
                    $uploadedFileStruct->id = $filename;
                    $this->ioService->createBinaryFile($uploadedFileStruct);
                    $this->fs->remove($filePath);
                } catch (\Exception $e) {
                    $this->logger->log(LogLevel::ERROR, $e->getMessage());
                }
            }

            $params += [
                'totalImported' => $totalImported,
                'totalUrls' => $totalUrls,
                'return' => $return,
                'fileLog' => $filename,
            ];
        }

        return $params;
    }

    public function checkUrlDestinationExist(string $destination): bool
    {
        $urlExists = false;

        try {
            $urlExists = $this->urlWildCardService->translate($destination);
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return $urlExists;
    }

    public function saveUrls(string $filePath, string $source, string $destination): array
    {
        $return = [];
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
                    $filePath,
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
                $filePath,
                "$source;$destination;$msg;$status\n"
            );
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return $return;
    }

    public function saveFileHistory(string $originalFileName, string $fileLog): void
    {
        try {
            $redirectImportHistory = new RedirectImportHistory();
            $redirectImportHistory->setNameFile($originalFileName);
            $redirectImportHistory->setDate(new DateTime());
            $redirectImportHistory->setPath($fileLog);
            $this->entityManager->persist($redirectImportHistory);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }
    }

    public function downloadFile(RedirectImportHistory $log): ?string
    {
        try {
            $file = $this->ioService->loadBinaryFile($log->getPath());

            return $this->ioService->getFileContents($file);
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return null;
    }

    public function getLogsHistory(): array
    {
        return $this->entityManager->getRepository(RedirectImportHistory::class)->findAll();
    }
}
