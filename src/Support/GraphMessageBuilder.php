<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Support;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class GraphMessageBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Email $email, string $sender, bool $saveToSentItems): array
    {
        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        $message = [
            'subject' => $email->getSubject() ?? '(no subject)',
            'body' => [
                'contentType' => $html !== null ? 'HTML' : 'Text',
                'content' => $html ?? $text ?? '',
            ],
            'from' => [
                'emailAddress' => [
                    'address' => $sender,
                ],
            ],
            'toRecipients' => $this->mapAddresses($email->getTo()),
        ];

        $cc = $this->mapAddresses($email->getCc());
        $bcc = $this->mapAddresses($email->getBcc());
        $replyTo = $this->mapAddresses($email->getReplyTo());

        if ($cc !== []) {
            $message['ccRecipients'] = $cc;
        }

        if ($bcc !== []) {
            $message['bccRecipients'] = $bcc;
        }

        if ($replyTo !== []) {
            $message['replyTo'] = $replyTo;
        }

        $attachments = $this->mapAttachments($email->getAttachments());

        if ($attachments !== []) {
            $message['attachments'] = $attachments;
        }

        return [
            'message' => $message,
            'saveToSentItems' => $saveToSentItems,
        ];
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array<string, array<string, string>>>
     */
    private function mapAddresses(array $addresses): array
    {
        return array_values(array_map(function (Address $address): array {
            $payload = [
                'address' => $address->getAddress(),
            ];

            $name = trim($address->getName());

            if ($name !== '') {
                $payload['name'] = $name;
            }

            return ['emailAddress' => $payload];
        }, $addresses));
    }

    /**
     * @param  array<int, DataPart>  $attachments
     * @return array<int, array<string, string>>
     */
    private function mapAttachments(array $attachments): array
    {
        return array_values(array_map(function (DataPart $part): array {
            return [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $part->getFilename() ?? $part->getName() ?? 'attachment',
                'contentType' => $part->getContentType(),
                'contentBytes' => base64_encode($part->getBody()),
            ];
        }, $attachments));
    }
}
