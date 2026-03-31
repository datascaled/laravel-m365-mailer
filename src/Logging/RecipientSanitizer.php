<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Logging;

use Symfony\Component\Mime\Address;

final class RecipientSanitizer
{
    public function __construct(private readonly string $hashKey) {}

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array<string, string>>
     */
    public function sanitize(array $addresses, bool $storePlaintext): array
    {
        return array_map(function (Address $address) use ($storePlaintext): array {
            $email = strtolower(trim($address->getAddress()));

            $payload = [
                'display' => $this->maskEmail($email),
                'hash' => hash_hmac('sha256', $email, $this->hashKey),
            ];

            if ($storePlaintext) {
                $payload['email'] = $email;
            }

            $name = trim($address->getName());

            if ($name !== '') {
                $payload['name'] = $name;
            }

            return $payload;
        }, $addresses);
    }

    private function maskEmail(string $email): string
    {
        [$localPart, $domainPart] = array_pad(explode('@', $email, 2), 2, '');

        if ($domainPart === '') {
            return substr($email, 0, 2).str_repeat('*', max(strlen($email) - 2, 1));
        }

        $maskedLocal = match (strlen($localPart)) {
            0 => '***',
            1 => $localPart.'***',
            2 => substr($localPart, 0, 1).'***',
            default => substr($localPart, 0, 2).'***',
        };

        $segments = explode('.', $domainPart);
        $maskedDomain = implode('.', array_map(function (string $segment): string {
            if ($segment === '') {
                return $segment;
            }

            return substr($segment, 0, 1).'***';
        }, $segments));

        return $maskedLocal.'@'.$maskedDomain;
    }
}
