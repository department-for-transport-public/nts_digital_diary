<?php


namespace App\EventSubscriber;


use App\Features;
use App\Utility\CspInlineScriptHelper;
use App\Utility\RecaptchaHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecureHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected CspInlineScriptHelper $cspInlineScriptHelper,
        protected RecaptchaHelper $recaptchaHelper,
    ) {}

    public function kernelResponseEvent(ResponseEvent $event): void
    {
        $cspScriptSrc = "'self' {$this->nonce('js-detect')}";
        $inlineSessionStyle = "'sha256-biLFinpqYMtWHmXfkA1BPeCY0/fNt46SAZ+BBk5YUog='";
        $cspStyleSrc = "'self' {$this->nonce('env-label')}";
        $cspFrameSrc = "player.vimeo.com";

        if ($this->recaptchaHelper->isRecaptchaUsed()) {
            $cspFrameSrc .= " www.google.com/recaptcha/";
            $cspScriptSrc .= " www.google.com/recaptcha/ www.gstatic.com/recaptcha/";
        }

        $event->getResponse()->headers->add([
            'X-Frame-Options' => 'sameorigin',
            'X-Content-Type-Options' => 'nosniff',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; script-src $cspScriptSrc; style-src $cspStyleSrc; frame-src $cspFrameSrc",
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);

        if (Features::isEnabled(Features::SMARTLOOK_SESSION_RECORDING))
        {
            $event->getResponse()->headers->add([
                'Content-Security-Policy' => "default-src 'self'; style-src $cspStyleSrc; frame-src $cspFrameSrc; script-src $cspScriptSrc {$this->nonce('smartlook')} 'unsafe-eval' https://*.smartlook.com https://*.smartlook.cloud; connect-src 'self' https://*.smartlook.com https://*.smartlook.cloud; child-src 'self' blob:; worker-src 'self' blob:",
            ]);
        }
    }

    protected function nonce(string $context): string
    {
        return "'nonce-{$this->cspInlineScriptHelper->getNonce($context)}'";
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'kernelResponseEvent',
        ];
    }
}