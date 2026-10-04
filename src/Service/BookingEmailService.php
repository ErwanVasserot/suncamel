<?php

namespace App\Service;

use App\Entity\Booking;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BookingEmailService
{
    private const ADMIN_EMAIL = 'guilhem.camel@gmail.com';
    private const RENTAL_TIMEZONE = 'Pacific/Auckland';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function sendBookingConfirmedEmails(Booking $booking): void
    {
        $customerEmail = $booking->getEmail();

        $context = [
            'booking' => $booking,
            'timezone' => new \DateTimeZone(self::RENTAL_TIMEZONE),
            'terms_url' => $this->urlGenerator->generate(
                'page_show',
                ['slug' => 'pages/suncamel-terms-and-conditions'],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ];

        $this->mailer->send((new TemplatedEmail())
            ->from(new Address('no-reply@suncamel.co.nz', 'SunCamel'))
            ->to($customerEmail)
            ->subject(sprintf('Your SunCamel booking %s is confirmed', $booking->getReference()))
            ->htmlTemplate('email/booking_confirmation.html.twig')
            ->context($context));

        $this->mailer->send((new TemplatedEmail())
            ->from(new Address('no-reply@suncamel.co.nz', 'SunCamel'))
            ->to(self::ADMIN_EMAIL)
            ->replyTo($customerEmail)
            ->subject(sprintf('New booking %s', $booking->getReference()))
            ->htmlTemplate('email/new_booking.html.twig')
            ->context($context));
    }
}
