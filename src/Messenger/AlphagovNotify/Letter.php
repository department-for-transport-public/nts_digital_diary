<?php


namespace App\Messenger\AlphagovNotify;


use App\Entity\Embeddable\Address;
use function PHPUnit\Framework\isEmpty;

class Letter extends AbstractMessage
{
    public function __construct(string $eventName, string $originatingEntityClass, string $originatingEntityId, protected Address $address, string $templateId, array $personalisation = [], ?string $reference = null)
    {
        parent::__construct($eventName, $originatingEntityClass, $originatingEntityId, $templateId, $personalisation, $reference);
    }

    public function getSendMethodParameters(): array
    {
        return [
            $this->getTemplateId(),
            $this->getPersonalisation(),
            $this->getReference(),
        ];
    }

    public function getPersonalisation(): ?array
    {
        return array_merge(parent::getPersonalisation(), $this->getAddressForPersonalisation());
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getAddressForPersonalisation(): array
    {
        $address = array_values(array_filter($this->address->toArray()));
        $notifyAddressFields = ['address_line_1', 'address_line_2', 'address_line_3', 'address_line_4', 'address_line_5', 'address_line_6', 'address_line_7'];

        // if our address has fewer fields than notify requires, create additional null values
        $address = array_merge($address, array_fill(count($address), count($notifyAddressFields) - count($address), null));
        return array_combine(
            $notifyAddressFields,
            $address
        );
    }

    public function getRecipient(): ?string
    {
        return $this->address->isFilled()
            ? join("\n", array_filter($this->address->toArray(), fn($v) => !isEmpty($v)))
            : null;
    }
}