<?php

namespace App\Form\Auth;

use App\Repository\UserRepository;
use App\Validator\Constraints\ChangeUserEmailAddress;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EmailType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangeEmailType extends AbstractType
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emailAddress', EmailType::class, [
                'label' => 'change-email.email.label',
                'label_attr' => ['class' => "govuk-label--s"],
                'help' => 'change-email.email.help',
                'constraints' => [
                    new NotBlank(['message' => 'change-email.email.not-blank']),
                    new Email(),
                    new ChangeUserEmailAddress(['userId' => $options['user_id']]),
                ],
                'attr' => [
                    'class' => 'govuk-input--width-20',
                ],
            ])
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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('translation_domain', 'auth');
        $resolver->setDefault('cancel_link_href', null);
        $resolver->setRequired([
            'save_label',
            'user_id',
        ]);
    }
}