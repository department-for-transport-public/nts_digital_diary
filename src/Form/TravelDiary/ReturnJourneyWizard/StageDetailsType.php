<?php


namespace App\Form\TravelDiary\ReturnJourneyWizard;


use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Form\TravelDiary\AbstractStageDetailsType;
use Ghost\GovUkFrontendBundle\Form\Type\MoneyType;
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
                        $form->add('parkingCost', MoneyType::class, [
                            'label' => "return-journey.stage-details.parking-cost.label",
                            'label_attr' => ['class' => 'govuk-label--m'],
                            'help' => "return-journey.stage-details.parking-cost.help",
                            'help_html' => 'markdown',
                            'attr' => ['class' => 'govuk-input--width-4'],
                            'priority' => 1,
                        ]);
                    }
                    break;
                case Method::TYPE_PUBLIC :
                    $form->add('ticketCost', MoneyType::class, [
                        'label' => "return-journey.stage-details.ticket-cost.label",
                        'label_attr' => ['class' => 'govuk-label--m'],
                        'help_html' => 'markdown',
                        'help' => "return-journey.stage-details.ticket-cost.help",
                        'attr' => ['class' => 'govuk-input--width-5'],
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