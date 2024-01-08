<?php

namespace App\Security\Voter;

use App\Security\Voter\Interviewer\InterviewerTrainingVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class VideoJsEventsVoter extends Voter
{
    public const REPORT_VIDEO_JS_EVENTS = 'REPORT_VIDEO_JS_EVENTS';

    public function __construct(protected Security $security) {}

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::REPORT_VIDEO_JS_EVENTS;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return ! $this->security->isGranted(InterviewerTrainingVoter::IS_INTERVIEWER_TRAINING);
    }
}