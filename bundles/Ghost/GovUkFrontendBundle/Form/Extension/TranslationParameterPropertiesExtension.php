<?php


namespace Ghost\GovUkFrontendBundle\Form\Extension;

use App\Utility\PropertyAccessHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class TranslationParameterPropertiesExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class
        ];
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $formType = get_class($form->getConfig()->getType()->getInnerType());
        if (defined($transPropertiesConstant = "{$formType}::INJECT_TRANSLATION_PARAMETERS")) {
            $transParameters = PropertyAccessHelper::resolveMap($form->getData(), constant($transPropertiesConstant));
            $view->vars['help_translation_parameters'] = array_merge($transParameters, $view->vars['help_translation_parameters'] ?? []);
            $view->vars['label_translation_parameters'] = array_merge($transParameters, $view->vars['label_translation_parameters'] ?? []);
        }
    }
}