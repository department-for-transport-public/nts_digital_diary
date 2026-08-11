<?php


namespace App\Form\TravelDiary\CombinedReflowWizard;


use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Repository\DiaryDayRepository;
use App\Repository\Journey\JourneyRepository;
use App\Utility\DateTimeFormats;
use App\Utility\TravelDiary\DuplicateJourneyHelper;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class SourceDayJourneyType extends AbstractType
{

    public function __construct(
        readonly private DuplicateJourneyHelper $duplicateJourneyHelper,
        protected DiaryDayRepository            $diaryDayRepository,
        protected JourneyRepository             $journeyRepository,
        protected DateTimeFormats               $dateTimeFormats,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $journeyType = $options['journey_type'];
        $builder->setDataMapper(new SourceDayJourneyDataMapper($this->duplicateJourneyHelper, $journeyType));
        $choices = $this->diaryDayRepository
            ->getQueryBuilderForRepeatJourneySourceDay()
            ->getQuery()
            ->execute();
        $choiceAttr = [];
        $choiceOptions = [];
        foreach ($choices as $key => $choice) {
            if (count($choice->getNonEmptyJourneys()) === 0) {
                $choiceAttr[$key] = ['disabled' => 'disabled'];
            } else {
                $choiceOptions[$key] = ['conditional_form_name' => $this->createJourneySubChoice($builder, $choice)];
            }
        }

        $builder
            ->add('sourceDayNumber', EntityType::class, [
                'class' => DiaryDay::class,
                'choices' => $choices,
                'choice_attr' => $choiceAttr,
                'choice_options' => $choiceOptions,
                'model_property' => 'number',

                'choice_label' => fn(): string => "{$journeyType}.select-day-journey.choice-label",
                'choice_value' => fn(?DiaryDay $d): string => $d ? 'day-'.$d->getNumber() : '',

                'label_is_page_heading' => true,
                'label' => "{$journeyType}.select-day-journey.label",
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => "{$journeyType}.select-day-journey.help",
                'choice_translation_parameters' => fn(DiaryDay $d): array => [
                    'dayNumber' => $d->getNumber(),
                    'date' => $d->getDate()->format($this->dateTimeFormats->getDate('with-dow')),
                    'journeyCount' => count($d->getNonEmptyJourneys()),
                ],
            ]);
    }

    protected function createJourneySubChoice(FormBuilderInterface $builder, DiaryDay $day): string
    {
        //$choices = $this->journeyRepository->getQueryBuilderByDayId($day->getId(), true)->getQuery()->execute();
        $choices = $day->getNonEmptyJourneys();
        $name = "day-{$day->getNumber()}-journey";
        $builder
            ->add($name, EntityType::class, [
                'priority' => -20,
                'property_path' => 'sourceJourneyId',
                'model_property' => 'id',
                'class' => Journey::class,
                'choices' => $choices,
                'label' => false,
                'choice_label' => fn(Journey $j): TranslatableMessage => new TranslatableMessage('repeat-journey.select-journey.choice.label', [
                    'startLocation' => $j->getStartLocationForDisplay(),
                    'endLocation' => $j->getEndLocationForDisplay(),
                    'purpose' => $j->getPurpose(),
                    'time' => $j->getStartTime()->format($this->dateTimeFormats->getTime('user-pref')),
                ], 'travel-diary'),
            ]);
        return $name;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => function(FormInterface $form){
                return $form->get('sourceDayNumber')->getData() === null
                    ? ["wizard.duplicate-journey.source-day"]
                    : ["wizard.duplicate-journey.source-journey"];
            },
            'translation_domain' => 'travel-diary',
        ]);
        $resolver
            ->setRequired('journey_type')
            ->setAllowedValues('journey_type', [DuplicateJourneyHelper::JOURNEY_TYPE_REPEAT, DuplicateJourneyHelper::JOURNEY_TYPE_RETURN])
        ;
    }
}