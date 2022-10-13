<?php

namespace Ghost\GovUkFrontendBundle\Model;

use DateTimeZone;

class Time extends \DateTime
{
    public static function fromDateTime(\DateTimeInterface $dateTime)
    {
        return (new Time())->setTimestamp($dateTime->getTimestamp());
    }

    public function toDateTime()
    {
        return (new \DateTime())->setTimestamp($this->getTimestamp());
    }

    /**
     * @return \DateTime|false
     */
    public static function createFromFormat($format, $time, DateTimeZone $timezone = null)
    {
        return self::fromDateTime(\DateTime::createFromFormat($format, $time, $timezone));
    }
}
