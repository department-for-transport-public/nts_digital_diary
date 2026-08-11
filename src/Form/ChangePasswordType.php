<?php

namespace App\Form;

use App\Entity\User;
use App\Validator\Constraints\PasswordComplexity;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Ghost\GovUkFrontendBundle\Form\Type\PasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['require_current_password']) {
            $builder
                ->add('currentPassword', PasswordType::class, [
                    'mapped' => false,
                    'label' => 'password.current.label',
                    'help' => 'password.current.help',
                    'translation_domain' => 'messages',
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => [
                        'class' => 'govuk-input--width-15',
                        'autocomplete' => 'current-password',
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'password.current.not-blank',
                        ]),
                        new UserPassword([
                            'message' => 'password.current.invalid',
                        ]),
                    ],
                ]);
        }

        $constraints = [
            new NotBlank([
                'message' => 'password.not-blank',
            ]),
        ];

        $builder
            ->add('password1', PasswordType::class, [
                'mapped' => false,
                'label' => 'password.choose.label',
                'help' => 'password.choose.help',
                'translation_domain' => 'messages',
                'constraints' => array_merge($constraints, [
                    new PasswordComplexity(),
                ]),
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-15',
                    'autocomplete' => 'new-password',
                ],
            ])
            ->add('password2', PasswordType::class, [
                'mapped' => false,
                'label' => 'password.repeat.label',
                'help' => 'password.repeat.help',
                'translation_domain' => 'messages',
                'constraints' => $constraints,
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-15',
                    'autocomplete' => 'new-password',
                ],
            ]);

        $builder
            ->add('button_group', ButtonGroupType::class);

        $buttonGroup = $builder->get('button_group');

        $buttonGroup
            ->add('save', ButtonType::class, [
                'label' => $options['save_label'],
            ]);

        if ($options['cancel_link_href']) {
            $buttonGroup
                ->add('cancel', LinkType::class, [
                    'label' => 'actions.cancel',
                    'translation_domain' => 'messages',
                    'href' => $options['cancel_link_href'],
                ]);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
            $form = $event->getForm();
            if (!$form->isValid()) {
                return;
            }
            $user = $event->getData();
            $user->setPlainPassword($form->get('password1')->getData());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', User::class);
        $resolver->setDefault('translation_domain', 'messages');

        $resolver->setDefault('constraints', [
            new Callback(function($user, ExecutionContextInterface $context): void {
                /** @var FormInterface $form */
                $form = $context->getRoot();
                $password1 = $form->get('password1')->getData();
                $password2 = $form->get('password2')->getData();

                if ($password1 !== null && $password2 !== null && $password1 !== $password2) {
                    $context
                        ->buildViolation('password.must-match')
                        ->atPath('password2')
                        ->addViolation();
                }
            }),
        ]);

        $resolver->setDefault('require_current_password', false);
        $resolver->setDefault('cancel_link_href', null);
        $resolver->setRequired('save_label');
    }
}
