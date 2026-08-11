<?php

namespace App\EventSubscriber\Security;

use App\Features;
use App\Utility\Security\RecaptchaHelper;
use App\Utility\VimeoHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecureHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected RecaptchaHelper $recaptchaHelper,
        protected VimeoHelper     $vimeoHelper,
    ) {}

    // NOTES:
    // - If you dump() anywhere, then the CSP header gets removed by WebDebugToolbarListener.php (at ~line 99)!
    // - Mentions of unsafe-inline in dev mode probably originate from ContentSecurityPolicyHandler.php (again
    //   part of the web-profiler-bundle)
    public function kernelResponseEvent(ResponseEvent $event): void
    {
        $headers = $event->getResponse()->headers;
        $cspHeader = $headers->get('Content-Security-Policy');

        if ($cspHeader === null) {
            // If there's no CSP header, no point trying to add things to it!
            // N.B. this happens on error pages, for example
            return;
        }

        $cspRules = [];

        // Extract CSP rules and index by rule name
        foreach(array_map(trim(...), explode(';', $cspHeader)) as $rule) {
            $name = explode(' ', $rule)[0];
            $cspRules[$name] = $rule;
        }

        $addEntry = function(string $name, string $entry) use (&$cspRules) {
            if (!isset($cspRules[$name])) {
                $cspRules[$name] = $name;
            }

            $cspRules[$name] .= " $entry";
        };

        if ($this->recaptchaHelper->isRecaptchaUsed()) {
            $addEntry('frame-src', 'www.google.com/recaptcha/');
            $addEntry('script-src', 'www.google.com/recaptcha/');
            $addEntry('script-src', 'www.gstatic.com/recaptcha/');
        }

        if ($this->vimeoHelper->isVimeoUsed()) {
            $addEntry('frame-src', 'player.vimeo.com');
            $addEntry('script-src', 'player.vimeo.com');
            $addEntry('img-src', 'i.vimeocdn.com');
        }

        if (Features::isEnabled(Features::SMARTLOOK_SESSION_RECORDING)) {
            $addEntry('connect-src', 'https://*.smartlook.com');
            $addEntry('connect-src', 'https://*.smartlook.cloud');
            $addEntry('script-src', 'https://*.smartlook.com');
            $addEntry('script-src', 'https://*.smartlook.cloud');
        }

        // Per-response CSP additions, set as a request attribute (see
        // DataExportController). Deliberately NOT a session flash: reading the
        // flash bag would start the session on every response (incl. 404s),
        // which is a PdoSessionHandler DB hit. Request-scoped, so it won't
        // survive a redirect — fine for the current set-and-render flow.
        foreach ($event->getRequest()->attributes->get('csp:add', []) as $cspRule) {
            $addEntry($cspRule[0], $cspRule[1]);
        }

        // If we want the CSP rules to comprise only authorised sources, we need to set
        // e.g. style-src: 'none'

        // However, if we then use csp_nonce('style') to add nonces, nelmio generates
        // a header that looks like so:
        // style-src: 'none' 'nonce-abc123'

        // Browsers moan about this, and so here we strip the 'none' if there are other
        // entries

        // (So if csp_nonce() not called, we end up with style-src: 'none', and if there
        //  are calls, we end up with style-src: 'nonce-abc123' 'nonce-def234')

        foreach($cspRules as $idx => $cspRule) {
            $cspRuleParts = explode(' ', $cspRule);

            $cspRuleName = $cspRuleParts[0] ?? null;
            $cspRuleEntries = array_slice($cspRuleParts, 1);

            // N.B. Not exhaustive - amend as needed
            if (in_array($cspRuleName, [
                'child-src',
                'connect-src',
                'font-src',
                'img-src',
                'media-src',
                'script-src',
                'style-src',
            ])) {
                $hasMultipleEntries = count($cspRuleEntries) > 1;
                $cspRuleEntries = array_filter($cspRuleEntries, fn($entry) =>
                    !$hasMultipleEntries ||
                    !in_array(strtolower($entry), ["'none'", "none", '"none"'])
                );

                $cspRules[$idx] = join(' ', array_merge([$cspRuleName], $cspRuleEntries));
            }
        }

        $headers->set('Content-Security-Policy', join('; ', $cspRules));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['kernelResponseEvent', -128],
        ];
    }
}
