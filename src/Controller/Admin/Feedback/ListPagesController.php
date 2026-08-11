<?php

namespace App\Controller\Admin\Feedback;

use App\ListPage\Admin\FeedbackAssignmentListPage;
use App\ListPage\Admin\FeedbackViewListPage;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/feedback", name: "feedback_")]
class ListPagesController extends AbstractController
{
    #[Route("/assignment", name: "assignment_dashboard")]
    #[IsGranted(attribute: 'ADMIN_FEEDBACK_ASSIGN')]
    public function assignmentDashboard(FeedbackAssignmentListPage $listPage, Request $request): Response
    {
        return $this->renderListPage($listPage, $request, "admin/feedback/assignment_list.html.twig");
    }

    #[Route("/dashboard", name: "view_dashboard")]
    #[Route("/dashboard/archive", name: "view_dashboard_archive")]
    #[IsGranted(attribute: 'ADMIN_FEEDBACK_VIEW')]
    public function viewDashboard(FeedbackViewListPage $listPage, Request $request): Response
    {
        $listPage->setIsArchive($isArchive = $request->get('_route') === "admin_feedback_view_dashboard_archive");
        return $this->renderListPage($listPage, $request, "admin/feedback/view_list.html.twig", [
            'isArchive' => $isArchive,
        ]);
    }

    protected function renderListPage(AbstractListPage $listPage, Request $request, string $template, array $additionalViewData = []): Response
    {
        $listPage->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render($template, array_merge($additionalViewData, [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
        ]));
    }
}
