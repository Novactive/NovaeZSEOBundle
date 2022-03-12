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
use Exception;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\Event\URLWildcardService;
use Ibexa\Contracts\AdminUi\Controller\Controller;
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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/novaseo/redirect")
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class RedirectController extends Controller
{
    public const URL_LIMIT = 10;

    /**
     * @Route("/list", name="novaseo_redirect_list")
     */
    public function listAction(
        Request $request,
        URLWildcardService $urlWildcardService,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        PermissionResolver $permissionResolver
    ): Response {
        if (!$permissionResolver->hasAccess('novaseobundle.redirects', 'view')) {
            throw new AccessDeniedException('Limited access !!!');
        }

        $errors = [];
        $messages = [];
        $urlExists = null;

        $form = $this->createForm(RedirectType::class);
        $form->handleRequest($request);

        $formDelete = $this->createForm(DeleteUrlType::class);
        $formDelete->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $source = trim($form->getData()['source']);
            $destination = trim($form->getData()['destination']);
            $type = trim($form->getData()['type']);

            if (!$permissionResolver->hasAccess('novaseobundle.redirects', 'add')) {
                throw new AccessDeniedException('Limited access !!!');
            }
            // verify if URL destination exists in source URL
            try {
                $urlExists = $urlWildcardService->translate($destination);
                $errors[] = $translator->trans('nova.redirect.create.exists', ['url' => $destination], 'redirect');
            } catch (Exception $e) {
                $e->getMessage();
            }

            if (('' !== $source || '' !== $destination) && ($source !== $destination) && (null === $urlExists)) {
                try {
                    $result = $urlWildcardService->create($source, $destination, $type);

                    if ($result) {
                        $messages[] = $source.' '.$translator->trans('nova.redirect.create.info', [], 'redirect');
                    }
                } catch (Exception $e) {
                    $message = explode(':', $e->getMessage());
                    $errors[] = isset($message[1]) ? $source.' '.$message[1] : $e->getMessage();
                    $logger->log(LogLevel::ERROR, $e->getMessage());
                }
            } else {
                $errors[] = $translator->trans('nova.redirect.create.warnning', [], 'redirect');
            }
        }

        if ($formDelete->isSubmitted() && $formDelete->isValid()) {
            $deleteAction = self::class.'::deleteAction';
            $response = $this->forward($deleteAction, ['request' => $request]);
            if (Response::HTTP_CREATED === $response->getStatusCode()) {
                $messages[] = $translator->trans('nova.redirect.delete.info', [], 'redirect');
            }
        }

        $page = $request->query->get('page') ?? 1;
        $pagerfanta = new Pagerfanta(new ArrayAdapter($urlWildcardService->loadAll()));

        $pagerfanta->setMaxPerPage(self::URL_LIMIT);
        $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));

        return $this->render(
            '@NovaeZSEO/platform_admin/list_url_wildcard.html.twig',
            [
                'pager' => $pagerfanta,
                'form' => $form->createView(),
                'errors' => $errors,
                'messages' => $messages,
                'formDelete' => $formDelete->createView(),
            ]
        );
    }

    public function deleteAction(
        Request $request,
        URLWildcardService $urlWildcardService,
        LoggerInterface $logger,
        PermissionResolver $permissionResolver
    ): Response {
        if (!$permissionResolver->hasAccess('novaseobundle.redirects', 'remove')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        $urlWildCardChoice = $request->get('WildcardIDList');

        try {
            if ($urlWildCardChoice) {
                foreach ($urlWildCardChoice as $item) {
                    $urlWildCard = $urlWildcardService->load($item);
                    $urlWildcardService->remove($urlWildCard);
                }

                return new Response(null, 201);
            }
        } catch (Exception $e) {
            $logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return new Response();
    }

    /**
     * @Route("/url-redirect-import", name="novactive_platform_admin_ui.import-redirect-url")
     * @Template("@NovaeZSEO/platform_admin/import_urls.html.twig")
     */
    public function importAction(
        Request $request,
        PermissionResolver $permissionResolver,
        ImportUrlsHelper $importUrlsHelper,
        LoggerInterface $logger,
        TranslatorInterface $translator
    ): array {
        if (!$permissionResolver->hasAccess('novaseobundle.redirects', 'import')) {
            throw new AccessDeniedException('Limited access !!!');
        }

        $params = [];
        $session = $request->getSession();

        $form = $this->createForm(ImportUrlsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $request->files->get('novaseo_import_urls')['file'];
            if ($file instanceof UploadedFile) {
                $filePath = $file->getRealPath();
                if ('text/plain' === $file->getMimeType()) {
                    try {
                        $resultUrlsImported = $importUrlsHelper->importUrlRedirection($filePath);
                        if (isset($resultUrlsImported['errorType'])) {
                            $params['errors'][] = $resultUrlsImported['errorType'];
                        } else {
                            $importUrlsHelper->saveFileHistory(
                                $file->getClientOriginalName(),
                                $resultUrlsImported['fileLog']
                            );
                            $session->set('IMPORT_URL', $resultUrlsImported);
                        }
                    } catch (Exception $e) {
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
            $pagerfanta = new Pagerfanta(new ArrayAdapter($lastUrlImported['return']));

            $pagerfanta->setMaxPerPage(self::URL_LIMIT);
            $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));
            $params['pager'] = $pagerfanta;
            $params['totalImported'] = $lastUrlImported['totalImported'];
            $params['totalUrls'] = $lastUrlImported['totalUrls'];
        }

        $params += [
            'form' => $form->createView(),
        ];

        return $params;
    }

    /**
     * @Route("/history-import-redirect-url", name="novactive_platform_admin_ui.history-import-redirect-url")
     * @Template("@NovaeZSEO/platform_admin/history_urls_imported.html.twig")
     */
    public function historyUrlsImported(
        Request $request,
        ImportUrlsHelper $importUrlsHelper,
        PermissionResolver $permissionResolver
    ): array {
        if (!$permissionResolver->hasAccess('novaseobundle.redirects', 'import')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        $result = $importUrlsHelper->getLogsHistory();
        $params = [];
        if (count($result) > 0) {
            $page = $request->query->get('page') ?? 1;
            $pagerfanta = new Pagerfanta(new ArrayAdapter($result));

            $pagerfanta->setMaxPerPage(self::URL_LIMIT);
            $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));

            $params = ['pager' => $pagerfanta];
        }

        return $params;
    }

    /**
     * @Route("/download-log-redirect-url/{id}", name="novactive_platform_admin_ui.download-log-redirect-url")
     */
    public function downloadAction(
        int $id,
        EntityManagerInterface $entityManager,
        ImportUrlsHelper $importUrlsHelper,
        PermissionResolver $permissionResolver
    ): Response {
        if (!$permissionResolver->hasAccess('novaseobundle.redirects', 'import')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        $log = $entityManager->getRepository(RedirectImportHistory::class)->find($id);
        if ($log instanceof RedirectImportHistory) {
            $fileContent = $importUrlsHelper->downloadFile($log);
            if ($fileContent) {
                $response = new Response($fileContent);
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
