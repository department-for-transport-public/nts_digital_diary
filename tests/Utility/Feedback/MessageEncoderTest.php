<?php

namespace App\Tests\Utility\Feedback;

use App\Utility\Feedback\MessageEncoder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MessageEncoderTest extends KernelTestCase
{
    protected MessageEncoder $messageEncoder;

    protected function setUp(): void
    {
        $container = self::getContainer();
        $messageEncoder = $container->get(MessageEncoder::class);

        if (!$messageEncoder instanceof MessageEncoder) {
            $this->fail('Failed to retrieve MessageEncoder from the container');
        }

        $this->messageEncoder = $messageEncoder;
    }

    public function dataIvMutator(): array
    {
        return [
            'single character mutation' => [
                function(string $iv) {
                    $lastCharIdx = strlen($iv) - 1;
                    $iv[$lastCharIdx] = $this->mutateCharacter($iv[$lastCharIdx]);
                    return $iv;
                }
            ],
            'mutate the entire IV' => [
                fn(string $iv) => join('', array_map([$this, 'mutateCharacter'], str_split($iv)))
            ],
        ];
    }

    public function testCanDecrypt(): void
    {
        $encodedMessage = $this->messageEncoder->encodeFeedback();
        $decrypted = $this->runDecryptFeedback($encodedMessage);

        // Message decrypted successfully?
        $this->assertNotNull($decrypted);
    }

    /**
     * @dataProvider dataIvMutator
     */
    public function testDecryptFeedbackIvMutation(\Closure $ivMutator): void
    {
        $encodedMessage = $this->messageEncoder->encodeFeedback();
        [$message, $iv] = explode(':', $encodedMessage);

        $iv = $ivMutator($iv);

        $decrypted = $this->runDecryptFeedback("{$message}:{$iv}");

        // Message should fail to decrypt
        $this->assertNull($decrypted);
    }

    /**
     * @dataProvider dataIvMutator
     */
    public function testDecodeFeedbackIvMutation(\Closure $ivMutator): void
    {
        $encodedMessage = $this->messageEncoder->encodeFeedback();
        [$message, $iv] = explode(':', $encodedMessage);

        $iv = $ivMutator($iv);

        $decoded = $this->messageEncoder->decodeFeedback("{$message}:{$iv}");

        // Message should fail to decode
        $this->assertNull($decoded);
    }

    protected function mutateCharacter(string $char): string
    {
        $map = '0123456789abcdef0';

        if (strlen($char) !== 1) {
            throw new \RuntimeException('Invalid character (incorrect length)');
        }

        $idx = strpos($map, $char);

        if ($idx === false) {
            throw new \RuntimeException('Invalid character (out of bounds)');
        }

        return $map[$idx + 1];
    }

    public function runDecryptFeedback(string $mutatedMessage): ?string
    {
        $reflClass = new \ReflectionClass($this->messageEncoder);
        $reflMethod = $reflClass->getMethod('decryptFeedback');

        return $reflMethod->invoke($this->messageEncoder, $mutatedMessage);
    }
}