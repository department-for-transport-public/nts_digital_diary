<?php

namespace App\Form\TravelDiary;

use App\Entity\DiaryKeeper;
use App\Features;
use App\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ApproveDiaryConfirmActionType extends AbstractType
{
    public function __construct(protected Features $features)
    {}

    public function getParent(): string
    {
        return ConfirmActionType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var DiaryKeeper $diaryKeeper */
        $diaryKeeper = $options['diary_keeper'];

        if ($diaryKeeper->hasEmptyDays()) {
            $builder
                ->add('verifyEmptyDays', Gds\CheckboxType::class, [
                    'priority' => 20,
                    'constraints' => [new NotBlank(message: "common.option.required")],
                    'label' => 'diary-state.approve.confirm-empty-journeys',
                    'label_translation_parameters' => ['name' => $diaryKeeper->getName()],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'diary_keeper'
        ]);
        $resolver->setDefaults([
            'translation_domain' => 'interviewer',
        ]);
    }
}