<?php

namespace App\Form\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Method;
use App\Entity\OtpUserInterface;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class VehicleType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var OtpUserInterface $user */
        $user = $this->security->getUser();

        $builder
            ->add('friendlyName', InputType::class, [
                'label' => "vehicle.form.name.label",
                'help' => "vehicle.form.name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('primaryDriver', EntityType::class, [
                'label' => "vehicle.form.primary-driver.label",
                'help' => "vehicle.form.primary-driver.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'expanded' => true,
                'class' => DiaryKeeper::class,
                'choice_label' => 'name',
                'choices' => $user->getHousehold()->getDiaryKeepersWhoCanBePrimaryDrivers(),
            ])
            ->add('capiNumber', InputType::class, [
                'label' => "vehicle.form.capi-number.label",
                'help' => "vehicle.form.capi-number.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('method', EntityType::class, [
                'class' => Method::class,
                'choice_label' => fn(Method $x) => $x->getPrefixedDescriptionTranslationKey('stage.method.choices.', true),
                'query_builder' => fn(EntityRepository $er) => $er
                    ->createQueryBuilder('m')
                    ->where('m.descriptionTranslationKey IN (:keys)')
                    ->andWhere('m.type = :type')
                    ->setParameters([
                        'keys' => Vehicle::VALID_METHOD_KEYS,
                        'type' => 'private',
                    ])
                    ->orderBy('m.id'),
                'choice_translation_domain' => 'travel-diary',
                'label' => "vehicle.form.method.label",
                'help' => "vehicle.form.method.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('button_group', ButtonGroupType::class);

        $builder
            ->get('button_group')
            ->add('save', ButtonType::class, [
                'label' => "vehicle.form.submit",
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'actions.cancel',
                'translation_domain' => 'messages',
                'href' => $options['cancel_link_href'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'translation_domain' => 'on-boarding',
            'validation_groups' => ['wizard.on-boarding.vehicle'],
        ]);

        $resolver->setRequired([
            'cancel_link_href',
        ]);
    }
}