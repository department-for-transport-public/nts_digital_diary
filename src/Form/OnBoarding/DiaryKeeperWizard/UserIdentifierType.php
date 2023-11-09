<?php

namespace App\Form\OnBoarding\DiaryKeeperWizard;

use App\Entity\DiaryKeeper;
use App\Repository\DiaryKeeperRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserIdentifierType extends AbstractType
{
    const INJECT_TRANSLATION_PARAMETERS = [
        'onlyDiaryKeeper' => 'isTheOnlyDiaryKeeper',
    ];

    protected DiaryKeeperRepository $diaryKeeperRepository;

    public function __construct(DiaryKeeperRepository $diaryKeeperRepository)
    {
        $this->diaryKeeperRepository = $diaryKeeperRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper(new UserIdentityDataMapper());

        $builder->add('mediaType', ChoiceType::class, [
            'label' => "diary-keeper.user-identifier.media-type.label",
            'help' => "diary-keeper.user-identifier.media-type.help",
            'label_attr' => ['class' => 'govuk-label--m'],
            'choices' => [
                'diary-keeper.user-identifier.media-type.choices.digital.label' => DiaryKeeper::MEDIA_TYPE_DIGITAL,
                'diary-keeper.user-identifier.media-type.choices.paper.label' => DiaryKeeper::MEDIA_TYPE_PAPER,
            ],
            'choice_options' => [
                'diary-keeper.user-identifier.media-type.choices.digital.label' => [
                    'conditional_form_name' => 'user',
                    'help' => 'diary-keeper.user-identifier.media-type.choices.digital.help',
                ],
                'diary-keeper.user-identifier.media-type.choices.paper.label' => [
                    'help' => 'diary-keeper.user-identifier.media-type.choices.paper.help',
                ],
            ],
        ]);

        $builder->add('user', FormType::class, [
            'label' => false,
            'error_bubbling' => false,
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            /** @var DiaryKeeper $data */
            $data = $event->getData();
            $form = $event->getForm();

            $isAdult = $data->getIsAdult();

            $form->get('user')
                ->add('username', InputType::class, [
                    'label' => "diary-keeper.user-identifier.user-identifier.label",
                    'help' => $isAdult ?
                        "diary-keeper.user-identifier.user-identifier.help" :
                        "diary-keeper.user-identifier.user-identifier.child-help",
                    'help_html' => 'markdown',
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => ['class' => 'govuk-input--width-20'],
                    'property_path' => 'username',
                    'row_attr' => ['class' => 'govuk-!-margin-top-4'],
                ]);
//                ->add('consent', CheckboxType::class, [
//                    'label' => "diary-keeper.user-identifier.consent.label",
//                    'help' => "diary-keeper.user-identifier.consent.help",
//                    'property_path' => 'hasConsented',
//                ]);

            if (!$data->isTheOnlyDiaryKeeper()) {
                $choices = $this->diaryKeeperRepository->getOnBoardingProxyChoices($data);
                $choiceOpts = [];
                foreach($choices as $key => $choice) {
                    $choiceOpts[$key] = $choice->hasIdentifierForLogin() ? [] : ['disabled' => true];
                }

                if (count($choices) > 0) {
                    $form->get('user')
                        ->add('proxies', EntityType::class, [
                            'label' => "diary-keeper.user-identifier.proxy.label",
                            'help' => "diary-keeper.user-identifier.proxy.help",
                            'label_attr' => ['class' => 'govuk-label--s'],
                            'class' => DiaryKeeper::class,
                            'multiple' => true,
                            'expanded' => true,
                            'choice_label' => function () {
                                return "diary-keeper.label";
                            },
                            'choice_translation_domain' => 'on-boarding',
                            'choice_translation_parameters' => function (DiaryKeeper $choice) {
                                return [
                                    'name' => $choice->getName(),
                                    'username' => $choice->getUser() ? $choice->getUser()->getUsername() : 'none',
                                    'hasEmail' => $choice->hasIdentifierForLogin() ? 1 : 0,
                                ];
                            },
                            'choices' => $choices,
                            'choice_options' => $choiceOpts,
                            'property_path' => 'diaryKeeper.proxies',
                            'row_attr' => ['class' => 'govuk-!-margin-top-7'],
                        ]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DiaryKeeper::class,
            'translation_domain' => 'on-boarding',
            'validation_groups' => function(FormInterface $form) {
                $groups = ['wizard.on-boarding.diary-keeper.media-type'];
                if ($form->getData()->getMediaType() === DiaryKeeper::MEDIA_TYPE_DIGITAL) {
                    $groups[] = 'wizard.on-boarding.diary-keeper.user-identifier';
                }

                return $groups;
            },
            'error_mapping' => ['proxies' => 'user'],
        ]);
    }
}