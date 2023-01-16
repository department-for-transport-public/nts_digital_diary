<?php

namespace App\Utility\Screenshots;

use Nesk\Puphpeteer\Resources\Page;
use Throwable;

class ScreenshotsException extends \Exception
{
    protected ?Page $page;

    public function __construct($message = "", Page $page = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->page = $page;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }
}