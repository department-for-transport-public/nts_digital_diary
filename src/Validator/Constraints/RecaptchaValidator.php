<?php

namespace App\Validator\Constraints;

use App\Utility\RecaptchaHelper;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RecaptchaValidator extends ConstraintValidator
{

    public function __construct(
        protected RecaptchaHelper $recaptchaHelper,
        protected RequestStack $requestStack,
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Recaptcha) {
            throw new UnexpectedTypeException($constraint, Recaptcha::class);
        }

        $request = $this->requestStack->getMainRequest();
        $userResponse = trim($request->request->get('g-recaptcha-response', ''));

        if ($userResponse === '') {
            $this->context->addViolation('Respond to the captcha');
            return;
        }

        if ($this->context->getViolations()->count() > 0) {
            // No point making a call to check the captcha if the form has other errors!
            return;
        }

        $client = new Client([
            'timeout' => 3.0,
        ]);

        $serverResponse = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $this->recaptchaHelper->getRecaptchaSecretKey(),
                'response' => $userResponse,
            ],
        ]);

        $serverResponseJson = json_decode($serverResponse->getBody()->getContents(), true);

        if (is_array($serverResponseJson) && ($serverResponseJson['success'] ?? false) === true) {
            // Success!
            return;
        }

        $this->context->addViolation('Captcha response unsuccessful');
    }
}