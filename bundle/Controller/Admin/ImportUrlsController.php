<?php

namespace Novactive\Bundle\eZSEOBundle\Controller\Admin;

use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Novactive\Bundle\eZSEOBundle\Form\Type\ImportUrlsType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImportUrlsController extends Controller
{
    const URL_LIMIT = 10;

    /**
     * @param Request $request
     * @Route("/url-redirect-import", name="novactive_platform_admin_ui.import-redirect-url")
     * @return Response
     */
    public function importAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Limited access !!!');
        }

        $params = $message = [];
        $importUrlHelper = $this->container->get('novactive_ezseobundle_importurls_helper');
        $session = $request->getSession();

        $form = $this->createForm(ImportUrlsType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $file = $request->files->get('novaseo_import_urls')['file'];
            if ($file) {
                $filePath = $file->getRealPath();
                if ($file->getMimeType() == 'text/plain') {
                    try {
                        $resultUrlsImported = $importUrlHelper->importUrlRedirection($filePath);

                        if (isset($resultUrlsImported['errorType'])) {
                            $params['errors'][] = $resultUrlsImported['errorType'];
                        } else {
                            $fileName = $file->getClientOriginalName();
                            $fileToImport = $file->move('redirectUrls/upload', $fileName);
                            $fileLog = $resultUrlsImported['fileLog'];
                            $importUrlHelper->saveFileHistory($fileToImport, $fileLog);
                            $session->set('IMPORT_URL', $resultUrlsImported);

                        }
                    } catch (\Exception $e) {
                        $importUrlHelper->log($e->getMessage());
                    }
                } else {
                    $params['errors'][] = $importUrlHelper->trans('nova.import.root.form.error.invalid_type');
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
            $params['pager'] = $pagerfanta;
            $params['totalImported'] = $lastUrlImported['totalImported'];
            $params['totalUrls'] = $lastUrlImported['totalUrls'];

        }

        $params += [
            'form' => $form->createView(),
        ];

        return $this->render(
            'NovaeZSEOBundle::platform_admin/import_urls.html.twig',
            $params
        );
    }

    /**
     * @param Request $request
     * @Route("/history-import-redirect-url", name="novactive_platform_admin_ui.history-import-redirect-url")
     * @return Response
     */
    public function hisroryUrlsImported(Request $request)
    {
        $importUrlHelper = $this->container->get('novactive_ezseobundle_importurls_helper');
        $result = $importUrlHelper->getLogsHistory();
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


        return $this->render(
            'NovaeZSEOBundle::platform_admin/history_urls_imported.html.twig',
            $params
        );
    }

    /**
     * @Route("/download-log-redirect-url/{id}", name="novactive_platform_admin_ui.download-log-redirect-url")
     * @return Response
     */
    public function downloadAction($id)
    {
        $log = $this->getDoctrine()->getRepository("NovaeZSEOBundle:RedirectImportHistory")->find($id);
        if ($log) {
            $stream = new Stream($log->getPath());
            $response = new BinaryFileResponse($stream);
            $response->headers->set('Content-Type', 'text/plain');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $stream->getFilename());

            return $response;
        }

        return $this->redirectToRoute('novactive_platform_admin_ui.history-import-redirect-url');

    }
}
