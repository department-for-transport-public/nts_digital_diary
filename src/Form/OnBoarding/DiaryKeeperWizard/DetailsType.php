<?php


namespace App\Form\OnBoarding\DiaryKeeperWizard;


use App\Entity\DiaryKeeper;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transPrefix = "diary-keeper.details";
        $builder
            ->add('name', InputType::class, [
                'label' => "$transPrefix.name.label",
                'help' => "$transPrefix.name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('number', NumberType::class, [
                'label' => "$transPrefix.number.label",
                'help' => "$transPrefix.number.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('isAdult', BooleanChoiceType::class, [
                'attr' => [],
                'label' => "$transPrefix.is-adult.label",
                'help' => "$transPrefix.is-adult.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ;
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