<?php

namespace App\Tests\Twig;

use App\Entity\Embeddable\CostOrNil;
use App\Twig\CostOrNilExtension;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CostOrNilExtensionTest extends TestCase
{
    private CostOrNilExtension $extension;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnCallback(
            fn(string $key, array $params) => match(true) {
                isset($params['cost']) => "£{$params['cost']}",
                default => "[$key]",
            }
        );

        $this->extension = new CostOrNilExtension($this->translator);
    }

    public function dataFormatCostOrNil(): array
    {
        return [
            'null CostOrNil' => [null, ' - '],
            'hasCost: null, cost: null' => [
                (new CostOrNil()),
                ' - ',
            ],
            'hasCost: false, cost: any' => [
                (new CostOrNil())->setHasCost(false)->setCost(BigDecimal::of('12.50')),
                '[common.cost.nil]',
            ],
            'hasCost: true, cost: null' => [
                (new CostOrNil())->setHasCost(true),
                ' - ',
            ],
            'hasCost: true, cost: zero' => [
                (new CostOrNil())->setHasCost(true)->setCost(BigDecimal::zero()),
                ' - ',
            ],
            'hasCost: true, cost: 5.50' => [
                (new CostOrNil())->setHasCost(true)->setCost(BigDecimal::of('5.50')),
                '£5.5',
            ],
        ];
    }

    /**
     * @dataProvider dataFormatCostOrNil
     */
    public function testFormatCostOrNil(?CostOrNil $costOrNil, string $expected): void
    {
        $result = $this->extension->format_cost_or_nil($costOrNil, 'some.translation.key');
        $this->assertEquals($expected, $result);
    }
}
