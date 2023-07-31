<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Interviewer;
use App\Entity\User;
use App\Form\Admin\InterviewerType;
use App\ListPage\Admin\InterviewerAreaList;
use App\ListPage\Admin\InterviewerListPage;
use App\Utility\AccountCreationHelper;
use App\Utility\ConfirmAction\Admin\DeleteInterviewerConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @Route("/interviewers", name="interviewers_")
 */
class InterviewerController extends AbstractController
{
    /**
     * @Route(name="list")
     */
    public function list(InterviewerListPage $listPage, Request $request): Response
    {
        $listPage->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render("admin/interviewer/list.html.twig", [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }


    /**
     * @Route("/{id}/view", name="view")
     */
    public function view(InterviewerAreaList $areaList, Request $request, Interviewer $interviewer): Response
    {
        $areaList->setInterviewer($interviewer);
        $areaList->handleRequest($request);

        if ($areaList->isClearClicked()) {
            return new RedirectResponse($areaList->getClearUrl());
        }

        return $this->render("admin/interviewer/view.html.twig", [
            'areas' => $areaList->getData(),
            'areaForm' => $areaList->getFiltersForm()->createView(),
            'interviewer' => $interviewer,
        ]);
    }

    /**
     * @Route("/add", name="add")
     * //@Route("/{id}/edit", name="edit") ## editing introduces complications with verifying the new email address
     */
    public function edit(Request $request, EntityManagerInterface $entityManager, AccountCreationHelper $accountCreationHelper, Interviewer $interviewer = null): Response
    {
        if (!$interviewer) {
            $interviewer = (new Interviewer())->setUser(new User());
        }

        $originalUsername = $interviewer->getUser()->getUserIdentifier();
        $form = $this->createForm(InterviewerType::class, $interviewer);
        $form->handleRequest($request);
        $successUrl = $this->generateUrl('admin_interviewers_list');

        if ($form->isSubmitted()) {
            $cancelButton = $form->get('button_group')->get('cancel');
            if ($cancelButton instanceof SubmitButton && $cancelButton->isClicked()) {
                return new RedirectResponse($successUrl);
            }

            if ($form->isValid()) {
                /** @var Interviewer $interviewer */
                $interviewer = $form->getData();

                if (!$interviewer->getId()) {
                    $banner = new NotificationBanner(
                        new TranslatableMessage('notification.success', [], 'messages'),
                        "interviewer.add.success-notification.heading",
                        "interviewer.add.success-notification.content",
                        ['style' => NotificationBanner::STYLE_SUCCESS],
                        ['name' => $interviewer->getName(), 'serialId' => $interviewer->getSerialId(), 'email' => $interviewer->getUser()->getUsername()],
                        'admin'
                    );

                    $flashBag = $request->getSession()->getFlashBag();
                    $flashBag->add(NotificationBanner::FLASH_BAG_TYPE, $banner);

                    $entityManager->persist($interviewer);
                } /* else {
                    // TODO: handle changing of interviewer email address
                    if ($originalUsername !== ($newUsername = $interviewer->getUser()->getUsername())) {
                        $interviewer->getUser()->setUsername($originalUsername);
                        $accountCreationHelper->sendAccountCreationEmail($interviewer, $newUsername);
                    }
                } */
                $entityManager->flush();
                return new RedirectResponse($successUrl);
            }
        }

        return $this->render('admin/interviewer/edit.html.twig', [
            'form' => $form->createView(),
            'interviewer' => $form->getData(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete")
     */
    public function delete(Request $request, DeleteInterviewerConfirmAction $confirmAction, Interviewer $interviewer): Response
    {
        $data = $confirmAction
            ->setSubject($interviewer)
            ->controller($request,
                $this->generateUrl('admin_interviewers_list'),
                $this->generateUrl('admin_interviewers_view', ["id" => $interviewer->getId()])
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/interviewer/delete.html.twig", $data);
    }
}