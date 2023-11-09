<?php


namespace App\Form\TravelDiary\ReturnJourneyWizard;


use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Repository\DiaryDayRepository;
use App\Utility\DateTimeFormats;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class TargetDayType extends AbstractType
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sourceJourneyId = $this->requestStack->getCurrentRequest()->get('_route_params')['journeyId'];

        $builder
            ->add('diaryDay', EntityType::class, [
                'class' => DiaryDay::class,
                'query_builder' => fn(DiaryDayRepository $r) => $r->getQueryBuilderForReturnJourneyTargetDay($sourceJourneyId),

                'choice_label' => fn(DiaryDay $d, $index) => new TranslatableMessage('return-journey.select-day.choice-label', [
                    'index' => $index,
                    'date' => $d->getDate()->format(DateTimeFormats::DATE_WITH_DOW),
                    'dayNumber' => $d->getNumber(),
                ], 'travel-diary'),
                'choice_value' => fn(?DiaryDay $d) => $d ? 'day-'.$d->getNumber() : '',
                'label_is_page_heading' => true,
                'label' => 'return-journey.select-day.label',
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => 'return-journey.select-day.help',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
            'validation_groups' => ["wizard.return-journey.target-day"],
            'translation_domain' => 'travel-diary',
        ]);
    }
}