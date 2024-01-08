<?php


namespace App\EventSubscriber\Security;


use App\Features;
use App\Utility\Security\CspInlineScriptHelper;
use App\Utility\Security\RecaptchaHelper;
use App\Utility\VimeoHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecureHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected CspInlineScriptHelper $cspInlineScriptHelper,
        protected RecaptchaHelper $recaptchaHelper,
        protected VimeoHelper $vimeoHelper,
    ) {}

    public function kernelResponseEvent(ResponseEvent $event): void
    {
        $cspScriptSrc = "'self' {$this->nonce('js-detect')}";
        $inlineSessionStyle = "'sha256-biLFinpqYMtWHmXfkA1BPeCY0/fNt46SAZ+BBk5YUog='";
        $cspStyleSrc = "'self' {$this->nonce('env-label')}";
        $cspFrameSrc = "'self'";
        $cspImgSrc = "'self'";

        if ($this->recaptchaHelper->isRecaptchaUsed()) {
            $cspFrameSrc .= " www.google.com/recaptcha/";
            $cspScriptSrc .= " www.google.com/recaptcha/ www.gstatic.com/recaptcha/";
        }

        if ($this->vimeoHelper->isVimeoUsed()) {
            $cspFrameSrc .= " player.vimeo.com";
            $cspScriptSrc .= " player.vimeo.com";
            $cspImgSrc .= " i.vimeocdn.com";
        }

        $event->getResponse()->headers->add([
            'X-Frame-Options' => 'sameorigin',
            'X-Content-Type-Options' => 'nosniff',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; script-src $cspScriptSrc; style-src $cspStyleSrc; frame-src $cspFrameSrc; img-src $cspImgSrc",
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