<?php

namespace App\Controller\Admin\Feedback;

use App\ListPage\AbstractListPage;
use App\ListPage\Admin\FeedbackAssignmentListPage;
use App\ListPage\Admin\FeedbackViewListPage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/feedback", name: "feedback_")]
class ListPagesController extends AbstractController
{
    #[Route("/assignment", name: "assignment_dashboard")]
    #[Security("is_granted('ADMIN_FEEDBACK_ASSIGN')")]
    public function assignmentDashboard(FeedbackAssignmentListPage $listPage, Request $request): Response
    {
        return $this->renderListPage($listPage, $request, "admin/feedback/assignment_list.html.twig");
    }

    #[Route("/dashboard", name: "view_dashboard")]
    #[Security("is_granted('ADMIN_FEEDBACK_VIEW')")]
    public function viewDashboard(FeedbackViewListPage $listPage, Request $request): Response
    {
        return $this->renderListPage($listPage, $request, "admin/feedback/view_list.html.twig");
    }

    protected function renderListPage(AbstractListPage $listPage, Request $request, string $template): Response
    {
        $listPage->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render($template, [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }
}