<?php


namespace App\Form\TravelDiary\JourneyWizard;


use App\Entity\Journey\Journey;
use Ghost\GovUkFrontendBundle\Form\Type\TimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transPrefix = "journey.times";
        $builder
            ->add('startTime', TimeType::class, [
                'label' => "$transPrefix.start.label",
                'help' => "$transPrefix.start.help",
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add('endTime', TimeType::class, [
                'label' => "$transPrefix.end.label",
                'help' => "$transPrefix.end.help",
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => ['wizard.journey.times'],
        ]);
    }
}