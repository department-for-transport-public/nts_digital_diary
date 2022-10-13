<?php

namespace App\Twig;

use App\Utility\PostcodeHelper;
use App\Utility\RegistrationMarkHelper;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FormatExtension extends AbstractExtension
{
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
        ];
    }

    public function formatRegMark($regMark): ?string
    {
        return (new RegistrationMarkHelper($regMark))->getFormattedRegistrationMark();
    }

}