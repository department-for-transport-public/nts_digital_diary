<?php

namespace App\Utility\Metrics;

use App\Controller\FormWizard\AbstractFormWizardController;
use App\Entity\OtpUser;
use App\Entity\User;
use App\Entity\Utility\MetricsLog;
use App\Security\GoogleIap\IapUser;
use App\Security\Voter\Interviewer\InterviewerTrainingVoter;
use App\Utility\Metrics\Events\EventInterface;
use App\Utility\Metrics\Events\FormWizardEventInterface;
use App\Utility\Metrics\Events\UserSerialProviderEventInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Throwable;

class MetricsHelper
{
    public const GET_SERIAL_METHOD_ARGS = ["padAddressPart" => true, "spacesBetweenParts" => false];
    protected array $ignoredEventClasses = [];

    public const VIEWPORT_DETAILS_SESSION_KEY = 'viewport-details';

    public function __construct(protected Security $security, protected EntityManagerInterface $defaultEntityManager, protected EntityManagerInterface $metricsEntityManager, protected RequestStack $requestStack, protected string $appEnvironment){}

    public function ignoreEvents(array $classes): void
    {
        $this->ignoredEventClasses = $classes;
    }

    public function log(EventInterface $event): void
    {
        if ($this->security->isGranted(InterviewerTrainingVoter::IS_INTERVIEWER_TRAINING)) {
            return;
        }

        if (in_array(get_class($event), $this->ignoredEventClasses)) {
            return;
        }

        if ('unknown' === ($userSerial = $this->getUserSerial()) && $this->appEnvironment !== 'test') {
            // must be fixtures outside of tests (e.g. screenshots)
            return;
        }

        $logEntry = (new MetricsLog())
            ->setCreatedAt(new DateTimeImmutable())
            ->setEvent($event->getName())
            ->setMetadata($this->addDefaultMetadata($event))
            ->setDiarySerial($event->getDiarySerial())
            ->setUserSerial($userSerial)
        ;
        $this->metricsEntityManager->persist($logEntry);
        $this->metricsEntityManager->flush();
    }

    protected function addDefaultMetadata(EventInterface $event): array
    {
        $defaultMeta = [];
        try {
            if ($viewportDetails = $this->requestStack->getSession()->get(self::VIEWPORT_DETAILS_SESSION_KEY)) {
                if (is_array($viewportDetails)) {
                    $defaultMeta['browser_viewport'] = $viewportDetails;
                }
            }
        } catch (Throwable $e)
        {}

        return array_merge($event->getMetadata(), $defaultMeta);
    }

    protected function getUserSerial(): string
    {
        $token = $this->security->getToken();
        if ($token instanceof SwitchUserToken) {
            $token = $token->getOriginalToken();
        }
        $user = $token?->getUser();
        if (!$user) {
            return 'unknown';
        }

        if (method_exists($user, 'getId')) {
            $user = $this->defaultEntityManager->find(get_class($user), $user->getId());
        }

        return match (get_class($user)) {
            User::class => $user->getInterviewer()
                ? ('int:' . $user->getInterviewer()->getSerialId())
                : ('dk:' . $user->getDiaryKeeper()->getSerialNumber(...self::GET_SERIAL_METHOD_ARGS)),
            OtpUser::class => 'ob:' . $user->getUserIdentifier(),
            IapUser::class => 'adm:' . $user->getUserIdentifier(),
            default => 'unknown'
        };
    }
}