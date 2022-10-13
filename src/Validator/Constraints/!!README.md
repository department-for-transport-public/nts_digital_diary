Remember Sammy Jankis...

Expression or Callback validators can often do what you need - look at that before creating a custom constraint/validator.

e.g.

```php
    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Expression("this.getSurveyPeriodEnd() >= this.getSurveyPeriodStart()", groups={"add_international_survey"}, message="common.survey.period-end.after-start")
     */
    private ?DateTime $surveyPeriodEnd;
```

Failing that - you can add a validator to a property and still access the parent object...

```php
    public function validate($value, Constraint $constraint)
    {
        $parentObject = $this->context->getObject();

```
