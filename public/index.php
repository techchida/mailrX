<?php

declare(strict_types=1);

require __DIR__ . '/../src/app.php';
require __DIR__ . '/../src/layout.php';

app_bootstrap();
handle_request();

$routes = [
    'login' => [
        'title' => 'Sign In',
        'view' => __DIR__ . '/../src/pages/login.php',
    ],
    'dashboard' => [
        'title' => 'Campaigns',
        'view' => __DIR__ . '/../src/pages/dashboard.php',
    ],
    'create-campaign' => [
        'title' => 'Create Campaign',
        'view' => __DIR__ . '/../src/pages/create-campaign.php',
    ],
    'campaign-overview' => [
        'title' => 'Campaign Overview',
        'view' => __DIR__ . '/../src/pages/campaign-overview.php',
    ],
    'configs-test' => [
        'title' => 'Configs & Test Contacts',
        'view' => __DIR__ . '/../src/pages/configs-test.php',
    ],
    'manage-contacts' => [
        'title' => 'Contact Lists',
        'view' => __DIR__ . '/../src/pages/manage-contacts.php',
    ],
    'templates' => [
        'title' => 'Templates',
        'view' => __DIR__ . '/../src/pages/templates.php',
    ],
];

$page = $_GET['page'] ?? 'dashboard';
if (!array_key_exists($page, $routes)) {
    $page = 'dashboard';
}

if ($page !== 'login' && !auth_is_authenticated()) {
    $next = '/index.php?page=' . rawurlencode($page);
    foreach ($_GET as $key => $value) {
        if ($key === 'page') {
            continue;
        }
        if (is_array($value)) {
            continue;
        }
        $next .= '&' . rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
    }
    redirect_to('/index.php?page=login&next=' . rawurlencode($next));
}

if ($page === 'login' && auth_is_authenticated()) {
    redirect_to('/index.php?page=dashboard');
}

switch ($page) {
    case 'login':
        $data = [
            'next' => (string) ($_GET['next'] ?? '/index.php?page=dashboard'),
        ];
        break;
    case 'dashboard':
        $allCampaigns = fetch_campaigns();
        $campaignPage = max(1, (int) ($_GET['campaign_page'] ?? 1));
        $campaignsPerPage = 8;
        $campaignTotal = count($allCampaigns);
        $campaignTotalPages = max(1, (int) ceil($campaignTotal / $campaignsPerPage));
        $campaignPage = min($campaignPage, $campaignTotalPages);
        $campaignOffset = ($campaignPage - 1) * $campaignsPerPage;
        $pagedCampaigns = array_slice($allCampaigns, $campaignOffset, $campaignsPerPage);
        $data = [
            'campaigns' => $pagedCampaigns,
            'campaigns_all' => $allCampaigns,
            'campaign_stats_map' => fetch_campaign_engagement_stats_map(array_column($allCampaigns, 'id')),
            'campaign_pagination' => [
                'page' => $campaignPage,
                'per_page' => $campaignsPerPage,
                'total' => $campaignTotal,
                'total_pages' => $campaignTotalPages,
            ],
        ];
        break;
    case 'create-campaign':
        $campaignId = (int) ($_GET['campaign_id'] ?? 0);
        $data = [
            'campaign' => $campaignId ? fetch_campaign($campaignId) : null,
            'events' => $campaignId ? fetch_campaign_events($campaignId) : [],
            'smtp_configs' => fetch_smtp_configs(),
            'contact_lists' => fetch_contact_lists(),
            'test_contacts' => fetch_test_contacts(),
            'settings' => fetch_settings(),
            'templates' => fetch_email_templates_for_editor(true),
        ];
        break;
    case 'campaign-overview':
        $campaignId = (int) ($_GET['campaign_id'] ?? 0);
        $recipientQuery = trim((string) ($_GET['recipient_q'] ?? ''));
        $recipientPage = max(1, (int) ($_GET['recipients_page'] ?? 1));
        $recipientsPerPage = 20;
        $campaign = $campaignId ? fetch_campaign($campaignId) : null;
        $events = $campaignId ? fetch_campaign_events($campaignId) : [];
        $contactList = $campaign && !empty($campaign['contact_list_id'])
            ? fetch_contact_list((int) $campaign['contact_list_id'])
            : null;
        $deliveryStats = $campaign ? fetch_campaign_delivery_stats((int) $campaign['id']) : null;
        $deliveryTotalRows = $campaign ? count_campaign_delivery_rows((int) $campaign['id']) : 0;
        if ($deliveryTotalRows > 0) {
            $recipientTotal = count_campaign_delivery_rows((int) $campaign['id'], $recipientQuery);
            $recipientTotalPages = max(1, (int) ceil($recipientTotal / $recipientsPerPage));
            $recipientPage = min($recipientPage, $recipientTotalPages);
            $recipientOffset = ($recipientPage - 1) * $recipientsPerPage;
            $recipients = fetch_campaign_delivery_rows_paginated((int) $campaign['id'], $recipientsPerPage, $recipientOffset, $recipientQuery);
        } else {
            $recipientTotal = $contactList ? count_contacts_for_list((int) $contactList['id'], $recipientQuery) : 0;
            $recipientTotalPages = max(1, (int) ceil($recipientTotal / $recipientsPerPage));
            $recipientPage = min($recipientPage, $recipientTotalPages);
            $recipientOffset = ($recipientPage - 1) * $recipientsPerPage;
            $recipients = $contactList ? fetch_contacts_for_list_paginated((int) $contactList['id'], $recipientsPerPage, $recipientOffset, $recipientQuery) : [];
        }
        $smtpConfig = $campaign && !empty($campaign['smtp_config_id'])
            ? fetch_smtp_config((int) $campaign['smtp_config_id'])
            : null;
        $data = [
            'campaign' => $campaign,
            'events' => $events,
            'contact_list' => $contactList,
            'recipients' => $recipients,
            'smtp_config' => $smtpConfig,
            'delivery_stats' => $deliveryStats,
            'click_stats' => $campaign ? fetch_campaign_click_stats((int) $campaign['id']) : ['total_clicks' => 0, 'unique_clickers' => 0],
            'recipient_query' => $recipientQuery,
            'recipient_pagination' => [
                'page' => $recipientPage,
                'per_page' => $recipientsPerPage,
                'total' => $recipientTotal,
                'total_pages' => $recipientTotalPages,
            ],
            'overview' => campaign_overview_metrics($campaign, $contactList, $events, $deliveryStats),
        ];
        break;
    case 'configs-test':
        $data = [
            'smtp_configs' => fetch_smtp_configs(),
            'test_contacts' => fetch_test_contacts(),
            'settings' => fetch_settings(),
        ];
        break;
    case 'manage-contacts':
        $lists = fetch_contact_lists();
        $data = [
            'contact_lists' => $lists,
            'contact_list_data_points' => fetch_contact_list_data_points_map(array_column($lists, 'id')),
        ];
        break;
    case 'templates':
        $templateId = (int) ($_GET['template_id'] ?? 0);
        $data = [
            'templates' => fetch_email_templates(),
            'template' => $templateId ? fetch_email_template($templateId) : null,
            'settings' => fetch_settings(),
        ];
        break;
    default:
        $data = [];
}

render_header($routes[$page]['title'], $page);
require $routes[$page]['view'];
render_footer();
