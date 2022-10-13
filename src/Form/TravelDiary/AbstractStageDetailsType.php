<?php


namespace App\Form\TravelDiary;


use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractStageDetailsType extends AbstractType
{
    const INJECT_TRANSLATION_PARAMETERS = [
        'method_type' => 'method.type',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transPrefix = "stage.details";
        $builder
            ->add('travelTime', NumberType::class, [
                'label' => "$transPrefix.travel-time.label",
                'help' => "$transPrefix.travel-time.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'attr' => ['class' => 'govuk-input--width-5'],
                'help_html' => 'markdown',
                'suffix' => "$transPrefix.travel-time.units",
            ])
            ->add('companions', FormType::class, [
                'inherit_data' => true,
                'label' => "$transPrefix.companions.label",
                'help' => "$transPrefix.companions.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help_html' => 'markdown',
                'attr' => ['class' => 'govuk-form-group--inline'],
            ]);
        $builder->get('companions')
            ->add('adultCount', NumberType::class, [
                'label' => "$transPrefix.adult-count.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "$transPrefix.adult-count.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('childCount', NumberType::class, [
                'label' => "$transPrefix.child-count.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "$transPrefix.child-count.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
        ]);
    }
}