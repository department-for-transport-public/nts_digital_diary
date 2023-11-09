<?php

namespace App\Form;

use App\Entity\Feedback\Message;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EmailType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class FeedbackType extends AbstractType
{
    public const INJECT_TRANSLATION_PARAMETERS = [
        'category' => 'category.value',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['is_logged_in']) {
            $builder->add('emailAddress', EmailType::class, [
                'help' => "feedback.form.email.help",
                'label' => "feedback.form.email.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [
                    new Email(['message' => 'feedback.form.email.not-valid']),
                    new NotBlank(['message' => 'feedback.form.email.not-blank'], groups: ['feedback.category.support']),
                ],
            ]);
        }

        $builder->add('message', TextareaType::class, [
            'label' => 'feedback.form.comments.label',
            'help' => 'feedback.form.comments.help',
            'label_attr' => ['class' => 'govuk-label--s'],
            'constraints' => [
                new NotBlank(['message' => 'feedback.form.comments.not-blank']),
            ],
        ]);

        if (!$options['is_logged_in']) {
            $builder->add('recaptcha', RecaptchaType::class, [
                'mapped' => false,
                'attr' => [
                    'class' => 'govuk-!-margin-bottom-6',
                ],
            ]);
        }

        $builder->add('submit', ButtonType::class, [
            'type' => 'submit',
            'label' => "feedback.form.send.label",
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('is_logged_in');
        $resolver->setDefaults([
            'data_class' => Message::class,
            'validation_groups' => function(FormInterface $form) {
                /** @var Message $message */
                $message = $form->getData();
                return [
                    'Default',
                    "feedback.category.{$message->getCategory()->value}",
                ];
            },
        ]);
    }
}
