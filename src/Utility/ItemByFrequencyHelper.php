<?php

namespace App\Utility;

class ItemByFrequencyHelper
{
    protected array $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addEntries(array $entries, string $nameField, string $countField): void {
        foreach($entries as $entry) {
            $count = $entry[$countField];
            $name = $entry[$nameField];

            $this->addEntry($name, $count);
        }
    }

    public function addEntry(string $name, int $count): void
    {
        $key = strtolower(preg_replace("/[^A-Z0-9]+/i", "", $name));

        if (isset($this->data[$key])) {
            $this->data[$key]['count'] += $count;
        } else {
            $this->data[$key] = [
                'name' => $name,
                'count' => $count,
            ];
        }
    }

    public function getTopN(int $n): array
    {
        usort($this->data, fn($a, $b) => $b['count'] <=> $a['count']);
        $topN = array_slice($this->data, 0, $n);
        return array_map(fn($data) => $data['name'], $topN);
    }
}