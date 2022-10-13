<?php

namespace App\Utility\ConfirmAction;

use App\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractConfirmAction implements ConfirmActionInterface
{
    protected $subject;
    protected FormFactoryInterface $formFactory;
    protected FlashBagInterface $flashBag;
    protected TranslatorInterface $translator;
    protected array $extraViewData;
    protected string $confirmedActionUrl;
    protected string $cancelledActionUrl;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack)
    {
        $this->formFactory = $formFactory;
        $this->flashBag = $requestStack->getSession()->getFlashBag();
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getConfirmedBanner(): NotificationBanner
    {
        return new NotificationBanner(
            new TranslatableMessage('notification.success'),
            "{$this->getTranslationKeyPrefix()}.confirmed-notification.heading",
            "{$this->getTranslationKeyPrefix()}.confirmed-notification.content",
            ['style' => NotificationBanner::STYLE_SUCCESS],
            $this->getTranslationParameters(),
            $this->getTranslationDomain()
        );
    }

    public function getCancelledBanner(): NotificationBanner
    {
        return new NotificationBanner(
            new TranslatableMessage('notification.important'),
            "{$this->getTranslationKeyPrefix()}.cancelled-notification.heading",
            "{$this->getTranslationKeyPrefix()}.cancelled-notification.content",
            [],
            $this->getTranslationParameters(),
            $this->getTranslationDomain()
        );
    }

    public function getFormOptions(): array
    {
        return [
            'cancel_link_options' => [
                'href' => $this->cancelledActionUrl,
            ],
            'translation_domain' => $this->getTranslationDomain(),
        ];
    }

    public function getTranslationDomain(): ?string
    {
        return null;
    }

    public function getTranslationParameters(): array
    {
        return [];
    }

    public function getFormClass(): string
    {
        return ConfirmActionType::class;
    }

    public function getForm(): FormInterface
    {
        return $this->formFactory->create($this->getFormClass(), null, $this->getFormOptions());
    }

    /**
     * @param Request $request
     * @param string $confirmedActionUrl
     * @param null|string $cancelledActionUrl if omitted, will use the same url as for confirmed
     * @return RedirectResponse | array
     */
    public function controller(Request $request, string $confirmedActionUrl, string $cancelledActionUrl = null)
    {
        $this->confirmedActionUrl = $confirmedActionUrl;
        $this->cancelledActionUrl = $cancelledActionUrl ?? $confirmedActionUrl;

        $form = $this->getForm();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $confirm = $form->get('button_group')->get('confirm');
            if ($confirm instanceof SubmitButton && $confirm->isClicked()) {
                if ($form->isValid()) {
                    $this->doConfirmedAction($form->getData());
                    $this->flashBag->add(NotificationBanner::FLASH_BAG_TYPE, $this->getConfirmedBanner());
                    return new RedirectResponse($this->confirmedActionUrl);
                }
            } else {
                $this->flashBag->add(NotificationBanner::FLASH_BAG_TYPE, $this->getCancelledBanner());
                return new RedirectResponse($this->cancelledActionUrl);
            }
        }

        return [
            'translation_domain' => $this->getTranslationDomain(),
            'translation_prefix' => $this->getTranslationKeyPrefix(),
            'translation_parameters' => $this->getTranslationParameters(),
            'subject' => $this->getSubject(),
            'form' => $form->createView(),
        ] + $this->getExtraViewData();
    }

    public function getExtraViewData(): array
    {
        return $this->extraViewData ?? [];
    }

    public function setExtraViewData(array $extraViewData): self
    {
        $this->extraViewData = $extraViewData;
        return $this;
    }
}