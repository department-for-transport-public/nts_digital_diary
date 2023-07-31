<?php

namespace App\Controller\FormWizard;

use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\PropertyMerger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractSessionStateFormWizardController extends AbstractFormWizardController
{
    protected PropertyMerger $propertyMerger;

    public function getSessionKey(): string
    {
        return "wizard." . get_class($this);
    }

    protected SessionInterface $session;

    public function __construct(FormWizardManager $formWizardManager, RequestStack $requestStack, PropertyMerger $propertyMerger)
    {
        parent::__construct($formWizardManager);
        $this->session = $requestStack->getSession();
        $this->propertyMerger = $propertyMerger;
    }

    protected function setState(FormWizardStateInterface $state): void
    {
        $this->session->set($this->getSessionKey(), $state);
    }

    protected function cleanUp(): void
    {
        $this->session->remove($this->getSessionKey());
    }
}
