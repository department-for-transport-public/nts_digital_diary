<?php


namespace App\Form\TravelDiary\ReturnJourneyWizard;


use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Form\CostOrNilType;
use App\Form\TravelDiary\AbstractStageDetailsType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StageDetailsType extends AbstractStageDetailsType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Stage $stage */
            $stage = $event->getData();
            $form = $event->getForm();

            switch ($stage->getMethod()->getType()) {
                case Method::TYPE_PRIVATE :
                    if ($stage->getIsDiaryKeeperAdult()) {
                        $form->add('parkingCost', CostOrNilType::class, [
                            'translation_prefix' => 'return-journey.stage-details.parking-cost',
                            'priority' => 1,
                        ]);
                    }
                    break;
                case Method::TYPE_PUBLIC :
                    $form->add('ticketCost', CostOrNilType::class, [
                        'translation_prefix' => "return-journey.stage-details.ticket-cost",
                        'priority' => 1,
                    ]);
                    break;
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'validation_groups' => 'wizard.return-journey.stage-details',
        ]);
    }
}