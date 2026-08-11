<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Repository\DiaryKeeperRepository;
use App\Security\Voter\TravelDiary\JourneySharingVoter;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;

class ShareToType extends AbstractType
{
    public function __construct(
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
        protected readonly PropertyMerger                $propertyMerger
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper(new ShareToDataMapper($this->propertyMerger))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event): void {
                /** @var Journey $journey */
                $journey = $event->getData();
                $diaryKeeper = $journey->getDiaryDay()->getDiaryKeeper();
                $household = $diaryKeeper->getHousehold();

                $showSharingHelp = false;
                if (!$household->isJourneySharingEnabled()) {
                    foreach ($household->getDiaryKeepers() as $hdk) {
                        if ($hdk !== $diaryKeeper && !$this->canShareWith($hdk)) {
                            $showSharingHelp = true;
                            break;
                        }
                    }
                }

                $event->getForm()
                    ->add('shareTo', EntityType::class, [
                        'mapped' => false,
                        'label' => false,
                        'help' => $showSharingHelp ? 'share-journey.who-with.sharing-help' : null,
                        'multiple' => true,
                        'class' => DiaryKeeper::class,
                        'query_builder' => fn(DiaryKeeperRepository $r): \Doctrine\ORM\QueryBuilder => $r->getQueryBuilderForShareJourneyWhoWith(),
                        'choice_label' => fn(DiaryKeeper $dk): \Symfony\Component\Translation\TranslatableMessage => new TranslatableMessage('share-journey.who-with.choice.label', [
                            'name' => $dk->getName(),
                            'canShare' => $this->canShareWith($dk) ? 1 : 0,
                        ], 'travel-diary'),
                        'choice_options' => fn(DiaryKeeper $dk): array => [
                            'disabled' => !$this->canShareWith($dk),
                        ],
                    ]);

            });
    }

    public function configureOptions(OptionsResolver $resolver): void
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