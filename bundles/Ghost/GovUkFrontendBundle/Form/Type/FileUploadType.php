<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

// TODO: This is using both form inheritance and class inheritance. Class inheritance is a nono with symfony forms.
//  I believe it should only be using form inheritance on FileType. InputType should not be here at all?!
class FileUploadType extends FileType
{
    public function getParent(): string
    {
        return InputType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_file_upload';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge([
        ], $view->vars['attr']);

        $view->vars['type'] = 'file';
        $view->vars['blockClass'] = 'govuk-file-upload';

        // The built in symfony FileType clears the `value` var, but we need it to pass the GDS fixture tests
        $view->vars['value'] = $form->getData();
    }
}
