<?php

namespace App\Twig;

use App\Utility\PostcodeHelper;
use App\Utility\RegistrationMarkHelper;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FormatExtension extends AbstractExtension
{
    public function __construct(protected TranslatorInterface $translator) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_bool', function($bool) {
                return new TranslatableMessage('boolean.' . ($bool ? 'true' : 'false'), [], 'messages');
            }),
            new TwigFilter('format_reg_mark', [$this, 'formatRegMark']),
            new TwigFilter('format_potential_postcode', fn(?string $a) => PostcodeHelper::formatIfPostcode($a, true)),
            new TwigFilter('remove_email_namespace_prefix', function($username) {
                preg_match('/^(?:[^:"]+:)?(?<email>.*)$/', $username, $matches);
                return $matches['email'] ?? 'unknown';
            }),
            new TwigFilter('format_otp', function(string $otp) {
                return join(' ', str_split($otp, 4));
            }),
            new TwigFilter('format_feedback_user_serial', [$this, 'formatFeedbackUserSerial'])
        ];
    }

    public function formatFeedbackUserSerial(?string $serial): string | TranslatableMessage
    {

        $split = explode(":", $serial ?? "");
        if (count($split) === 2) {
            if (in_array($split[0], ['dk', 'int'])) {
                return $this->translator->trans("feedback.view.details.user-serial.${split[0]}", ['serial' => $split[1]], 'admin');
            }
        }

        return $serial ?? 'n/a';
    }

    public function formatRegMark($regMark): ?string
    {
        return (new RegistrationMarkHelper($regMark))->getFormattedRegistrationMark();
    }

}