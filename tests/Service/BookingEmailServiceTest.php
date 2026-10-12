<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Repository\BookingEmailContentRepository;
use App\Service\BookingEmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BookingEmailServiceTest extends TestCase
{
    public function testCancellationEmailContainsTheBookingReference(): void
    {
        $sentMessages = [];
        $mailer = new class($sentMessages) implements MailerInterface {
            /** @param list<RawMessage> $messages */
            public function __construct(private array &$messages)
            {
            }

            public function send(RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): void
            {
                $this->messages[] = $message;
            }
        };
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $emailContent = $this->createStub(BookingEmailContentRepository::class);
        $booking = (new Booking())
            ->setReference('SC-123456')
            ->setEmail('customer@example.com');

        (new BookingEmailService($mailer, $urlGenerator, $emailContent))->sendBookingCancelledEmail($booking);

        self::assertCount(1, $sentMessages);
        self::assertInstanceOf(TemplatedEmail::class, $sentMessages[0]);
        self::assertSame('Your SunCamel booking SC-123456 has been cancelled', $sentMessages[0]->getSubject());
        self::assertSame(['customer@example.com'], array_map(
            static fn ($address): string => $address->getAddress(),
            $sentMessages[0]->getTo(),
        ));
        self::assertSame('email/booking_cancellation.html.twig', $sentMessages[0]->getHtmlTemplate());
        self::assertSame('SC-123456', $sentMessages[0]->getContext()['booking']->getReference());
    }
}
