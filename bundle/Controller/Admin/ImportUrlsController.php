<?php
/**
 * NovaeZSEOBundle ImportUrlsController.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <m.bouchaala@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\Controller\Admin;

use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Novactive\Bundle\eZSEOBundle\Form\Type\ImportUrlsType;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Novactive\Bundle\eZSEOBundle\Core\Helper\ImportUrlsHelper;
use Symfony\Component\Translation\TranslatorInterface;

class ImportUrlsController extends Controller
{
    const URL_LIMIT = 10;

    /**
     * @var ImportUrlsHelper
     */
    private $importUrlHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ImportUrlsHelper $importUrlHelper,
        TranslatorInterface $translator,
        LoggerInterface $logger
    )
    {
        $this->importUrlHelper = $importUrlHelper;
        $this->translator      = $translator;
        $this->logger          = $logger;
    }

    /**
     * @Route("/url-redirect-import", name="novactive_platform_admin_ui.import-redirect-url")
     * @Template("NovaeZSEOBundle::platform_admin/import_urls.html.twig")
     */
    public function importAction(Request $request): array
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Limited access !!!');
        }

        $params  = [];
        $session = $request->getSession();

        $form = $this->createForm(ImportUrlsType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $file = $request->files->get('novaseo_import_urls')['file'];
            if ($file) {
                $filePath = $file->getRealPath();
                if ($file->getMimeType() == 'text/plain') {
                    try {
                        $resultUrlsImported =  $this->importUrlHelper->importUrlRedirection($filePath);

                        if (isset($resultUrlsImported['errorType'])) {
                            $params['errors'][] = $resultUrlsImported['errorType'];
                        } else {
                            $fileName     = $file->getClientOriginalName();
                            $fileToImport = $file->move('redirectUrls/upload', $fileName);
                            $fileLog      = $resultUrlsImported['fileLog'];
                            $this->importUrlHelper->saveFileHistory($fileToImport, $fileLog);
                            $session->set('IMPORT_URL', $resultUrlsImported);
                        }
                    } catch (\Exception $e) {
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
    public function hisroryUrlsImported(Request $request): array
    {
        $result = $this->importUrlHelper->getLogsHistory();
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
    public function downloadAction($id): Response
    {
        $log = $this->getDoctrine()->getRepository("NovaeZSEOBundle:RedirectImportHistory")->find($id);
        if ($log) {
            $fileContent = $this->importUrlHelper->downloadFile($log);
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
