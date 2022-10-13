<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordType extends AbstractType
{
    public function getParent(): string
    {
        return InputType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_password';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type'] = 'password';
        $view->vars['enable_show_password'] = $options['enable_show_password'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // Show password functionality:
        //   * https://technology.blog.gov.uk/2021/04/19/simple-things-are-complicated-making-a-show-password-option/
        //   * https://components.publishing.service.gov.uk/component-guide/show_password
        //   * https://github.com/alphagov/govuk_publishing_components/blob/main/app/views/govuk_publishing_components/components/_show_password.html.erb
        //   * https://github.com/alphagov/govuk_publishing_components/blob/main/app/assets/javascripts/govuk_publishing_components/components/show-password.js
        $resolver->setDefault('enable_show_password', true);
        $resolver->setDefault('disable_form_submit_check', false);
        $resolver->setDefault('show_password_translation_domain', 'govuk-frontend');

        $resolver->setNormalizer('attr', function(Options $options, $value) {
            if ($options['enable_show_password']) {
                if (!is_array($value)) {
                    $value = [];
                }

                if ($options['disable_form_submit_check']) {
                    $value['data-disable-form-submit-check'] = '1';
                }
            }

            return $value;
        });
    }
}
