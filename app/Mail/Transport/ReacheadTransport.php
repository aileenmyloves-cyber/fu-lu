<?php

namespace App\Mail\Transport;

use RuntimeException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class ReacheadTransport extends AbstractTransport
{
    public function __construct(protected ?string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $from = $email->getFrom()[0]->getAddress();
        $subject = $email->getSubject();
        $html = $email->getHtmlBody() ?? $email->getTextBody();

        foreach ($email->getTo() as $recipient) {
            $response = Http::withToken($this->apiKey)
                ->post('https://reachead.com/api/v1/email/send', [
                    'from'    => $from,
                    'to'      => $recipient->getAddress(),
                    'subject' => $subject,
                    'html'    => $html,
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Reachead mail send failed: '.$response->body());
            }
        }
    }

    public function __toString(): string
    {
        return 'reachead';
    }
}
