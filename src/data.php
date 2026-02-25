<?php

declare(strict_types=1);

function get_sample_data(): array
{
    return [
        'campaigns' => [
            [
                'id' => 101,
                'title' => 'February Promo',
                'subject' => 'Love the savings this week',
                'status' => 'Draft',
                'audience' => 'All Customers',
                'updated' => '2026-02-06 14:22',
            ],
            [
                'id' => 102,
                'title' => 'New Feature Launch',
                'subject' => 'Meet the new dashboard',
                'status' => 'Scheduled',
                'audience' => 'Power Users',
                'updated' => '2026-02-07 09:10',
            ],
            [
                'id' => 103,
                'title' => 'Churn Save',
                'subject' => 'We saved you a seat',
                'status' => 'Sent',
                'audience' => 'At-Risk',
                'updated' => '2026-02-03 18:45',
            ],
        ],
        'smtp_configs' => [
            [
                'id' => 'smtp-1',
                'name' => 'Primary SMTP',
                'host' => 'smtp.mailhost.com',
                'from' => 'hello@mailr.com',
                'status' => 'Active',
            ],
            [
                'id' => 'smtp-2',
                'name' => 'Backup SMTP',
                'host' => 'smtp.backup.com',
                'from' => 'backup@mailr.com',
                'status' => 'Paused',
            ],
        ],
        'contact_lists' => [
            [
                'id' => 'list-1',
                'name' => 'All Customers',
                'count' => 18420,
                'updated' => '2026-02-05',
            ],
            [
                'id' => 'list-2',
                'name' => 'Power Users',
                'count' => 2560,
                'updated' => '2026-02-01',
            ],
            [
                'id' => 'list-3',
                'name' => 'At-Risk',
                'count' => 740,
                'updated' => '2026-01-28',
            ],
        ],
        'test_contacts' => [
            [
                'id' => 'test-1',
                'email' => 'qa-team@mailr.com',
                'name' => 'QA Team',
                'notes' => 'General QA list',
            ],
            [
                'id' => 'test-2',
                'email' => 'marketing@mailr.com',
                'name' => 'Marketing Leads',
                'notes' => 'Review copy and links',
            ],
        ],
    ];
}

