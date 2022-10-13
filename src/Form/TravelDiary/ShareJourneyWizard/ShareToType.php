<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\FormWizard\PropertyMerger;
use App\Repository\DiaryKeeperRepository;
use App\Security\Voter\JourneySharingVoter;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

class ShareToType extends AbstractType
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private PropertyMerger $propertyMerger;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, PropertyMerger $propertyMerger)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->propertyMerger = $propertyMerger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper(new ShareToDataMapper($this->propertyMerger))

            ->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event){
                /** @var Journey $journey */
                $journey = $event->getData();
                $event->getForm()
                    ->add('shareTo', EntityType::class, [
                        'mapped' => false,
                        'label' => false,
                        'help' => $journey->getDiaryDay()->getDiaryKeeper()->getHousehold()->isJourneySharingEnabled() ? null : 'share-journey.who-with.sharing-help',
                        'multiple' => true,
                        'class' => DiaryKeeper::class,
                        'query_builder' => fn(DiaryKeeperRepository $r) => $r->getQueryBuilderForShareJourneyWhoWith(),
                        'choice_label' => fn(DiaryKeeper $dk) => new TranslatableMessage('share-journey.who-with.choice.label', [
                            'name' => $dk->getName(),
                            'canShare' => $this->canShareWith($dk) ? 1 : 0,
                        ], 'travel-diary'),
                        'choice_options' => fn(DiaryKeeper $dk) => [
                            'disabled' => !$this->canShareWith($dk),
                        ],
                    ])
                ;

            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'wizard.share-journey.share-to',
            'translation_domain' => 'travel-diary',
        ]);
    }

    protected function canShareWith(DiaryKeeper $dk): bool
    {
        static $canShare = [];
        return $canShare[$dk->getId()]
            ?? ($canShare[$dk->getId()] = $this->authorizationChecker->isGranted(JourneySharingVoter::CAN_SHARE_WITH_DIARY_KEEPER, $dk));
    }
}