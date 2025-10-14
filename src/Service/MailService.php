<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService
{
    public function __construct(private MailerInterface $mailer) {}

    public function send(string $to, string $subject, string $htmlContent, ?string $from = 'no-reply@sortie.com'): void {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}
