<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DashboardController extends AbstractController
{
    /**
     * @Route(name="dashboard")
     */
    public function index(Security $security): Response
    {
        $redirectMap = [
            'ROLE_INTERVIEWER' => 'admin_interviewers_list',
            'ROLE_FEEDBACK_ASSIGNER' => 'admin_feedback_assignment_dashboard',
            'ROLE_FEEDBACK_VIEWER' => 'admin_feedback_view_dashboard',
            'ROLE_SAMPLE_IMPORTER' => 'admin_sampleimport_index',
        ];

        foreach($redirectMap as $role => $route) {
            if ($security->isGranted($role)) {
                return new RedirectResponse($this->generateUrl($route));
            }
        }

        // Should be impossible to get here...
        throw new AccessDeniedHttpException();
    }
}