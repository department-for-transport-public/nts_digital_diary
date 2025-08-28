<?php


namespace App\Form\TravelDiary\RepeatJourneyWizard;


use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Repository\DiaryDayRepository;
use App\Utility\DateTimeFormats;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TargetDayType extends AbstractType
{

    public function __construct(
        protected DiaryDayRepository $diaryDayRepository,
        protected DateTimeFormats $dateTimeFormats,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->diaryDayRepository
            ->getQueryBuilderForRepeatJourneySourceDay($options['is_practice'] ? 0 : 7)
            ->getQuery()
            ->execute();

        $builder
            ->add('diaryDay', EntityType::class, [
                'class' => DiaryDay::class,
                'choices' => $choices,

                'choice_label' => fn(): string => 'repeat-journey.select-target-day.choice-label',
                'choice_value' => fn(?DiaryDay $d): string => $d ? 'day-'.$d->getNumber() : '',

                'label_is_page_heading' => true,
                'label' => 'repeat-journey.select-target-day.label',
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => 'repeat-journey.select-target-day.help',
                'choice_translation_parameters' => fn(DiaryDay $d): array => [
                    'dayNumber' => $d->getNumber(),
                    'date' => $d->getDate()->format($this->dateTimeFormats->getDate('with-dow')),
                    'journeyCount' => count($d->getJourneys()),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
            'validation_groups' => ["wizard.repeat-journey.target-day"],
            'translation_domain' => 'travel-diary',
            'is_practice' => false,
        ]);
        $resolver->setAllowedTypes('is_practice', ['bool']);
    }
}