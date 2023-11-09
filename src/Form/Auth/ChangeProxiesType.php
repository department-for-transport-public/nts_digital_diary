<?php

namespace App\Form\Auth;

use App\Entity\DiaryKeeper;
use App\Repository\DiaryKeeperRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeProxiesType extends AbstractType
{
    public function __construct(
        private readonly DiaryKeeperRepository $diaryKeeperRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($options) {
            /** @var DiaryKeeper $data */
            $data = $event->getData();
            $form = $event->getForm();

            $choices = $this->diaryKeeperRepository->getChangeProxyChoices($data);
            $choiceOpts = [];
            foreach ($choices as $key => $choice) {
                $choiceOpts[$key] = $choice->hasIdentifierForLogin() ? [] : ['disabled' => true];
            }

            $form
                ->add('proxies', EntityType::class, [
                    'label' => "diary-keeper.change-proxies.proxies.label",
                    'help' => "diary-keeper.change-proxies.proxies.help",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'class' => DiaryKeeper::class,
                    'multiple' => true,
                    'expanded' => true,
                    'choice_label' => function () {
                        return "diary-keeper.change-proxies.proxies.choice.label";
                    },
                    'choice_translation_parameters' => function (DiaryKeeper $choice) {
                        return [
                            'name' => $choice->getName(),
                            'hasEmail' => $choice->hasIdentifierForLogin() ? 1 : 0,
                        ];
                    },
                    'choices' => $choices,
                    'choice_options' => $choiceOpts,
                ])
                ->add('button_group', ButtonGroupType::class);

            $buttonGroup = $form->get('button_group');
            $buttonGroup
                ->add('save', ButtonType::class, [
                    'label' => 'actions.save',
                    'translation_domain' => 'messages',
                ]);

            if ($options['cancel_link_href']) {
                $buttonGroup
                    ->add('cancel', LinkType::class, [
                        'label' => 'actions.cancel',
                        'translation_domain' => 'messages',
                        'href' => $options['cancel_link_href'],
                    ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'interviewer',
            'data_class' => DiaryKeeper::class,
            'validation_groups' => function(FormInterface $form) {
                return $form->getData()->getMediaType() === DiaryKeeper::MEDIA_TYPE_DIGITAL
                    ? ['interviewer.diary-keeper.change-proxies']
                    : [];
            },
        ]);
        $resolver->setRequired(['cancel_link_href']);
    }
}