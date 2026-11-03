<?php

namespace App\Support;

class ResellerClubDomainContacts
{
    public static function forDomain($domain, array $contacts)
    {
        $parts = explode('.', strtolower(trim($domain)));
        $tld = end($parts);

        $contactIds = [
            'reg-contact-id' => $contacts['reg'] ?? null,
            'admin-contact-id' => $contacts['admin'] ?? null,
            'tech-contact-id' => $contacts['tech'] ?? null,
            'billing-contact-id' => $contacts['billing'] ?? null,
        ];

        if (in_array($tld, ['eu', 'ru', 'uk'], true)) {
            $contactIds['admin-contact-id'] = '-1';
        }

        if (in_array($tld, ['eu', 'fr', 'ru', 'uk'], true)) {
            $contactIds['tech-contact-id'] = '-1';
        }

        if (in_array($tld, ['berlin', 'ca', 'eu', 'fr', 'nl', 'nz', 'ru', 'uk', 'london'], true)) {
            $contactIds['billing-contact-id'] = '-1';
        }

        foreach ($contactIds as $contactId) {
            if ($contactId === null || $contactId === '') {
                return null;
            }
        }

        return $contactIds;
    }
}
