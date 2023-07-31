<?php


namespace Ghost\GovUkFrontendBundle\Form\Extension;

use App\Utility\PropertyAccessHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * There is a problem with the built-in PHP MessageFormatter. It tries to access all message parameters and pre-format
 * them. If a parameter is used in the message, and has specific formatting that is fine, but if a parameters is not
 * used it may cause problems. E.g. a date parameter. Using the date parameter `{myDate, date}` is fine, but if the
 * date parameter is not used MessageFormatter just tries to convert it to a string, and we get a `Object of class
 * DateTime could not be converted to string` error.
 */
class TranslationParameterPropertiesExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class
        ];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $formType = get_class($form->getConfig()->getType()->getInnerType());
        if (defined($transPropertiesConstant = "{$formType}::INJECT_TRANSLATION_PARAMETERS")) {
            $transParameters = PropertyAccessHelper::resolveMap($form->getData(), constant($transPropertiesConstant));
            $view->vars['help_translation_parameters'] = array_merge($transParameters, $view->vars['help_translation_parameters'] ?? []);
            $view->vars['label_translation_parameters'] = array_merge($transParameters, $view->vars['label_translation_parameters'] ?? []);
        }
    }
}