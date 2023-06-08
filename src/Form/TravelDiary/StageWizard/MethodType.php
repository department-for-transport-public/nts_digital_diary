<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MethodType extends AbstractType
{
    const INJECT_TRANSLATION_PARAMETERS = [
        'dayNumber' => 'journey.diaryDay.number',
        'stage_number' => 'number',
    ];

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper(new MethodDataMapper());

        $choices = $this->entityManager->getRepository(Method::class)->findBy([], ['sort' => 'ASC']);
        $choices = array_combine(array_map(fn(Method $m) => $m->getDescriptionTranslationKey(), $choices), $choices);

        $choiceOptions = [
            'walk' => ['help' => "stage.method.choices.walk.help"],
        ];

        $otherFormConfig = [];
        foreach ($choices as $key=>$choice) {
            if (!$choice->getCode()) {
                $transKey = $choice->getDescriptionTranslationKey();
                $formName = "other-$transKey";
                $choiceOptions[$key] = ['conditional_form_name' => $formName];
                $otherFormConfig[$formName] = [
                    'label' => "stage.method.other.$transKey.label",
                    'label_attr' => ['class' => 'govuk-hint'],
                    'help' => "stage.method.other.$transKey.help",
                ];
            }
        }

        $builder
            ->add('method', EntityType::class, [
                'label_attr' => ['class' => 'govuk-label--l'],
                'label_is_page_heading' => true,
                'label' => "stage.method.page-title",
                'help' => "stage.method.help",
                'class' => Method::class,
                'choices' => $choices,
                'choice_label' => function (Method $choice) {
                    return "stage.method.choices.{$choice->getDescriptionTranslationKey()}";
                },
                'choice_options' => $choiceOptions,
                'group_by' => function (Method $method) {
                    return Method::TYPE_PRIVATE === $method->getDisplayGroup() ?
                        "stage.method.groups.private" :
                        "stage.method.groups.public";
                },
                'group_label_attr' => ['class' => 'govuk-label--m'],
                'group_label_heading_element' => 'h2',
            ]);

        foreach ($otherFormConfig as $name => $config) {
            $builder->add($name, InputType::class, array_merge_recursive([
                'mapped' => false,
            ], $config));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'validation_groups' => 'wizard.stage.method',
            'translation_domain' => 'travel-diary',
        ]);
    }
}