<?php
/**
 * Created by PhpStorm.
 * User: aminebetari
 * Date: 28/03/19
 * Time: 17:20
 */

namespace Novactive\Bundle\eZSEOBundle\Controller\Admin;

use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Novactive\Bundle\eZSEOBundle\Form\Type\RedirectType;
use Novactive\Bundle\eZSEOBundle\Form\Type\DeleteUrlType;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use eZ\Publish\Core\Repository\URLWildcardService;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class RedirectController extends Controller
{
    const URL_LIMIT = 10;

    /**
     * @var URLWildcardService
     */
    private $urlWildCardService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    public function __construct(URLWildcardService $urlWildCardService, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->urlWildCardService = $urlWildCardService;
        $this->translator         = $translator;
        $this->logger             = $logger;
    }

    /**
     * @Route("/urlwildcards", name="novactive_platform_admin_ui.list")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
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
            $source      = trim($form->getData()["source"]);
            $destination = trim($form->getData()["destination"]);
            $type        = trim($form->getData()["type"]);

            // verify if URL destination exists in source URL
            try {
                $urlExists = $this->urlWildCardService->translate($destination);
                $errors[] = $this->translator->trans('nova.redirect.create.exists', ['url' => $destination], 'redirect');
            } catch (\Exception $e) {
                // TODO
            }

            if (($source != "" || $destination != "") && ($source != $destination) && ($urlExists === null)) {
                // try to save data in table ezurlwildcard
                try {
                    $result = $this->urlWildCardService->create($source, $destination, $type);

                    if ($result) {
                        $messages[] = $source.' '. $this->translator->trans('nova.redirect.create.info', [], 'redirect');
                    }
                } catch (\Exception $e) {
                    $message = explode(':', $e->getMessage());
                    $errors[] = isset($message[1]) ? $source. ' '.$message[1] : $e->getMessage();
                    $this->logger->log(\Psr\Log\LogLevel::ERROR, $e->getMessage());
                }
            } else {
                $errors[] = $this->translator->trans('nova.redirect.create.warnning', [], 'redirect');
            }
        }

        // submit form delete
        if ($formDelete->isValid()) {
            $response = $this->forward(
                'NovaeZSEOBundle:Admin/Redirect:delete',
                [
                'request'    => $request,
                ]
            );

            if ($response->getStatusCode() == Response::HTTP_CREATED) {
                $messages[] = $this->translator->trans('nova.redirect.delete.info', [], 'redirect');
            }
        }

        $page = $request->query->get('page') ?? 1;
        // get datas
        $pagerfanta = new Pagerfanta(
            new ArrayAdapter($this->urlWildCardService->loadAll())
        );

        $pagerfanta->setMaxPerPage(self::URL_LIMIT);
        $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));

        return $this->render(
            'NovaeZSEOBundle::platform_admin/list_url_wildcard.html.twig',
            [
            'pager'         => $pagerfanta,
            'form'          => $form->createView(),
            'errors'        => $errors,
            'messages'      => $messages,
            'formDelete'    => $formDelete->createView(),
            ]
        );
    }

    /**
     * delete a urlwildcard
     *
     * @param  Request $request
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException('Limited access !!!');
        }
        
        $urlWildCardChoice = $request->get("WildcardIDList");

        // delete a wildcard url
        try {
            if ($urlWildCardChoice) {
                foreach ($urlWildCardChoice as $item) {
                    $urlWildCard = $this->urlWildCardService->load($item);
                    $this->urlWildCardService->remove($urlWildCard);
                }
                // return custom response
                return new Response(null, 201);
            }
        } catch (\Exception $e) {
            $this->logger->log(\Psr\Log\LogLevel::ERROR, $e->getMessage());
            return new Response();
        }
    }
}
