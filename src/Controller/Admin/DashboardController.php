<?php


namespace App\Controller\Admin;


use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route(name="dashboard")
     */
    public function index(): Response
    {
        return new RedirectResponse($this->generateUrl('admin_interviewers_list'));
    }
}