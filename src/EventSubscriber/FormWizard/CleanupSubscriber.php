<?php


namespace App\EventSubscriber\FormWizard;


use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\FormWizard\FormWizardManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Clear all session-based wizard data from the session, except for the current wizard (if it is a wizard)
 *
 * Class FormWizardCleanupSubscriber
 * @package App\EventSubscriber
 */
class CleanupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected LoggerInterface $log,
        protected FormWizardManager $formWizardManager,
        protected RequestStack $requestStack,
    ) {}

    public function kernelControllerEvent(ControllerEvent $event): void
    {
        $controllerBlacklist = [
            ProfilerController::class,
        ];

        if (!is_array($event->getController())) return;
        $controller = $event->getController()[0];
        $controllerClass = get_class($controller);
        if (
            in_array($controllerClass, $controllerBlacklist)
            || !str_starts_with($controllerClass, "App\\Controller\\")
        ) {
            $this->log->debug("[FormWizard] Cleanup: Ignoring controller $controllerClass");
            return;
        }

        $this->log->debug("[FormWizard] Cleanup: Running on controller $controllerClass");

        if ($controller instanceof AbstractSessionStateFormWizardController) {
            // In wizard: remove other wizard's state
            $this->formWizardManager->cleanUp($controller->getSessionKey());
        } else {
            // Not in wizard: remove all wizard's state
            $this->formWizardManager->cleanUp();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['kernelControllerEvent', 256],
            ],
        ];
    }
}