<?php

namespace App\Form\Auth;

use App\Entity\User;
use App\Validator\Constraints\PasswordComplexity;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Ghost\GovUkFrontendBundle\Form\Type\PasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $passwordOptions = [
            'attr' => [
                'class' => 'govuk-input--width-10',
            ],
        ];

        $constraints = [
            new NotBlank([
                'message' => 'setup.password.not-blank',
            ]),
        ];

        $builder
            ->add('password1', PasswordType::class, array_merge($passwordOptions, [
                'label' => 'setup.password.password-1.label',
                'help' => 'setup.password.password-1.help',
                'constraints' => array_merge($constraints, [
                    new PasswordComplexity(),
                ]),
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-15',
                    'autocomplete' => 'new-password'
                ],
            ]))
            ->add('password2', PasswordType::class, array_merge($passwordOptions, [
                'label' => 'setup.password.password-2.label',
                'help' => 'setup.password.password-2.help',
                'constraints' => $constraints,
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-15',
                    'autocomplete' => 'new-password'
                ],
            ]))
            ->add('button_group', ButtonGroupType::class)
            ->setDataMapper(new ChangePasswordDataMapper());

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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', User::class);
        $resolver->setDefault('translation_domain', 'auth');

        $resolver->setDefault('constraints', [
            new Callback(function(User $user, ExecutionContextInterface $context) {
                /** @var FormInterface $form */
                $form = $context->getRoot();
                $password1 = $form->get('password1')->getData();
                $password2 = $form->get('password2')->getData();

                if ($password1 !== null && $password2 !== null && $password1 !== $password2) {
                    $context
                        ->buildViolation('setup.password.must-match')
                        ->atPath('password2')
                        ->addViolation();
                }
            }),
        ]);

        $resolver->setDefault('cancel_link_href', null);
        $resolver->setRequired([
            'save_label'
        ]);
    }
}