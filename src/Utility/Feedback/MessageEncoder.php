<?php

namespace App\Utility\Feedback;

use App\Entity\Feedback\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MessageEncoder extends AbstractExtension
{
    public function __construct(
        protected LoggerInterface $logger,
        protected RequestStack $requestStack,
        protected string $secret,
        protected Security $security,
        protected UserRepository $userRepository,
    ) {}

    protected const ALGORITHM = 'aes-256-cbc';
    protected const ENCODED_PROPERTIES = ['page', 'emailAddress', 'currentUserSerial', 'originalUserSerial'];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encodeFeedback', [$this, 'encodeFeedback']),
        ];
    }

    protected function getNewMessage(bool $withPath = false): Message
    {
        $feedback = new Message();

        $impersonator = null;
        $user = null;
        $token = $this->security->getToken();

        if ($withPath) {
            $feedback->setPage($this->requestStack->getCurrentRequest()?->server->get('REQUEST_URI'));
        }

        if ($token) {
            $user = $token->getUser();

            if ($token instanceof SwitchUserToken) {
                // This user is often missing information, so we reload
                $impersonatorIdentifier = $token->getOriginalToken()->getUser()->getUserIdentifier();
                $impersonator = $this->userRepository->loadUserForSerialInformation($impersonatorIdentifier);
            }
        }

        $getUserSerial = function(?UserInterface $user) use (&$fe): ?string {
            if ($user instanceof User) {
                $diaryKeeper = $user->getDiaryKeeper();
                $interviewer = $user->getInterviewer();

                if ($diaryKeeper) {
                    $household = $diaryKeeper->getHousehold();
                    return str_replace(" ", "", "dk:{$household->getSerialNumber($diaryKeeper->getNumber())}");
                } else if ($interviewer) {
                    return "int:{$interviewer->getSerialId()}";
                }
            }
            return null;
        };

        $feedback
            ->setCurrentUserSerial($getUserSerial($user))
            ->setOriginalUserSerial($getUserSerial($impersonator))
            ->setEmailAddress(($impersonator ?? $user)?->getUserIdentifier());
        ;

        return $feedback;
    }

    public function encodeFeedback(): string
    {
        $feedback = $this->getNewMessage(true);

        $propertyAccessor = new PropertyAccessor();
        $data = array_combine(
            self::ENCODED_PROPERTIES,
            array_map(fn($property) => $propertyAccessor->getValue($feedback, $property), self::ENCODED_PROPERTIES)
        );

        $encoded = json_encode($data);

        return $this->encryptFeedback($encoded);
    }

    protected function encryptFeedback(string $encoded): string
    {
        $iv_length = openssl_cipher_iv_length(self::ALGORITHM);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encodedIv = bin2hex($iv);

        // Changing the IV leads to data that is still decryptable, although with a corrupted / changed first block:
        // https://en.wikipedia.org/wiki/Block_cipher_mode_of_operation#Cipher_block_chaining_(CBC)

        // Hence we append the IV to the *end* of the encrypted data, so that upon decoding, we can check that it
        // hasn't been changed (and by putting it at the end, that "original IV" is beyond the reach of being
        // corrupted by changes to the decrypting IV).

        $data = "{$encoded}:{$encodedIv}";

        $encrypted = openssl_encrypt($data, self::ALGORITHM, $this->secret, 0, $iv);

        // https://security.stackexchange.com/a/17046
        // IV doesn't need to be kept secret, but must be random and unique.

        return "{$encrypted}:{$encodedIv}";

    }

    public function decodeFeedback(string $encodedFeedback): ?Message
    {
        $encoded = $this->decryptFeedback($encodedFeedback);

        if ($encoded === null) {
            // Already logged in decryptFeedback
            return null;
        }

        try {
            $data = json_decode($encoded, true);

            if ($data === null) {
                $this->logger->warning('decodeFeedback(): JSON decode failed (null)');
                return null;
            }

            $message = new Message();
            $propertyAccessor = new PropertyAccessor();

            try {
                array_walk($data, fn($value, $property) => $propertyAccessor->setValue($message, $property, $value));
            }
            catch(NoSuchPropertyException) {
                $this->logger->warning('decodeFeedback(): JSON decode failed (corrupt)');
                return null;
            }

            return $message;
        }
        catch(\ValueError) {
            $this->logger->warning('decodeFeedback(): JSON decode failed (depth)');
            return null;
        }
    }

    // Separated out for ease of testing...
    protected function decryptFeedback(string $encodedPayload): ?string
    {
        if (substr_count($encodedPayload, ':') !== 1) {
            $this->logger->warning('decodeFeedback(): String did not include exactly one colon');
            return null;
        }

        [$encrypted, $encodedIv] = explode(':', $encodedPayload);

        if (strlen($encodedIv) === 0 || strlen($encrypted) === 0) {
            $this->logger->warning('decodeFeedback(): Some inputs were zero-length');
            return null;
        }

        $iv = @hex2bin($encodedIv);
        $iv_length = openssl_cipher_iv_length(self::ALGORITHM);

        if ($iv === false || \mb_strlen($iv, '8bit') !== $iv_length) {
            $this->logger->warning('decodeFeedback(): Provided IV was the wrong length');
            return null;
        }

        $data = openssl_decrypt($encrypted, self::ALGORITHM, $this->secret, 0, $iv);

        if ($data === false) {
            $this->logger->warning('decodeFeedback(): Failed to decrypt provided string');
            return null;
        }

        $colonPos = strrpos($data, ':');

        if ($colonPos === false) {
            $this->logger->warning('decodeFeedback(): Decrypted string did not contain a colon');
            return null;
        }

        $encoded = substr($data, 0, $colonPos);
        $originalIv = substr($data, $colonPos + 1);

        if ($originalIv !== $encodedIv) {
            $this->logger->warning('decodeFeedback(): IV has been changed');
            return null;
        }

        return $encoded;
    }

    public function decodeFeedbackFromRequest(Request $request, string $feedbackField = 'info'): Message
    {
        $infoString = $request->query->get($feedbackField, "");

        // as a fallback (in case the decoding didn't work), get a fresh Message without page (no longer valid)
        return $this->decodeFeedback($infoString) ?: $this->getNewMessage();
    }
}