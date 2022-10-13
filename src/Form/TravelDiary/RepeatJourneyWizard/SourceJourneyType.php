<?php


namespace App\Form\TravelDiary\RepeatJourneyWizard;


use App\Entity\Journey\Journey;
use App\FormWizard\PropertyMerger;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\Repository\Journey\JourneyRepository;
use App\Utility\DateTimeFormats;
use App\Utility\TravelDiary\RepeatJourneyHelper;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class SourceJourneyType extends AbstractType
{
    private RepeatJourneyHelper $repeatJourneyHelper;

    public function __construct(RepeatJourneyHelper $repeatJourneyHelper)
    {
        $this->repeatJourneyHelper = $repeatJourneyHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper(new SourceJourneyDataMapper($this->repeatJourneyHelper))
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                /** @var RepeatJourneyState $state */
                $state = $event->getData();
                $event->getForm()
                    ->add('sourceJourneyId', EntityType::class, [
                        'class' => Journey::class,
                        'query_builder' => fn(JourneyRepository $r) => $r->getQueryBuilderByDayId($state->sourceDayId),
                        'model_property' => 'id',

                        'translation_domain' => 'travel-diary',
                        'label_is_page_heading' => true,
                        'label' => 'repeat-journey.select-journey.label',
                        'label_attr' => ['class' => 'govuk-label--l'],
                        'help' => 'repeat-journey.select-journey.help',
                        'choice_label' => fn(Journey $j) => new TranslatableMessage('repeat-journey.select-journey.choice.label', [
                            'startLocation' => $j->getStartLocationForDisplay(),
                            'endLocation' => $j->getEndLocationForDisplay(),
                            'purpose' => $j->getPurpose(),
                            'time' => $j->getStartTime()->format(DateTimeFormats::TIME_SHORT),
                        ], 'travel-diary'),
                    ]);
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ["wizard.repeat-journey.source-journey"],
            'translation_domain' => 'travel-diary',
        ]);
    }
}