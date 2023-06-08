<?php


namespace App\Form\OnBoarding\DiaryKeeperWizard;


use App\Entity\DiaryKeeper;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', InputType::class, [
                'label' => "diary-keeper.details.name.label",
                'help' => "diary-keeper.details.name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('number', NumberType::class, [
                'label' => "diary-keeper.details.number.label",
                'help' => "diary-keeper.details.number.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            /** @var DiaryKeeper $diaryKeeper */
            $diaryKeeper = $event->getData();
            $event->getForm()
                ->add('isAdult', BooleanChoiceType::class, [
                    'attr' => [],
                    'disabled' => !$diaryKeeper->getPrimaryDriverVehicles()->isEmpty(),
                    'label' => "diary-keeper.details.is-adult.label",
                    'help' => "diary-keeper.details.is-adult.help",
                    'label_attr' => ['class' => 'govuk-label--s'],
                ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DiaryKeeper::class,
            'translation_domain' => 'on-boarding',
            'validation_groups' => 'wizard.on-boarding.diary-keeper.details',
        ]);
    }
}