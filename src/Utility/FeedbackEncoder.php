<?php

namespace App\Utility;

use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeedbackEncoder extends AbstractExtension
{
    public function __construct(
        protected LoggerInterface $logger,
        protected RequestStack $requestStack,
        protected string $secret,
        protected Security $security,
        protected UserRepository $userRepository,
    ) {}

    protected const ALGORITHM = 'aes-256-cbc';

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encodeFeedback', [$this, 'encodeFeedback']),
        ];
    }

    public function encodeFeedback(): string
    {
        $token = $this->security->getToken();

        $impersonator = null;
        $user = null;

        $loggedInUser = null;


        if ($token) {
            $user = $token->getUser();

            if ($token instanceof SwitchUserToken) {
                // This user is often missing information, so we reload
                $impersonatorIdentifier = $token->getOriginalToken()->getUser()->getUserIdentifier();
                $impersonator = $this->userRepository->loadUserForSerialInformation($impersonatorIdentifier);

                $loggedInUser = $impersonator->getUserIdentifier();
            } else {
                $loggedInUser = $user->getUserIdentifier();
            }
        }

        $data = [
            'page' => 'Page: '.$this->requestStack->getMainRequest()->server->get('REQUEST_URI'),
            'time' => 'Request time: '.(new \DateTime())->format('c'),
            'is_logged_in' => ($loggedInUser !== null),
        ];

        if (!$loggedInUser) {
            $data['user'] = 'User: Not logged in';
        }

        $addUserSerials = function(User $user, string $keyPrefix = '', string $labelPrefix = '', bool $showUserIdentifier = false) use (&$data): void {
            $diaryKeeper = $user->getDiaryKeeper();
            $interviewer = $user->getInterviewer();

            $postfix = $showUserIdentifier ? " ({$user->getUserIdentifier()})" : "";

            if ($diaryKeeper) {
                $household = $diaryKeeper->getHousehold();
                $data[$keyPrefix."dk"] = $labelPrefix."DiaryKeeper: ".$household->getSerialNumber($diaryKeeper->getNumber()).$postfix;
            } else if ($interviewer) {
                $data[$keyPrefix."int"] = $labelPrefix."Interviewer: ".$interviewer->getSerialId().$postfix;
            }
        };

        if ($user instanceof User) {
            if ($impersonator instanceof User) {
                $impersonatorIsDiaryKeeper = $impersonator->getDiaryKeeper() !== null;

                $addUserSerials($impersonator, '', 'Logged in ', true);

                if ($impersonatorIsDiaryKeeper) {
                    $addUserSerials($user, 'imp_', 'Proxying for ');
                } else {
                    $addUserSerials($user, 'imp_', 'Impersonating ');
                }
            } else {
                $addUserSerials($user, '', 'Logged in ', true);
            }
        }

        // https://security.stackexchange.com/a/17046
        // IV doesn't need to be kept secret, but must be random and unique.

        $iv_length = openssl_cipher_iv_length(self::ALGORITHM);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encoded = json_encode($data);
        $encrypted = openssl_encrypt($encoded, self::ALGORITHM, $this->secret, 0, $iv);

        $encodedIv = bin2hex($iv);
        return "{$encrypted}:{$encodedIv}";
    }

    public function decodeFeedback(string $encodedFeedback): ?array
    {
        if (substr_count($encodedFeedback, ':') !== 1) {
            $this->logger->warning('decodeFeedback(): String did not include exactly one colon');
            return null;
        }

        [$encrypted, $encodedIv] = explode(':', $encodedFeedback);

        if (strlen($encodedIv) === 0 || strlen($encrypted) === 0) {
            $this->logger->warning('decodeFeedback(): Some inputs were zero-length');
            return null;
        }

        $iv = @hex2bin($encodedIv);
        $iv_length = openssl_cipher_iv_length(self::ALGORITHM);

        if ($iv === false || mb_strlen($iv, '8bit') !== $iv_length) {
            $this->logger->warning('decodeFeedback(): Provided IV was the wrong length');
            return null;
        }

        $encoded = openssl_decrypt($encrypted, self::ALGORITHM, $this->secret, 0, $iv);

        if ($encoded === false) {
            $this->logger->warning('decodeFeedback(): Failed to decrypt provided string');
            return null;
        }

        try {
            return json_decode($encoded, true);
        }
        catch(\ValueError) {
            $this->logger->warning('decodeFeedback(): JSON decode failed (depth)');
            return null;
        }
    }

    public function decodeFeedbackFromRequest(Request $request, string $feedbackField = 'info'): array
    {
        $infoString = $request->query->get($feedbackField);

        $info = [];
        if ($infoString) {
            $info = $this->decodeFeedback($infoString) ?? [];
        }

        return $info;
    }
}