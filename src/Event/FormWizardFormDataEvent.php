<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

class FormWizardFormDataEvent extends Event
{
    public const NAME = "form_wizard.form_data";

    private $formData;

    public function __construct($formData)
    {
        $this->formData = $formData;
    }

    /**
     * @return mixed
     */
    public function getFormData()
    {
        return $this->formData;
    }
}