<?php
/**
 * NovaeZSEOBundle RedirectController.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\SignalSlot\URLWildcardService;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Novactive\Bundle\eZSEOBundle\Core\Helper\ImportUrlsHelper;
use Novactive\Bundle\eZSEOBundle\Entity\RedirectImportHistory;
use Novactive\Bundle\eZSEOBundle\Form\Type\DeleteUrlType;
use Novactive\Bundle\eZSEOBundle\Form\Type\ImportUrlsType;
use Novactive\Bundle\eZSEOBundle\Form\Type\RedirectType;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/novaseo/redirect")
 */
class RedirectController extends Controller
{
    const URL_LIMIT = 10;

    /**
     * @Route("/list", name="novaseo_redirect_list")
     */
    public function listAction(
        Request $request,
        URLWildcardService $urlWildcardService,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        Security $security
    ): Response {
        if (!$security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Limited access !!!');
        }

        $errors    = [];
        $messages  = [];
        $urlExists = null;
        // create form (add and delete)
        $form = $this->createForm(RedirectType::class);
        $form->handleRequest($request);

        // create form delete URL
        $formDelete = $this->createForm(DeleteUrlType::class);
        $formDelete->handleRequest($request);

        // Form create
        if ($form->isValid()) {
            $source      = trim($form->getData()['source']);
            $destination = trim($form->getData()['destination']);
            $type        = trim($form->getData()['type']);

            // verify if URL destination exists in source URL
            try {
                $urlExists = $urlWildcardService->translate($destination);
                $errors[]  = $translator->trans(
                    'nova.redirect.create.exists',
                    ['url' => $destination],
                    'redirect'
                );
            } catch (\Exception $e) {
                $e->getMessage();
            }

            if (('' != $source || '' != $destination) && ($source != $destination) && (null === $urlExists)) {
                // try to save data in table ezurlwildcard
                try {
                    $result = $urlWildcardService->create($source, $destination, $type);

                    if ($result) {
                        $messages[] = $source.' '.$translator->trans('nova.redirect.create.info', [], 'redirect');
                    }
                } catch (\Exception $e) {
                    $message  = explode(':', $e->getMessage());
                    $errors[] = isset($message[1]) ? $source.' '.$message[1] : $e->getMessage();
                    $logger->log(LogLevel::ERROR, $e->getMessage());
                }
            } else {
                $errors[] = $translator->trans('nova.redirect.create.warnning', [], 'redirect');
            }
        }

        // submit form delete
        if ($formDelete->isValid()) {
            $response = $this->forward(
                'NovaeZSEOBundle:Admin/Redirect:delete',
                [
                    'request' => $request,
                ]
            );

            if (Response::HTTP_CREATED == $response->getStatusCode()) {
                $messages[] = $translator->trans('nova.redirect.delete.info', [], 'redirect');
            }
        }

        $page = $request->query->get('page') ?? 1;
        // get datas
        $pagerfanta = new Pagerfanta(
            new ArrayAdapter($urlWildcardService->loadAll())
        );

        $pagerfanta->setMaxPerPage(self::URL_LIMIT);
        $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));

        return $this->render(
            'NovaeZSEOBundle::platform_admin/list_url_wildcard.html.twig',
            [
                'pager'      => $pagerfanta,
                'form'       => $form->createView(),
                'errors'     => $errors,
                'messages'   => $messages,
                'formDelete' => $formDelete->createView(),
            ]
        );
    }

    /**
     * delete a urlwildcard.
     */
    public function deleteAction(
        Request $request,
        URLWildcardService $urlWildcardService,
        LoggerInterface $logger,
        Security $security
    ): Response {
        if (!$security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        $urlWildCardChoice = $request->get('WildcardIDList');

        // delete a wildcard url
        try {
            if ($urlWildCardChoice) {
                foreach ($urlWildCardChoice as $item) {
                    $urlWildCard = $urlWildcardService->load($item);
                    $urlWildcardService->remove($urlWildCard);
                }

                // return custom response
                return new Response(null, 201);
            }
        } catch (\Exception $e) {
            $logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return new Response();
    }

    /**
     * @Route("/url-redirect-import", name="novactive_platform_admin_ui.import-redirect-url")
     * @Template("NovaeZSEOBundle::platform_admin/import_urls.html.twig")
     */
    public function importAction(
        Request $request,
        Security $security,
        ImportUrlsHelper $importUrlsHelper,
        LoggerInterface $logger,
        TranslatorInterface $translator
    ): array {
        if (!$security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Limited access !!!');
        }

        $params  = [];
        $session = $request->getSession();

        $form = $this->createForm(ImportUrlsType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $file = $request->files->get('novaseo_import_urls')['file'];
            if ($file instanceof UploadedFile) {
                $filePath = $file->getRealPath();
                if ('text/plain' === $file->getMimeType()) {
                    try {
                        $resultUrlsImported = $importUrlsHelper->importUrlRedirection($filePath);

                        if (isset($resultUrlsImported['errorType'])) {
                            $params['errors'][] = $resultUrlsImported['errorType'];
                        } else {
                            $fileName     = $file->getClientOriginalName();
                            $fileToImport = $file->move('redirectUrls/upload', $fileName);
                            $fileLog      = $resultUrlsImported['fileLog'];
                            $importUrlsHelper->saveFileHistory($fileToImport, $fileLog);
                            $session->set('IMPORT_URL', $resultUrlsImported);
                        }
                    } catch (\Exception $e) {
                        $logger->log(LogLevel::ERROR, $e->getMessage());
                    }
                } else {
                    $params['errors'][] = $translator->trans(
                        'nova.import.root.form.error.invalid_type',
                        [],
                        'redirect'
                    );
                }
            }
        }

        $lastUrlImported = $session->get('IMPORT_URL');

        if ($lastUrlImported) {
            $page = $request->query->get('page') ?? 1;

            $pagerfanta = new Pagerfanta(
                new ArrayAdapter($lastUrlImported['return'])
            );

            $pagerfanta->setMaxPerPage(self::URL_LIMIT);
            $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));
            $params['pager']         = $pagerfanta;
            $params['totalImported'] = $lastUrlImported['totalImported'];
            $params['totalUrls']     = $lastUrlImported['totalUrls'];
        }

        $params += [
            'form' => $form->createView(),
        ];

        return $params;
    }

    /**
     * @Route("/history-import-redirect-url", name="novactive_platform_admin_ui.history-import-redirect-url")
     * @Template("NovaeZSEOBundle::platform_admin/history_urls_imported.html.twig")
     */
    public function hisroryUrlsImported(Request $request, ImportUrlsHelper $importUrlsHelper): array
    {
        $result = $importUrlsHelper->getLogsHistory();
        $params = [];
        if (count($result) > 0) {
            $page = $request->query->get('page') ?? 1;

            $pagerfanta = new Pagerfanta(
                new ArrayAdapter($result)
            );

            $pagerfanta->setMaxPerPage(self::URL_LIMIT);
            $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));

            $params = [
                'pager' => $pagerfanta,
            ];
        }

        return $params;
    }

    /**
     * @Route("/download-log-redirect-url/{id}", name="novactive_platform_admin_ui.download-log-redirect-url")
     */
    public function downloadAction(
        EntityManagerInterface $entityManager,
        int $id,
        ImportUrlsHelper $importUrlsHelper
    ): Response {
        $log = $entityManager->getRepository('NovaeZSEOBundle:RedirectImportHistory')->find($id);
        if ($log instanceof RedirectImportHistory) {
            $fileContent = $importUrlsHelper->downloadFile($log);
            if ($fileContent) {
                $response    = new Response($fileContent);
                $disposition = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    $log->getNameFile()
                );
                $response->headers->set('Content-Disposition', $disposition);
                $response->headers->set('Cache-Control', 'private');
                $response->headers->set('Content-type', 'application/octet-stream');

                return $response;
            }
        }

        return $this->redirectToRoute('novactive_platform_admin_ui.history-import-redirect-url');
    }
}
