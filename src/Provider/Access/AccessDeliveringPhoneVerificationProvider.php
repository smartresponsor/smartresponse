<?php

declare(strict_types=1);

namespace App\Provider\Access;

use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;
use App\Delivering\Message\DeliveringSendSms;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AccessDeliveringPhoneVerificationProvider implements AccessPhoneVerificationProviderInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function supports(string $providerName): bool
    {
        return 'delivering' === $providerName;
    }

    public function sendVerificationMessage(string $phoneNumber, string $message): void
    {
        $normalizedPhoneNumber = trim($phoneNumber);
        $normalizedMessage = trim($message);
        $correlationId = $this->correlationId();

        $this->messageBus->dispatch(new DeliveringSendSms(
            recipient: $normalizedPhoneNumber,
            body: $normalizedMessage,
            correlationId: $correlationId,
            idempotencyKey: hash('sha256', implode('|', [
                'accessing-phone-verification',
                $normalizedPhoneNumber,
                $normalizedMessage,
            ])),
        ));
    }

    private function correlationId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}
