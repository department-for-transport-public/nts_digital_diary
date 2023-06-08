<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EmailType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['is_logged_in']) {
            $builder->add('email', EmailType::class, [
                'help' => "feedback.email.help",
                'label' => "feedback.email.label",
                'constraints' => [
                    new Email(['message' => 'feedback.email.not-valid']),
                ],
            ]);
        }

        $builder->add('comments', TextareaType::class, [
            'label' => 'feedback.comments.label',
            'constraints' => [
                new NotBlank(['message' => 'feedback.comments.not-blank']),
            ],
        ]);

        if (!$options['is_logged_in']) {
            $builder->add('recaptcha', RecaptchaType::class, [
                'attr' => [
                    'class' => 'govuk-!-margin-bottom-6',
                ],
            ]);
        }

        $builder->add('submit', ButtonType::class, [
            'type' => 'submit',
            'label' => "feedback.send.label",
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('is_logged_in');
    }
}
