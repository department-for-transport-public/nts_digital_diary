<?php

namespace App\Form\Admin\Tools;

use App\Entity\AreaPeriod;
use App\Repository\AreaPeriodRepository;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class DirectOnboardingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('area', EntityType::class, [
                'label' => false,
                'class' => AreaPeriod::class,
                'choice_label' => 'area',
                'expanded' => false,
                'placeholder' => 'tools.index.direct-onboarding.select-area',
                'query_builder' => fn(AreaPeriodRepository $er): QueryBuilder => $er->createQueryBuilder('ap')
                    ->where('ap.trainingInterviewer is NULL')
                    ->andWhere('EXISTS (SELECT 1 FROM App\Entity\OtpUser ou WHERE ou.areaPeriod = ap)')
                    ->orderBy('ap.area', 'ASC'),
                'choice_translation_domain' => false,
                'constraints' => [new NotBlank()],
            ])
/*            ->add('dkCount', ChoiceType::class, [
                'label' => false,
                'placeholder' => '# of DKs',
                'choices' => ['1'=>1, '2'=>2, '3'=>3, '4'=>4],
                'expanded' => false,
                'constraints' => [new NotBlank()],
            ])
            ->add('vCount', ChoiceType::class, [
                'label' => false,
                'placeholder' => '# of Vehicles',
                'choices' => ['1'=>1, '2'=>2],
                'expanded' => false,
                'constraints' => [new NotBlank()],
            ])*/
            ->add('submit', ButtonType::class, [
                'label' => 'tools.index.direct-onboarding.submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
            'attr' => [
                'class' => 'govuk-fieldset--inline govuk-fieldset--inline-narrow',
            ],
//            'method' => Request::METHOD_GET,
        ]);
    }
}