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
use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\Event\URLWildcardService;
use Novactive\Bundle\eZSEOBundle\Core\Helper\ImportUrlsHelper;
use Novactive\Bundle\eZSEOBundle\Entity\RedirectImportHistory;
use Novactive\Bundle\eZSEOBundle\Form\Type\DeleteUrlType;
use Novactive\Bundle\eZSEOBundle\Form\Type\ImportUrlsType;
use Novactive\Bundle\eZSEOBundle\Form\Type\RedirectType;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
#[\Symfony\Component\Routing\Attribute\Route(path: '/novaseo/redirect')]
class RedirectController extends Controller
{
    public const URL_LIMIT = 10;

    public function __construct(
        protected URLWildcardService $urlWildcardService,
        protected TranslatorInterface $translator,
        protected LoggerInterface $logger,
        protected PermissionResolver $permissionResolver,
        protected ImportUrlsHelper $importUrlsHelper,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/list', name: 'novaseo_redirect_list')]
    public function listAction(
        Request $request
    ): Response {
        if (!$this->permissionResolver->hasAccess('novaseobundle.redirects', 'view')) {
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
            $source = trim((string) $form->getData()['source']);
            $destination = trim((string) $form->getData()['destination']);
            $type = trim((string) $form->getData()['type']);

            if (!$this->permissionResolver->hasAccess('novaseobundle.redirects', 'add')) {
                throw new AccessDeniedException('Limited access !!!');
            }
            // verify if URL destination exists in source URL
            try {
                $urlExists = $this->urlWildcardService->translate($destination);
                $errors[] = $this->translator->trans(
                    'nova.redirect.create.exists',
                    ['url' => $destination],
                    'redirect'
                );
            } catch (Exception $e) {
                $e->getMessage();
            }

            if (('' !== $source || '' !== $destination) && ($source !== $destination) && (null === $urlExists)) {
                try {
                    $result = $this->urlWildcardService->create($source, $destination, $type);

                    if ($result) {
                        $messages[] = $source.' '.$this->translator->trans('nova.redirect.create.info', [], 'redirect');
                    }
                } catch (Exception $e) {
                    $message = explode(':', $e->getMessage());
                    $errors[] = isset($message[1]) ? $source.' '.$message[1] : $e->getMessage();
                    $this->logger->log(LogLevel::ERROR, $e->getMessage());
                }
            } else {
                $errors[] = $this->translator->trans('nova.redirect.create.warnning', [], 'redirect');
            }
        }

        if ($formDelete->isSubmitted() && $formDelete->isValid()) {
            $deleteAction = self::class.'::deleteAction';
            $response = $this->forward($deleteAction, ['request' => $request]);
            if (Response::HTTP_CREATED === $response->getStatusCode()) {
                $messages[] = $this->translator->trans('nova.redirect.delete.info', [], 'redirect');
            }
        }

        $page = $request->query->get('page') ?? 1;
        $pagerfanta = new Pagerfanta(new ArrayAdapter($this->urlWildcardService->loadAll()));

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
    ): Response {
        if (!$this->permissionResolver->hasAccess('novaseobundle.redirects', 'remove')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        $urlWildCardChoice = $request->get('WildcardIDList');

        try {
            if ($urlWildCardChoice) {
                foreach ($urlWildCardChoice as $item) {
                    $urlWildCard = $this->urlWildcardService->load($item);
                    $this->urlWildcardService->remove($urlWildCard);
                }

                return new Response(null, 201);
            }
        } catch (Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage());
        }

        return new Response();
    }

    #[\Symfony\Bridge\Twig\Attribute\Template('@NovaeZSEO/platform_admin/import_urls.html.twig')]
    #[\Symfony\Component\Routing\Attribute\Route(
        path: '/url-redirect-import',
        name: 'novactive_platform_admin_ui.import-redirect-url'
    )]
    public function importAction(
        Request $request
    ): array {
        if (!$this->permissionResolver->hasAccess('novaseobundle.redirects', 'import')) {
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
                        $resultUrlsImported = $this->importUrlsHelper->importUrlRedirection($filePath);
                        if (isset($resultUrlsImported['errorType'])) {
                            $params['errors'][] = $resultUrlsImported['errorType'];
                        } else {
                            $this->importUrlsHelper->saveFileHistory(
                                $file->getClientOriginalName(),
                                $resultUrlsImported['fileLog']
                            );
                            $session->set('IMPORT_URL', $resultUrlsImported);
                        }
                    } catch (Exception $e) {
                        $this->logger->log(LogLevel::ERROR, $e->getMessage());
                    }
                } else {
                    $params['errors'][] = $this->translator->trans(
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

    #[\Symfony\Bridge\Twig\Attribute\Template('@NovaeZSEO/platform_admin/history_urls_imported.html.twig')]
    #[\Symfony\Component\Routing\Attribute\Route(
        path: '/history-import-redirect-url',
        name: 'novactive_platform_admin_ui.history-import-redirect-url'
    )]
    public function historyUrlsImported(
        Request $request
    ): array {
        if (!$this->permissionResolver->hasAccess('novaseobundle.redirects', 'import')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        $result = $this->importUrlsHelper->getLogsHistory();
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

    #[\Symfony\Component\Routing\Attribute\Route(
        path: '/download-log-redirect-url/{id}',
        name: 'novactive_platform_admin_ui.download-log-redirect-url'
    )]
    public function downloadAction(
        int $id
    ): Response {
        if (!$this->permissionResolver->hasAccess('novaseobundle.redirects', 'import')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        $log = $this->entityManager->getRepository(RedirectImportHistory::class)->find($id);
        if ($log instanceof RedirectImportHistory) {
            $fileContent = $this->importUrlsHelper->downloadFile($log);
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
