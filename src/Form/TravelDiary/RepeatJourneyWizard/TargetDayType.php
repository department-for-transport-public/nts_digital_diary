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
    private DiaryDayRepository $diaryDayRepository;

    public function __construct(DiaryDayRepository $diaryDayRepository)
    {
        $this->diaryDayRepository = $diaryDayRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = $this->diaryDayRepository
            ->getQueryBuilderForRepeatJourneySourceDay($options['is_practice'] ? 0 : 7)
            ->getQuery()
            ->execute();

        $builder
            ->add('diaryDay', EntityType::class, [
                'class' => DiaryDay::class,
                'choices' => $choices,

                'choice_label' => fn() => 'repeat-journey.select-target-day.choice-label',
                'choice_value' => fn(?DiaryDay $d) => $d ? 'day-'.$d->getNumber() : '',

                'label_is_page_heading' => true,
                'label' => 'repeat-journey.select-target-day.label',
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => 'repeat-journey.select-target-day.help',
                'choice_translation_parameters' => fn(DiaryDay $d) => [
                    'dayNumber' => $d->getNumber(),
                    'date' => $d->getDate()->format(DateTimeFormats::DATE_WITH_DOW),
                    'journeyCount' => count($d->getJourneys()),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
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