<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard;

use App\Entity\DiaryKeeper;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaAndIdentityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper(new UserIdentityDataMapper())

            ->add('user', FormType::class, [
                'label' => false,
                'error_bubbling' => false,
            ])

            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                /** @var DiaryKeeper $diaryKeeper */
                $diaryKeeper = $event->getData();
                $event->getForm()->get('user')
                    ->add('username', InputType::class, [
                        'label' => "direct-onboarding.diary-keeper-logins.username.label",
                        'help' => "direct-onboarding.diary-keeper-logins.username.help",
                        'label_translation_parameters' => ['name' => $diaryKeeper->getName(), 'number' => $diaryKeeper->getNumber()],
                        'help_html' => 'markdown',
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'attr' => ['class' => 'govuk-input--width-20'],
                        'property_path' => 'username',
                        'row_attr' => ['class' => 'govuk-!-margin-top-4'],
                    ])
                ;
            })
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DiaryKeeper::class,
            'translation_domain' => 'on-boarding',
        ]);
    }
}