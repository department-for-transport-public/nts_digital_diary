<?php


namespace Ghost\GovUkFrontendBundle\Model;


use Symfony\Component\Translation\TranslatableMessage;

class NotificationBanner
{
    const FLASH_BAG_TYPE = 'notification-banner';

    const OPTION_STYLE = 'style';
    const OPTION_HTML_CONTENT = 'html_content';

    const STYLE_SUCCESS = 'success';
    const STYLE_WARNING = 'warning';

    protected string | TranslatableMessage $title;
    protected string | TranslatableMessage $heading;
    protected string | TranslatableMessage $content;

    /**
     * Valid options are:
     *  - type: false | 'success'
     */
    protected array $options;
    protected ?string $translationDomain;
    protected array $translationParameters;

    public function __construct(string | TranslatableMessage $title, string | TranslatableMessage $heading, string | TranslatableMessage $content, array $options = [], array $translationParameters = [], ?string $translationDomain = null)
    {
        $this->title = $title;
        $this->heading = $heading;
        $this->content = $content;
        $this->options = $options;
        $this->translationDomain = $translationDomain;
        $this->translationParameters = $translationParameters;
    }

    public function getTitle(): TranslatableMessage
    {
        return $this->title instanceof TranslatableMessage
            ? $this->title
            : new TranslatableMessage($this->title, $this->translationParameters, $this->translationDomain);
    }

    public function getHeading(): TranslatableMessage
    {
        return $this->heading instanceof TranslatableMessage
            ? $this->heading
            : new TranslatableMessage($this->heading, $this->translationParameters, $this->translationDomain);
    }

    public function getContent(): TranslatableMessage
    {
        return $this->content instanceof TranslatableMessage
            ? $this->content
            : new TranslatableMessage($this->content, $this->translationParameters, $this->translationDomain);
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}