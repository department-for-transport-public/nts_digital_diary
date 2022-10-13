<?php

namespace App\Form\Auth;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EmailType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ForgottenPasswordType extends AbstractType
{
    public RateLimiterFactory $forgottenPasswordEmailLimiter;
    public RateLimiterFactory $forgottenPasswordIpLimiter;
    public RequestStack $requestStack;

    public function __construct(RateLimiterFactory $forgottenPasswordEmailLimiter, RateLimiterFactory $forgottenPasswordIpLimiter, RequestStack $requestStack)
    {
        $this->forgottenPasswordEmailLimiter = $forgottenPasswordEmailLimiter;
        $this->forgottenPasswordIpLimiter = $forgottenPasswordIpLimiter;
        $this->requestStack = $requestStack;
    }

    public function checkRateLimit(?string $emailAddress, ExecutionContextInterface $context)
    {
        if (!$emailAddress || $context->getViolations()->count() > 0) {
            return;
        }

        $limiters = [
            $this->forgottenPasswordIpLimiter->create($this->requestStack->getCurrentRequest()->getClientIp()),
            $this->forgottenPasswordEmailLimiter->create($emailAddress),
        ];

        array_map(function (LimiterInterface $limiter) use ($context) {
            $rateLimit = $limiter->consume(1);

            if ($rateLimit->isAccepted() || $context->getViolations()->count() > 0) {
                return;
            }

            $context
                ->addViolation('forgotten-password.too-many-submissions', [
                    'minutes' => $rateLimit->getRetryAfter()->diff(new \DateTime())->i,
                ]);
        }, $limiters);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emailAddress', EmailType::class, [
                'label' => 'forgotten-password.email.label',
                'help' => 'forgotten-password.email.help',
                'attr' => [
                    'class' => 'govuk-input--width-10',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'forgotten-password.email.not-blank',
                    ]),
                    new Email(),
                    new Callback([$this, 'checkRateLimit']),
                ],
            ])
            ->add('button_group', ButtonGroupType::class);

        $builder
            ->get('button_group')
            ->add('reset_password', ButtonType::class, [
                'label' => 'forgotten-password.reset-password',
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'actions.cancel',
                'translation_domain' => 'messages',
                'href' => $options['cancel_link_href'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('translation_domain', 'auth');
        $resolver->setRequired('cancel_link_href');
    }
}