<?php


namespace App\Form\TravelDiary\ReturnJourneyWizard;


use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Repository\DiaryDayRepository;
use App\Utility\DateTimeFormats;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class TargetDayType extends AbstractType
{
    public function __construct(
        protected RequestStack $requestStack,
        protected DateTimeFormats $dateTimeFormats,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $sourceJourneyId = $this->requestStack->getCurrentRequest()->get('_route_params')['journeyId'];

        $builder
            ->add('diaryDay', EntityType::class, [
                'class' => DiaryDay::class,
                'query_builder' => fn(DiaryDayRepository $r): QueryBuilder => $r->getQueryBuilderForReturnJourneyTargetDay($sourceJourneyId),

                'choice_label' => fn(DiaryDay $d, $index): TranslatableMessage => new TranslatableMessage('return-journey.select-day.choice-label', [
                    'index' => $index,
                    'date' => $d->getDate()->format($this->dateTimeFormats->getDate('with-dow')),
                    'dayNumber' => $d->getNumber(),
                ], 'travel-diary'),
                'choice_value' => fn(?DiaryDay $d): string => $d ? 'day-'.$d->getNumber() : '',
                'label_is_page_heading' => true,
                'label' => 'return-journey.select-day.label',
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => 'return-journey.select-day.help',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
            'validation_groups' => ["wizard.return-journey.target-day"],
            'translation_domain' => 'travel-diary',
        ]);
    }
}