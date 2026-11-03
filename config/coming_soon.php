<?php

/**
 * Public "coming soon" services.
 *
 * One source of truth for the standalone /coming-soon/{service} pages and the
 * client-area /mystore/coming-soon/{service} pages: the route validates the
 * slug against these keys, and the views read the copy, icon and accent from
 * here. Adding a service is a single entry.
 *
 * `accent`     — 6-digit hex used for the eyebrow, icon tint and focus ring.
 * `icon`       — inline SVG path data drawn at 24x24 (stroke, not fill).
 * `glyph`      — single character used by the client-area empty-state tile.
 * `tone`       — store.css tone key (blue / green / purple / orange / teal …)
 *                that colours the client-area tile.
 * `highlights` — three short reassurance points under the message.
 * `cta`        — the useful thing a visitor can do while they wait.
 */
return [

    'domain-transfer' => [
        'title' => 'Domain Transfer',
        'heading' => 'Domain Transfer is coming soon',
        'message' => 'We are carefully preparing Domain Transfer for you. It will be available soon.',
        'accent' => '#2563eb',
        'glyph' => '⇄',
        'tone' => 'blue',
        'icon' => 'M4 7h11l-3-3M20 17H9l3 3',
        'highlights' => [
            'Move an existing domain in a few guided steps',
            'Keep your remaining registration time',
            'No downtime while the transfer clears',
        ],
        'cta' => ['label' => 'Search a domain instead', 'route' => 'domains.index'],
    ],

    'ssl-certificate' => [
        'title' => 'SSL Certificate',
        'heading' => 'SSL Certificate is coming soon',
        'message' => 'We are carefully preparing SSL Certificate for you. It will be available soon.',
        'accent' => '#0f9d76',
        'glyph' => '◇',
        'tone' => 'purple',
        'icon' => 'M12 3l7 3v5c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6zM9.2 12l2 2 3.6-3.8',
        'highlights' => [
            'Padlock and HTTPS on every page',
            'Free automated renewal reminders',
            'Issued and installed for you',
        ],
        'cta' => ['label' => 'Register a domain', 'route' => 'domains.index'],
    ],

    'business-email' => [
        'title' => 'Business Email',
        'heading' => 'Business Email is coming soon',
        'message' => 'We are carefully preparing Business Email for you. It will be available soon.',
        'accent' => '#7c5ce0',
        'glyph' => '✉',
        'tone' => 'orange',
        'icon' => 'M3 6h18v12H3zM3 7l9 6 9-6',
        'highlights' => [
            'Your name at your own domain',
            'Spam and phishing filtering built in',
            'Mailbox size that grows with your team',
        ],
        'cta' => ['label' => 'Browse hosting plans', 'route' => 'hosting.shared'],
    ],

    'sms-service' => [
        'title' => 'SMS Service',
        'heading' => 'SMS Service is coming soon',
        'message' => 'We are carefully preparing SMS Service for you. It will be available soon.',
        'accent' => '#e0842c',
        'glyph' => '◌',
        'tone' => 'teal',
        'icon' => 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z',
        'highlights' => [
            'Bulk and transactional SMS in one place',
            'Delivery reports for every campaign',
            'Local sender IDs for Bangladesh',
        ],
        'cta' => ['label' => 'Contact support', 'route' => 'support'],
    ],

];
