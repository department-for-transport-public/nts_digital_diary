<?php


namespace App\EventSubscriber\FormWizard;


use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\FormWizard\FormWizardStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function \str_starts_with;

/**
 * Clear all session-based wizard data from the session, except for the current wizard (if it is a wizard)
 *
 * Class FormWizardCleanupSubscriber
 * @package App\EventSubscriber
 */
class CleanupSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private LoggerInterface $log;

    public function __construct(RequestStack $requestStack, LoggerInterface $log)
    {
        $this->requestStack = $requestStack;
        $this->log = $log;
    }

    public function kernelControllerEvent(ControllerEvent $event)
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
            $this->log->notice("[FormWizard] Cleanup: Ignoring controller $controllerClass");
            return;
        }

        $this->log->notice("[FormWizard] Cleanup: Running on controller $controllerClass");

        if ($controller instanceof AbstractSessionStateFormWizardController) {
            // in wizard
            $this->cleanUp($controller->getSessionKey());
        } else {
            // not in wizard
            $this->cleanUp();
        }
    }

    private function cleanUp($exclude = null)
    {
        if ($exclude) {
            $this->log->notice("[FormWizard] Cleanup: Exclude '$exclude' from search");
        }

        $session = $this->requestStack->getSession();
        $sessionVars = $session->all();
        foreach ($sessionVars as $key => $var) {
            if ($var instanceof FormWizardStateInterface && $key !== $exclude) {
                $this->log->notice("[FormWizard] Cleanup: Removing wizard session var '$key'");
                $session->remove($key);
            }
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