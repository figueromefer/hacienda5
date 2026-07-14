<?php

namespace App\Support;

class ReceiptRecipients
{
    public static function normalize(?string $to, ?string $cc): array
    {
        $toRecipients = self::unique(self::parse($to));
        $toKeys = array_fill_keys(array_map('strtolower', $toRecipients), true);

        $ccRecipients = array_values(array_filter(
            self::unique(self::parse($cc)),
            fn (string $email): bool => ! isset($toKeys[strtolower($email)]),
        ));

        if ($toRecipients !== [] || $ccRecipients !== []) {
            $institutional = (string) config('mail.receipt_copy', 'info@haciendacinco.mx');
            $allKeys = array_fill_keys(array_map('strtolower', [...$toRecipients, ...$ccRecipients]), true);

            if ($institutional !== '' && ! isset($allKeys[strtolower($institutional)])) {
                $ccRecipients[] = $institutional;
            }
        }

        return ['to' => $toRecipients, 'cc' => $ccRecipients];
    }

    public static function parse(?string $recipients): array
    {
        if (blank($recipients)) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[,;\r\n]+/', (string) $recipients) ?: [],
        )));
    }

    private static function unique(array $recipients): array
    {
        $unique = [];

        foreach ($recipients as $email) {
            $key = strtolower($email);

            if (! isset($unique[$key])) {
                $unique[$key] = $email;
            }
        }

        return array_values($unique);
    }
}
