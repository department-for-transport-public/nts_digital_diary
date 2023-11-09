<?php


namespace App\ExpressionLanguage;


use App\Attribute\AutoconfigureTag\ExpressionLanguageProvider;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[ExpressionLanguageProvider(ExpressionLanguageProvider::WORKFLOW)]
class WizardFormDataFunctionProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var mixed
     */
    private $formData;

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('getFormData',
                function() {return "getFormData()";},
                function() {return $this->formData ?? null;}
            ),
            new ExpressionFunction('isFormDataSet',
                function(){return "isFormDataSet()";},
                function(){return isset($this->formData);}
            ),
            new ExpressionFunction('isFormDataPropertySameAs',
                function($property, $value){return "isFormDataPropertySameAs($property, $value)";},
                function($expressionContext, $property, $value) {
                    $propertyAccess = PropertyAccess::createPropertyAccessor();
                    return isset($this->formData)
                        && $propertyAccess->isReadable($this->formData, $property)
                        && $propertyAccess->getValue($this->formData, $property) === $value;
                }
            ),
        ];
    }

    public function setFormData($formData)
    {
        $this->formData = $formData;
    }
}