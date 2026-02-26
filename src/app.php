<?php

declare(strict_types=1);

function app_bootstrap(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    load_env_file();
    ensure_storage();
    db();
}

function load_env_file(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $envPath = __DIR__ . '/../.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function ensure_storage(): void
{
    $storage = __DIR__ . '/../storage';
    $uploads = $storage . '/uploads';

    if (!is_dir($storage)) {
        mkdir($storage, 0755, true);
    }

    if (!is_dir($uploads)) {
        mkdir($uploads, 0755, true);
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = db_config();
    $charset = $config['charset'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $charset
    );

    try {
        $pdo = new PDO($dsn, $config['user'], $config['password']);
    } catch (PDOException $e) {
        if ((int) $e->getCode() !== 1049) {
            throw $e;
        }

        $serverDsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $config['host'],
            $config['port'],
            $charset
        );
        $serverPdo = new PDO($serverDsn, $config['user'], $config['password']);
        $serverPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $serverPdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $config['database']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $pdo = new PDO($dsn, $config['user'], $config['password']);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $hasTables = schema_has_tables($pdo);
    initialize_db($pdo);
    ensure_schema($pdo);
    if (!$hasTables) {
        seed_sample_data($pdo);
    }

    return $pdo;
}

function db_config(): array
{
    return [
        'host' => getenv('MAILR_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('MAILR_DB_PORT') ?: 3306),
        'database' => getenv('MAILR_DB_NAME') ?: 'mailr',
        'user' => getenv('MAILR_DB_USER') ?: 'root',
        'password' => getenv('MAILR_DB_PASS') ?: '',
        'charset' => getenv('MAILR_DB_CHARSET') ?: 'utf8mb4',
    ];
}

function schema_has_tables(PDO $pdo): bool
{
    $stmt = $pdo->query("SHOW TABLES");
    return (bool) $stmt->fetchColumn();
}

function initialize_db(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaigns (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            preview_text VARCHAR(255) NULL,
            from_name VARCHAR(255) NULL,
            smtp_config_id INT UNSIGNED NULL,
            audience_mode VARCHAR(32) NULL,
            contact_list_id INT UNSIGNED NULL,
            suppression_list VARCHAR(255) NULL,
            segment_filter VARCHAR(255) NULL,
            schedule_at DATETIME NULL,
            send_window VARCHAR(64) NULL,
            tracking VARCHAR(64) NULL,
            html_content TEXT,
            text_content TEXT,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_campaigns_updated_at (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS smtp_configs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            host VARCHAR(255) NOT NULL,
            port INT UNSIGNED NULL,
            username VARCHAR(255) NULL,
            password TEXT,
            encryption VARCHAR(16) NULL,
            from_address VARCHAR(255) NOT NULL,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_lists (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            count INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contacts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            list_id INT UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NULL,
            last_name VARCHAR(255) NULL,
            tags VARCHAR(255) NULL,
            custom_fields MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_contacts_list_id (list_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS test_contacts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            name VARCHAR(255) NULL,
            notes VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(191) NOT NULL PRIMARY KEY,
            `value` TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaign_events (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            type VARCHAR(64) NOT NULL,
            details TEXT,
            created_at DATETIME NOT NULL,
            INDEX idx_campaign_events_campaign_id (campaign_id),
            INDEX idx_campaign_events_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaign_recipient_deliveries (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            contact_id INT UNSIGNED NOT NULL,
            list_id INT UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NULL,
            last_name VARCHAR(255) NULL,
            tags VARCHAR(255) NULL,
            custom_fields MEDIUMTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'queued',
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_error TEXT NULL,
            next_attempt_at DATETIME NULL,
            last_attempt_at DATETIME NULL,
            click_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_clicked_at DATETIME NULL,
            open_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_opened_at DATETIME NULL,
            queued_at DATETIME NOT NULL,
            sent_at DATETIME NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_campaign_contact (campaign_id, contact_id),
            INDEX idx_crd_campaign_status (campaign_id, status),
            INDEX idx_crd_campaign_updated (campaign_id, updated_at),
            INDEX idx_crd_campaign_next_attempt (campaign_id, next_attempt_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS suppressed_contacts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            reason VARCHAR(64) NOT NULL,
            source VARCHAR(64) NULL,
            details TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_suppressed_email (email),
            INDEX idx_suppressed_reason (reason)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaign_click_events (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            delivery_id INT UNSIGNED NOT NULL,
            contact_id INT UNSIGNED NULL,
            email VARCHAR(255) NULL,
            url TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_click_campaign_created (campaign_id, created_at),
            INDEX idx_click_delivery_created (delivery_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS email_templates (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NULL,
            description VARCHAR(255) NULL,
            source_text MEDIUMTEXT NULL,
            html_content MEDIUMTEXT NULL,
            text_content MEDIUMTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'Active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_email_templates_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

function ensure_schema(PDO $pdo): void
{
    if (!mysql_column_exists($pdo, 'campaigns', 'html_content')) {
        $pdo->exec("ALTER TABLE campaigns ADD COLUMN html_content TEXT;");
    }

    if (!mysql_column_exists($pdo, 'campaigns', 'text_content')) {
        $pdo->exec("ALTER TABLE campaigns ADD COLUMN text_content TEXT;");
    }

    if (!mysql_column_exists($pdo, 'smtp_configs', 'password')) {
        $pdo->exec("ALTER TABLE smtp_configs ADD COLUMN password TEXT;");
    }
    if (!mysql_column_exists($pdo, 'smtp_configs', 'encryption')) {
        $pdo->exec("ALTER TABLE smtp_configs ADD COLUMN encryption VARCHAR(16) NULL;");
    }

    if (!mysql_table_exists($pdo, 'campaign_recipient_deliveries')) {
        $pdo->exec(
            "CREATE TABLE campaign_recipient_deliveries (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                campaign_id INT UNSIGNED NOT NULL,
                contact_id INT UNSIGNED NOT NULL,
                list_id INT UNSIGNED NOT NULL,
                email VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) NULL,
                last_name VARCHAR(255) NULL,
                tags VARCHAR(255) NULL,
                custom_fields MEDIUMTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'queued',
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                last_error TEXT NULL,
                next_attempt_at DATETIME NULL,
                last_attempt_at DATETIME NULL,
                click_count INT UNSIGNED NOT NULL DEFAULT 0,
                last_clicked_at DATETIME NULL,
                open_count INT UNSIGNED NOT NULL DEFAULT 0,
                last_opened_at DATETIME NULL,
                queued_at DATETIME NOT NULL,
                sent_at DATETIME NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_campaign_contact (campaign_id, contact_id),
                INDEX idx_crd_campaign_status (campaign_id, status),
                INDEX idx_crd_campaign_updated (campaign_id, updated_at),
                INDEX idx_crd_campaign_next_attempt (campaign_id, next_attempt_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
    }

    foreach ([
        ['contacts', 'custom_fields', "ALTER TABLE contacts ADD COLUMN custom_fields MEDIUMTEXT NULL;"],
        ['campaign_recipient_deliveries', 'custom_fields', "ALTER TABLE campaign_recipient_deliveries ADD COLUMN custom_fields MEDIUMTEXT NULL;"],
        ['campaign_recipient_deliveries', 'next_attempt_at', "ALTER TABLE campaign_recipient_deliveries ADD COLUMN next_attempt_at DATETIME NULL;"],
        ['campaign_recipient_deliveries', 'last_attempt_at', "ALTER TABLE campaign_recipient_deliveries ADD COLUMN last_attempt_at DATETIME NULL;"],
        ['campaign_recipient_deliveries', 'click_count', "ALTER TABLE campaign_recipient_deliveries ADD COLUMN click_count INT UNSIGNED NOT NULL DEFAULT 0;"],
        ['campaign_recipient_deliveries', 'last_clicked_at', "ALTER TABLE campaign_recipient_deliveries ADD COLUMN last_clicked_at DATETIME NULL;"],
        ['campaign_recipient_deliveries', 'open_count', "ALTER TABLE campaign_recipient_deliveries ADD COLUMN open_count INT UNSIGNED NOT NULL DEFAULT 0;"],
        ['campaign_recipient_deliveries', 'last_opened_at', "ALTER TABLE campaign_recipient_deliveries ADD COLUMN last_opened_at DATETIME NULL;"],
    ] as $migration) {
        if (!mysql_column_exists($pdo, (string) $migration[0], (string) $migration[1])) {
            $pdo->exec((string) $migration[2]);
        }
    }

    if (!mysql_table_exists($pdo, 'suppressed_contacts')) {
        $pdo->exec(
            "CREATE TABLE suppressed_contacts (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                reason VARCHAR(64) NOT NULL,
                source VARCHAR(64) NULL,
                details TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_suppressed_email (email),
                INDEX idx_suppressed_reason (reason)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
    }

    if (!mysql_table_exists($pdo, 'campaign_click_events')) {
        $pdo->exec(
            "CREATE TABLE campaign_click_events (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                campaign_id INT UNSIGNED NOT NULL,
                delivery_id INT UNSIGNED NOT NULL,
                contact_id INT UNSIGNED NULL,
                email VARCHAR(255) NULL,
                url TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_click_campaign_created (campaign_id, created_at),
                INDEX idx_click_delivery_created (delivery_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
    }

    if (!mysql_table_exists($pdo, 'email_templates')) {
        $pdo->exec(
            "CREATE TABLE email_templates (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                category VARCHAR(100) NULL,
                description VARCHAR(255) NULL,
                source_text MEDIUMTEXT NULL,
                html_content MEDIUMTEXT NULL,
                text_content MEDIUMTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'Active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_email_templates_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
    }
}

function mysql_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name"
    );
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function mysql_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name"
    );
    $stmt->execute(['table_name' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function seed_sample_data(PDO $pdo): void
{
    $now = date('Y-m-d H:i:s');

    $pdo->exec(
        "INSERT INTO smtp_configs (name, host, port, username, password, encryption, from_address, status, created_at)
         VALUES
          ('Primary SMTP', 'smtp.mailhost.com', 587, 'hello@mailr.com', '', 'tls', 'hello@mailr.com', 'Active', '{$now}'),
          ('Backup SMTP', 'smtp.backup.com', 587, 'backup@mailr.com', '', 'tls', 'backup@mailr.com', 'Paused', '{$now}');"
    );

    $pdo->exec(
        "INSERT INTO contact_lists (name, count, updated_at)
         VALUES
          ('All Customers', 0, '{$now}'),
          ('Power Users', 0, '{$now}'),
          ('At-Risk', 0, '{$now}');"
    );

    $pdo->exec(
        "INSERT INTO contacts (list_id, email, first_name, last_name, tags, custom_fields, created_at)
         VALUES
          (1, 'ava@customer.com', 'Ava', 'Ng', 'vip', '{\"plan\":\"pro\",\"city\":\"Austin\"}', '{$now}'),
          (1, 'liam@customer.com', 'Liam', 'Diaz', 'newsletter', '{\"plan\":\"starter\",\"city\":\"Denver\"}', '{$now}'),
          (1, 'mila@customer.com', 'Mila', 'Patel', 'promo', '{\"coupon_code\":\"SAVE20\"}', '{$now}'),
          (2, 'noah@power.com', 'Noah', 'Lee', 'power', '{\"company\":\"Northstar\",\"account_manager\":\"Tori\"}', '{$now}'),
          (2, 'emma@power.com', 'Emma', 'Stone', 'power', '{\"company\":\"Acme\"}', '{$now}'),
          (3, 'oliver@risk.com', 'Oliver', 'Grant', 'winback', '{\"renewal_date\":\"2026-03-15\"}', '{$now}');"
    );

    $pdo->exec("UPDATE contact_lists SET count = (SELECT COUNT(*) FROM contacts WHERE list_id = contact_lists.id), updated_at = '{$now}';");

    $pdo->exec(
        "INSERT INTO test_contacts (email, name, notes)
         VALUES
          ('qa-team@mailr.com', 'QA Team', 'General QA list'),
          ('marketing@mailr.com', 'Marketing Leads', 'Review copy and links');"
    );

    $pdo->exec(
        "INSERT INTO campaigns (title, subject, preview_text, from_name, smtp_config_id, audience_mode, contact_list_id, suppression_list, segment_filter, schedule_at, send_window, tracking, status, created_at, updated_at)
         VALUES
          ('February Promo', 'Love the savings this week', 'Limited-time offer', 'Mailr Team', 1, 'list', 1, '', '', NULL, 'All day', 'Open + click tracking', 'Draft', '{$now}', '{$now}'),
          ('New Feature Launch', 'Meet the new dashboard', 'See what changed', 'Mailr Team', 1, 'list', 2, '', '', '2026-02-10 10:00', 'Business hours only', 'Open + click tracking', 'Scheduled', '{$now}', '{$now}'),
          ('Churn Save', 'We saved you a seat', 'A win-back offer', 'Mailr Team', 2, 'list', 3, '', '', NULL, 'Evening only', 'Click only', 'Sent', '{$now}', '{$now}');"
    );

    $pdo->exec(
        "INSERT INTO settings (`key`, `value`)
         VALUES
          ('default_from_name', 'Mailr Team'),
          ('reply_to', 'reply@mailr.com'),
          ('timezone', 'America/New_York'),
          ('default_tracking', 'Open + click tracking');"
    );

    $pdo->exec(
        "INSERT INTO email_templates (name, category, description, source_text, html_content, text_content, status, created_at, updated_at)
         VALUES
          ('Starter Promo', 'Marketing', 'Simple promo layout with CTA and bullets', 'Write a promotional email for a weekly update. Include a short intro, bullet list, and CTA button.', '<h2>Hello {{first_name}},</h2><p>We saved a spot for you in this week''s campaign.</p><p><strong>What''s new:</strong></p><ul><li>Fresh dashboard insights</li><li>Automated follow-ups</li><li>Live delivery checks</li></ul><p><a href=\"{{cta_url}}\">Explore the update</a></p><p>-- Mailr Team</p>', 'Hello {{first_name}},\n\nWe saved a spot for you in this week''s campaign.\n\nWhat''s new:\n- Fresh dashboard insights\n- Automated follow-ups\n- Live delivery checks\n\nExplore the update: {{cta_url}}\n\n-- Mailr Team', 'Active', '{$now}', '{$now}'),
          ('Event Invite', 'Events', 'Invite template with time and CTA', 'Write an event invitation email with date/time, highlights, and registration CTA.', '<h2>You''re invited</h2><p>{{first_name}}, join us for a 30-minute walkthrough of Mailr''s latest updates.</p><p><strong>When:</strong> Thursday at 2pm ET</p><p><strong>Where:</strong> Live webinar</p><p><a href=\"{{cta_url}}\">Reserve your seat</a></p>', 'You''re invited\n\n{{first_name}}, join us for a 30-minute walkthrough of Mailr''s latest updates.\n\nWhen: Thursday at 2pm ET\nWhere: Live webinar\n\nReserve your seat: {{cta_url}}', 'Active', '{$now}', '{$now}');"
    );
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function app_auth_username(): string
{
    return trim((string) (getenv('MAILR_ADMIN_USER') ?: 'admin'));
}

function app_auth_password_plain(): string
{
    return (string) (getenv('MAILR_ADMIN_PASS') ?: 'admin123');
}

function app_auth_password_hash(): string
{
    return trim((string) (getenv('MAILR_ADMIN_PASSWORD_HASH') ?: ''));
}

function auth_is_authenticated(): bool
{
    return !empty($_SESSION['mailr_auth']['ok']) && ($_SESSION['mailr_auth']['ok'] === true);
}

function auth_login(string $username): void
{
    session_regenerate_id(true);
    $_SESSION['mailr_auth'] = [
        'ok' => true,
        'username' => $username,
        'logged_in_at' => time(),
    ];
}

function auth_logout(): void
{
    unset($_SESSION['mailr_auth']);
}

function auth_attempt_login(string $username, string $password): bool
{
    $expectedUser = app_auth_username();
    if (!hash_equals($expectedUser, $username)) {
        return false;
    }

    $hash = app_auth_password_hash();
    if ($hash !== '') {
        return password_verify($password, $hash);
    }

    return hash_equals(app_auth_password_plain(), $password);
}

function csrf_token(): string
{
    if (empty($_SESSION['mailr_csrf']) || !is_string($_SESSION['mailr_csrf'])) {
        $_SESSION['mailr_csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['mailr_csrf'];
}

function csrf_validate(?string $token): bool
{
    $sessionToken = (string) ($_SESSION['mailr_csrf'] ?? '');
    $token = (string) ($token ?? '');
    if ($sessionToken === '' || $token === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

function csrf_enforce(): void
{
    $token = (string) ($_POST['_csrf'] ?? '');
    if (csrf_validate($token)) {
        return;
    }
    if (is_ajax_request()) {
        json_response(['ok' => false, 'message' => 'Invalid or missing CSRF token. Refresh the page and retry.'], 419);
    }
    http_response_code(419);
    echo 'Invalid or missing CSRF token. Refresh the page and retry.';
    exit;
}

function require_auth_or_redirect(?string $next = null): void
{
    if (auth_is_authenticated()) {
        return;
    }
    $target = '/index.php?page=login';
    if ($next !== null && $next !== '') {
        $target .= '&next=' . rawurlencode($next);
    }
    redirect_to($target);
}

function handle_login(): void
{
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!csrf_validate((string) ($_POST['_csrf'] ?? ''))) {
        flash_set('error', 'Session expired. Refresh and try again.');
        redirect_to('/index.php?page=login');
    }
    if (!auth_attempt_login($username, $password)) {
        flash_set('error', 'Invalid credentials.');
        redirect_to('/index.php?page=login');
    }
    auth_login($username);
    $next = trim((string) ($_POST['next'] ?? ''));
    if ($next === '' || str_starts_with($next, 'http://') || str_starts_with($next, 'https://')) {
        $next = '/index.php?page=dashboard';
    }
    redirect_to($next);
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirect_to(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function is_ajax_request(): bool
{
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return strtolower((string) $requestedWith) === 'xmlhttprequest';
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function fetch_campaigns(): array
{
    $stmt = db()->query(
        "SELECT campaigns.*, contact_lists.name AS list_name
         FROM campaigns
         LEFT JOIN contact_lists ON campaigns.contact_list_id = contact_lists.id
         ORDER BY campaigns.updated_at DESC"
    );

    return $stmt->fetchAll();
}

function fetch_campaign(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT campaigns.*, contact_lists.name AS list_name
         FROM campaigns
         LEFT JOIN contact_lists ON campaigns.contact_list_id = contact_lists.id
         WHERE campaigns.id = :id"
    );
    $stmt->execute(['id' => $id]);
    $campaign = $stmt->fetch();
    return $campaign ?: null;
}

function fetch_campaign_events(int $campaignId): array
{
    $stmt = db()->prepare(
        "SELECT * FROM campaign_events WHERE campaign_id = :id ORDER BY created_at DESC LIMIT 8"
    );
    $stmt->execute(['id' => $campaignId]);
    return $stmt->fetchAll();
}

function fetch_contacts_for_list(int $listId, int $limit = 200): array
{
    $stmt = db()->prepare(
        "SELECT id, email, first_name, last_name, tags, custom_fields, created_at
         FROM contacts
         WHERE list_id = :list_id
         ORDER BY created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':list_id', $listId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['custom_fields'] = contact_custom_fields_array($row['custom_fields'] ?? null);
    }
    unset($row);
    return $rows;
}

function fetch_contact_list(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM contact_lists WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $list = $stmt->fetch();
    return $list ?: null;
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function normalize_placeholder_key(string $key): string
{
    $key = strtolower(trim($key));
    $key = preg_replace('/[^a-z0-9_]+/', '_', $key);
    $key = preg_replace('/_+/', '_', (string) $key);
    return trim((string) $key, '_');
}

function contact_custom_fields_array(mixed $raw): array
{
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    $normalized = [];
    foreach ($decoded as $k => $v) {
        $key = normalize_placeholder_key((string) $k);
        if ($key === '') {
            continue;
        }
        if (is_scalar($v) || $v === null) {
            $normalized[$key] = $v === null ? '' : (string) $v;
            continue;
        }
        $encoded = json_encode($v, JSON_UNESCAPED_SLASHES);
        $normalized[$key] = $encoded !== false ? $encoded : '';
    }
    return $normalized;
}

function tracking_secret(): string
{
    $secret = (string) (getenv('MAILR_TRACKING_SECRET') ?: getenv('APP_KEY') ?: '');
    if ($secret === '') {
        $dbName = (string) (db_config()['database'] ?? 'mailr');
        $secret = hash('sha256', 'mailr-default-secret-' . $dbName);
    }
    return $secret;
}

function sign_tracking_params(array $params): string
{
    ksort($params);
    return hash_hmac('sha256', http_build_query($params, '', '&', PHP_QUERY_RFC3986), tracking_secret());
}

function verify_tracking_signature(array $params, string $signature): bool
{
    $expected = sign_tracking_params($params);
    return hash_equals($expected, $signature);
}

function base_app_url(): string
{
    $configured = trim((string) (getenv('MAILR_BASE_URL') ?: ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    if (PHP_SAPI !== 'cli') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');
        return $scheme . '://' . $host;
    }
    return 'http://localhost:8000';
}

function is_email_suppressed(string $email): bool
{
    $email = normalize_email($email);
    if ($email === '') {
        return false;
    }
    $stmt = db()->prepare("SELECT 1 FROM suppressed_contacts WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    return (bool) $stmt->fetchColumn();
}

function suppress_contact_email(string $email, string $reason, string $source = 'system', string $details = ''): void
{
    $email = normalize_email($email);
    if ($email === '') {
        return;
    }
    $now = date('Y-m-d H:i:s');
    $stmt = db()->prepare(
        "INSERT INTO suppressed_contacts (email, reason, source, details, created_at, updated_at)
         VALUES (:email, :reason, :source, :details, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE
            reason = VALUES(reason),
            source = VALUES(source),
            details = VALUES(details),
            updated_at = VALUES(updated_at)"
    );
    $stmt->execute([
        'email' => $email,
        'reason' => $reason,
        'source' => $source,
        'details' => $details,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function should_click_track(?array $campaign): bool
{
    $tracking = strtolower(trim((string) ($campaign['tracking'] ?? '')));
    return $tracking !== 'disable tracking';
}

function should_open_track(?array $campaign): bool
{
    $tracking = strtolower(trim((string) ($campaign['tracking'] ?? '')));
    return $tracking === 'open + click tracking';
}

function build_click_tracking_url(int $campaignId, int $deliveryId, string $url): string
{
    $params = [
        'action' => 'track_click',
        'cid' => $campaignId,
        'did' => $deliveryId,
        'u' => $url,
    ];
    $params['sig'] = sign_tracking_params([
        'action' => 'track_click',
        'cid' => $campaignId,
        'did' => $deliveryId,
        'u' => $url,
    ]);
    return base_app_url() . '/index.php?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function build_unsubscribe_url(int $campaignId, int $deliveryId): string
{
    $base = [
        'action' => 'unsubscribe',
        'cid' => $campaignId,
        'did' => $deliveryId,
    ];
    $base['sig'] = sign_tracking_params([
        'action' => 'unsubscribe',
        'cid' => $campaignId,
        'did' => $deliveryId,
    ]);
    return base_app_url() . '/index.php?' . http_build_query($base, '', '&', PHP_QUERY_RFC3986);
}

function build_open_tracking_url(int $campaignId, int $deliveryId): string
{
    $base = [
        'action' => 'track_open',
        'cid' => $campaignId,
        'did' => $deliveryId,
    ];
    $base['sig'] = sign_tracking_params($base);
    return base_app_url() . '/index.php?' . http_build_query($base, '', '&', PHP_QUERY_RFC3986);
}

function rewrite_email_links_for_tracking(string $html, int $campaignId, int $deliveryId, bool $appendUnsubscribe = true, bool $appendOpenPixel = false): string
{
    if (trim($html) === '') {
        return $html;
    }

    if (!class_exists('DOMDocument')) {
        return $html;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $loaded = $dom->loadHTML(
        '<!DOCTYPE html><html><body>' . $html . '</body></html>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    if (!$loaded) {
        libxml_clear_errors();
        return $html;
    }

    $anchors = $dom->getElementsByTagName('a');
    foreach ($anchors as $a) {
        if (!$a instanceof DOMElement) {
            continue;
        }
        $href = trim((string) $a->getAttribute('href'));
        if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto:|tel:|javascript:)/i', $href)) {
            continue;
        }
        if (!preg_match('/^https?:\/\//i', $href)) {
            continue;
        }
        $a->setAttribute('data-mailr-original-href', $href);
        $a->setAttribute('href', build_click_tracking_url($campaignId, $deliveryId, $href));
    }

    if ($appendUnsubscribe) {
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body instanceof DOMElement) {
            $p = $dom->createElement('p');
            $p->setAttribute('style', 'margin:16px 0 0;color:#777;font-size:12px;line-height:1.5;');
            $a = $dom->createElement('a', 'Unsubscribe');
            $a->setAttribute('href', build_unsubscribe_url($campaignId, $deliveryId));
            $a->setAttribute('style', 'color:#777;');
            $p->appendChild($dom->createTextNode('If you no longer want these emails, '));
            $p->appendChild($a);
            $p->appendChild($dom->createTextNode('.'));
            $body->appendChild($p);
        }
    }

    if ($appendOpenPixel) {
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body instanceof DOMElement) {
            $img = $dom->createElement('img');
            $img->setAttribute('src', build_open_tracking_url($campaignId, $deliveryId));
            $img->setAttribute('width', '1');
            $img->setAttribute('height', '1');
            $img->setAttribute('alt', '');
            $img->setAttribute('style', 'display:block;width:1px;height:1px;border:0;opacity:0;');
            $body->appendChild($img);
        }
    }

    $result = '';
    $bodyNode = $dom->getElementsByTagName('body')->item(0);
    if ($bodyNode) {
        foreach ($bodyNode->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
    }
    libxml_clear_errors();
    return $result !== '' ? $result : $html;
}

function parse_retry_policy(): array
{
    return [
        'max_attempts' => max(1, (int) (getenv('MAILR_RETRY_MAX_ATTEMPTS') ?: 4)),
        'base_delay_seconds' => max(5, (int) (getenv('MAILR_RETRY_BASE_DELAY_SECONDS') ?: 60)),
        'max_delay_seconds' => max(60, (int) (getenv('MAILR_RETRY_MAX_DELAY_SECONDS') ?: 3600)),
    ];
}

function next_retry_time(int $attempts): string
{
    $policy = parse_retry_policy();
    $exp = max(0, $attempts - 1);
    $delay = min($policy['max_delay_seconds'], $policy['base_delay_seconds'] * (2 ** $exp));
    return date('Y-m-d H:i:s', time() + $delay);
}

function smtp_send_counts_recent(int $smtpConfigId): array
{
    $stmt = db()->prepare(
        "SELECT
            SUM(CASE WHEN crd.sent_at >= DATE_SUB(:now_ts, INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) AS minute_count,
            SUM(CASE WHEN crd.sent_at >= DATE_SUB(:now_ts, INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS hour_count
         FROM campaign_recipient_deliveries crd
         INNER JOIN campaigns c ON c.id = crd.campaign_id
         WHERE c.smtp_config_id = :smtp_config_id
           AND crd.status = 'sent'
           AND crd.sent_at IS NOT NULL"
    );
    $now = date('Y-m-d H:i:s');
    $stmt->execute([
        'now_ts' => $now,
        'smtp_config_id' => $smtpConfigId,
    ]);
    $row = $stmt->fetch() ?: [];
    return [
        'minute_count' => (int) ($row['minute_count'] ?? 0),
        'hour_count' => (int) ($row['hour_count'] ?? 0),
    ];
}

function smtp_rate_limits(): array
{
    return [
        'per_minute' => max(0, (int) (getenv('MAILR_SMTP_RATE_LIMIT_PER_MINUTE') ?: 0)),
        'per_hour' => max(0, (int) (getenv('MAILR_SMTP_RATE_LIMIT_PER_HOUR') ?: 0)),
    ];
}

function smtp_rate_limit_wait_seconds(int $smtpConfigId): int
{
    $limits = smtp_rate_limits();
    if ($limits['per_minute'] <= 0 && $limits['per_hour'] <= 0) {
        return 0;
    }
    $counts = smtp_send_counts_recent($smtpConfigId);
    if ($limits['per_minute'] > 0 && $counts['minute_count'] >= $limits['per_minute']) {
        return 60;
    }
    if ($limits['per_hour'] > 0 && $counts['hour_count'] >= $limits['per_hour']) {
        return 3600;
    }
    return 0;
}

function classify_smtp_failure_reason(string $message): ?string
{
    $m = strtolower($message);
    foreach ([
        'user unknown',
        'unknown user',
        'mailbox unavailable',
        'no such user',
        'recipient address rejected',
        'invalid recipient',
    ] as $pattern) {
        if (str_contains($m, $pattern)) {
            return 'bounce';
        }
    }
    return null;
}

function db_named_lock(string $name, int $timeoutSeconds = 1): bool
{
    $stmt = db()->prepare("SELECT GET_LOCK(:lock_name, :timeout_sec) AS lock_ok");
    $stmt->bindValue(':lock_name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':timeout_sec', $timeoutSeconds, PDO::PARAM_INT);
    $stmt->execute();
    return (int) ($stmt->fetchColumn() ?: 0) === 1;
}

function db_named_unlock(string $name): void
{
    $stmt = db()->prepare("SELECT RELEASE_LOCK(:lock_name)");
    $stmt->execute(['lock_name' => $name]);
}

function fetch_delivery_row(int $deliveryId): ?array
{
    $stmt = db()->prepare("SELECT * FROM campaign_recipient_deliveries WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $deliveryId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function log_campaign_click(int $campaignId, array $deliveryRow, string $url): void
{
    $now = date('Y-m-d H:i:s');
    $pdo = db();
    $stmt = $pdo->prepare(
        "INSERT INTO campaign_click_events (campaign_id, delivery_id, contact_id, email, url, created_at)
         VALUES (:campaign_id, :delivery_id, :contact_id, :email, :url, :created_at)"
    );
    $stmt->execute([
        'campaign_id' => $campaignId,
        'delivery_id' => (int) ($deliveryRow['id'] ?? 0),
        'contact_id' => (int) ($deliveryRow['contact_id'] ?? 0),
        'email' => (string) ($deliveryRow['email'] ?? ''),
        'url' => $url,
        'created_at' => $now,
    ]);

    $update = $pdo->prepare(
        "UPDATE campaign_recipient_deliveries
         SET click_count = click_count + 1, last_clicked_at = :clicked_at, updated_at = :updated_at
         WHERE id = :id"
    );
    $update->execute([
        'clicked_at' => $now,
        'updated_at' => $now,
        'id' => (int) ($deliveryRow['id'] ?? 0),
    ]);
}

function log_campaign_open(int $campaignId, array $deliveryRow): void
{
    $now = date('Y-m-d H:i:s');
    $stmt = db()->prepare(
        "UPDATE campaign_recipient_deliveries
         SET open_count = open_count + 1, last_opened_at = :opened_at, updated_at = :updated_at
         WHERE id = :id AND campaign_id = :campaign_id"
    );
    $stmt->execute([
        'opened_at' => $now,
        'updated_at' => $now,
        'id' => (int) ($deliveryRow['id'] ?? 0),
        'campaign_id' => $campaignId,
    ]);
}

function fetch_campaign_click_stats(int $campaignId): array
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) AS total_clicks, COUNT(DISTINCT delivery_id) AS unique_clickers
         FROM campaign_click_events
         WHERE campaign_id = :campaign_id"
    );
    $stmt->execute(['campaign_id' => $campaignId]);
    $row = $stmt->fetch() ?: [];
    return [
        'total_clicks' => (int) ($row['total_clicks'] ?? 0),
        'unique_clickers' => (int) ($row['unique_clickers'] ?? 0),
    ];
}

function pause_campaign_delivery(int $campaignId): void
{
    $campaign = fetch_campaign($campaignId);
    if (!$campaign) {
        return;
    }
    $campaign['status'] = 'Paused';
    $campaign['updated_at'] = date('Y-m-d H:i:s');
    update_campaign($campaignId, $campaign);
    add_campaign_event($campaignId, 'paused', 'Campaign delivery paused.');
}

function resume_campaign_delivery(int $campaignId): void
{
    $campaign = fetch_campaign($campaignId);
    if (!$campaign) {
        return;
    }
    $stats = fetch_campaign_delivery_stats($campaignId);
    $remaining = (int) (($stats['queued_count'] ?? 0) + ($stats['processing_count'] ?? 0));
    $campaign['status'] = $remaining > 0 ? 'Scheduled' : 'Sent';
    $campaign['updated_at'] = date('Y-m-d H:i:s');
    update_campaign($campaignId, $campaign);
    add_campaign_event($campaignId, 'resumed', 'Campaign delivery resumed.');
}

function handle_track_click(): void
{
    $campaignId = (int) ($_GET['cid'] ?? 0);
    $deliveryId = (int) ($_GET['did'] ?? 0);
    $url = trim((string) ($_GET['u'] ?? ''));
    $sig = trim((string) ($_GET['sig'] ?? ''));
    if ($campaignId <= 0 || $deliveryId <= 0 || $url === '' || $sig === '') {
        http_response_code(400);
        echo 'Invalid tracking link.';
        exit;
    }

    $params = [
        'action' => 'track_click',
        'cid' => $campaignId,
        'did' => $deliveryId,
        'u' => $url,
    ];
    if (!verify_tracking_signature($params, $sig)) {
        http_response_code(403);
        echo 'Invalid signature.';
        exit;
    }
    $url = sanitize_header_value($url);
    if (!preg_match('/^https?:\/\//i', $url)) {
        http_response_code(400);
        echo 'Invalid URL.';
        exit;
    }
    $delivery = fetch_delivery_row($deliveryId);
    if ($delivery && (int) ($delivery['campaign_id'] ?? 0) === $campaignId) {
        log_campaign_click($campaignId, $delivery, $url);
    }
    header('Location: ' . $url, true, 302);
    exit;
}

function handle_unsubscribe(): void
{
    $campaignId = (int) ($_GET['cid'] ?? 0);
    $deliveryId = (int) ($_GET['did'] ?? 0);
    $sig = trim((string) ($_GET['sig'] ?? ''));
    if ($campaignId <= 0 || $deliveryId <= 0 || $sig === '') {
        http_response_code(400);
        echo 'Invalid unsubscribe link.';
        exit;
    }
    $params = [
        'action' => 'unsubscribe',
        'cid' => $campaignId,
        'did' => $deliveryId,
    ];
    if (!verify_tracking_signature($params, $sig)) {
        http_response_code(403);
        echo 'Invalid signature.';
        exit;
    }
    $delivery = fetch_delivery_row($deliveryId);
    if ($delivery && (int) ($delivery['campaign_id'] ?? 0) === $campaignId) {
        $email = (string) ($delivery['email'] ?? '');
        suppress_contact_email($email, 'unsubscribe', 'recipient', 'One-click unsubscribe');
        add_campaign_event($campaignId, 'unsubscribe', 'Recipient unsubscribed: ' . $email);
        echo '<!doctype html><html><body style="font-family:Arial,sans-serif;padding:24px;"><h2>Unsubscribed</h2><p>Your email has been unsubscribed from future sends.</p></body></html>';
        exit;
    }
    http_response_code(404);
    echo 'Recipient not found.';
    exit;
}

function output_tracking_pixel(): void
{
    $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==', true);
    header('Content-Type: image/gif');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $gif !== false ? $gif : '';
    exit;
}

function handle_track_open(): void
{
    $campaignId = (int) ($_GET['cid'] ?? 0);
    $deliveryId = (int) ($_GET['did'] ?? 0);
    $sig = trim((string) ($_GET['sig'] ?? ''));
    if ($campaignId <= 0 || $deliveryId <= 0 || $sig === '') {
        output_tracking_pixel();
    }
    $params = [
        'action' => 'track_open',
        'cid' => $campaignId,
        'did' => $deliveryId,
    ];
    if (!verify_tracking_signature($params, $sig)) {
        output_tracking_pixel();
    }
    $delivery = fetch_delivery_row($deliveryId);
    if ($delivery && (int) ($delivery['campaign_id'] ?? 0) === $campaignId) {
        log_campaign_open($campaignId, $delivery);
    }
    output_tracking_pixel();
}

function queue_campaign_recipients(int $campaignId, int $listId): int
{
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        "INSERT INTO campaign_recipient_deliveries
            (campaign_id, contact_id, list_id, email, first_name, last_name, tags, custom_fields, status, attempts, last_error, queued_at, sent_at, updated_at)
         SELECT
            :campaign_id, c.id, c.list_id, c.email, c.first_name, c.last_name, c.tags, c.custom_fields,
            'queued', 0, NULL, :queued_now, NULL, :updated_now
         FROM contacts c
         LEFT JOIN suppressed_contacts sc ON sc.email = LOWER(TRIM(c.email))
         WHERE c.list_id = :list_id
           AND sc.id IS NULL
         ON DUPLICATE KEY UPDATE
            list_id = VALUES(list_id),
            email = VALUES(email),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            tags = VALUES(tags),
            custom_fields = VALUES(custom_fields),
            updated_at = VALUES(updated_at),
            status = IF(campaign_recipient_deliveries.status = 'sent', campaign_recipient_deliveries.status, 'queued'),
            last_error = IF(campaign_recipient_deliveries.status = 'sent', campaign_recipient_deliveries.last_error, NULL),
            next_attempt_at = NULL"
    );
    $stmt->execute([
        'campaign_id' => $campaignId,
        'list_id' => $listId,
        'queued_now' => $now,
        'updated_now' => $now,
    ]);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM campaign_recipient_deliveries WHERE campaign_id = :campaign_id");
    $countStmt->execute(['campaign_id' => $campaignId]);
    return (int) $countStmt->fetchColumn();
}

function fetch_campaign_delivery_rows(int $campaignId, int $limit = 250): array
{
    $stmt = db()->prepare(
        "SELECT * FROM campaign_recipient_deliveries
         WHERE campaign_id = :campaign_id
         ORDER BY updated_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['custom_fields'] = contact_custom_fields_array($row['custom_fields'] ?? null);
    }
    unset($row);
    return $rows;
}

function count_campaign_delivery_rows(int $campaignId, string $search = ''): int
{
    $where = "campaign_id = :campaign_id";
    $params = ['campaign_id' => $campaignId];
    if ($search !== '') {
        $where .= " AND (
            email LIKE :search_email OR first_name LIKE :search_first_name OR last_name LIKE :search_last_name OR tags LIKE :search_tags OR status LIKE :search_status OR last_error LIKE :search_last_error
        )";
        $like = '%' . $search . '%';
        $params['search_email'] = $like;
        $params['search_first_name'] = $like;
        $params['search_last_name'] = $like;
        $params['search_tags'] = $like;
        $params['search_status'] = $like;
        $params['search_last_error'] = $like;
    }
    $stmt = db()->prepare("SELECT COUNT(*) FROM campaign_recipient_deliveries WHERE {$where}");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function fetch_campaign_delivery_rows_paginated(int $campaignId, int $limit, int $offset, string $search = ''): array
{
    $where = "campaign_id = :campaign_id";
    if ($search !== '') {
        $where .= " AND (
            email LIKE :search_email OR first_name LIKE :search_first_name OR last_name LIKE :search_last_name OR tags LIKE :search_tags OR status LIKE :search_status OR last_error LIKE :search_last_error
        )";
    }
    $stmt = db()->prepare(
        "SELECT * FROM campaign_recipient_deliveries
         WHERE {$where}
         ORDER BY updated_at DESC, id DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt->bindValue(':search_email', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_first_name', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_last_name', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_tags', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_status', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_last_error', $like, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['custom_fields'] = contact_custom_fields_array($row['custom_fields'] ?? null);
    }
    unset($row);
    return $rows;
}

function count_contacts_for_list(int $listId, string $search = ''): int
{
    $where = "list_id = :list_id";
    if ($search !== '') {
        $where .= " AND (
            email LIKE :search_email OR first_name LIKE :search_first_name OR last_name LIKE :search_last_name OR tags LIKE :search_tags OR custom_fields LIKE :search_custom_fields
        )";
    }
    $stmt = db()->prepare("SELECT COUNT(*) FROM contacts WHERE {$where}");
    $stmt->bindValue(':list_id', $listId, PDO::PARAM_INT);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt->bindValue(':search_email', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_first_name', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_last_name', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_tags', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_custom_fields', $like, PDO::PARAM_STR);
    }
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function fetch_contacts_for_list_paginated(int $listId, int $limit, int $offset, string $search = ''): array
{
    $where = "list_id = :list_id";
    if ($search !== '') {
        $where .= " AND (
            email LIKE :search_email OR first_name LIKE :search_first_name OR last_name LIKE :search_last_name OR tags LIKE :search_tags OR custom_fields LIKE :search_custom_fields
        )";
    }
    $stmt = db()->prepare(
        "SELECT id, email, first_name, last_name, tags, custom_fields, created_at
         FROM contacts
         WHERE {$where}
         ORDER BY created_at DESC, id DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':list_id', $listId, PDO::PARAM_INT);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt->bindValue(':search_email', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_first_name', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_last_name', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_tags', $like, PDO::PARAM_STR);
        $stmt->bindValue(':search_custom_fields', $like, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['custom_fields'] = contact_custom_fields_array($row['custom_fields'] ?? null);
        $row['open_count'] = 0;
        $row['click_count'] = 0;
    }
    unset($row);
    return $rows;
}

function fetch_campaign_engagement_stats_map(array $campaignIds): array
{
    $ids = array_values(array_filter(array_map('intval', $campaignIds)));
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT campaign_id,
                   COUNT(*) AS total_recipients,
                   SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_count,
                   SUM(CASE WHEN open_count > 0 THEN 1 ELSE 0 END) AS unique_openers,
                   SUM(CASE WHEN click_count > 0 THEN 1 ELSE 0 END) AS unique_clickers
            FROM campaign_recipient_deliveries
            WHERE campaign_id IN ($placeholders)
            GROUP BY campaign_id";
    $stmt = db()->prepare($sql);
    $stmt->execute($ids);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $campaignId = (int) ($row['campaign_id'] ?? 0);
        $sent = (int) ($row['sent_count'] ?? 0);
        $openers = (int) ($row['unique_openers'] ?? 0);
        $clickers = (int) ($row['unique_clickers'] ?? 0);
        $map[$campaignId] = [
            'total_recipients' => (int) ($row['total_recipients'] ?? 0),
            'sent_count' => $sent,
            'unique_openers' => $openers,
            'unique_clickers' => $clickers,
            'open_rate' => $sent > 0 ? ($openers / $sent) : 0.0,
            'click_rate' => $sent > 0 ? ($clickers / $sent) : 0.0,
        ];
    }
    return $map;
}

function fetch_campaign_delivery_stats(int $campaignId): array
{
    $stmt = db()->prepare(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_count,
            SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued_count,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
            SUM(CASE WHEN status IN ('sent','failed') THEN 1 ELSE 0 END) AS processed_count,
            SUM(CASE WHEN open_count > 0 THEN 1 ELSE 0 END) AS open_unique_count,
            SUM(COALESCE(open_count, 0)) AS open_total_count,
            SUM(CASE WHEN click_count > 0 THEN 1 ELSE 0 END) AS click_unique_count,
            SUM(COALESCE(click_count, 0)) AS click_total_count
         FROM campaign_recipient_deliveries
         WHERE campaign_id = :campaign_id"
    );
    $stmt->execute(['campaign_id' => $campaignId]);
    $row = $stmt->fetch() ?: [];

    $sentCount = (int) ($row['sent_count'] ?? 0);
    $openUniqueCount = (int) ($row['open_unique_count'] ?? 0);
    $clickUniqueCount = (int) ($row['click_unique_count'] ?? 0);

    return [
        'total' => (int) ($row['total'] ?? 0),
        'sent_count' => $sentCount,
        'queued_count' => (int) ($row['queued_count'] ?? 0),
        'processing_count' => (int) ($row['processing_count'] ?? 0),
        'failed_count' => (int) ($row['failed_count'] ?? 0),
        'processed_count' => (int) ($row['processed_count'] ?? 0),
        'open_unique_count' => $openUniqueCount,
        'open_total_count' => (int) ($row['open_total_count'] ?? 0),
        'click_unique_count' => $clickUniqueCount,
        'click_total_count' => (int) ($row['click_total_count'] ?? 0),
        'open_rate' => $sentCount > 0 ? ($openUniqueCount / $sentCount) : 0.0,
        'click_rate' => $sentCount > 0 ? ($clickUniqueCount / $sentCount) : 0.0,
    ];
}

function process_campaign_delivery(int $campaignId, int $batchSize = 50, int $delayMs = 150, ?int $maxRecipients = null): array
{
    $campaignLock = 'mailr_campaign_send_' . $campaignId;
    if (!db_named_lock($campaignLock, 0)) {
        return ['ok' => false, 'message' => 'Campaign is already being processed by another worker.', 'sent' => 0, 'failed' => 0, 'processed' => 0, 'errors' => []];
    }

    try {
    $campaign = fetch_campaign($campaignId);
    if (!$campaign) {
        return ['ok' => false, 'message' => 'Campaign not found.', 'sent' => 0, 'failed' => 0, 'processed' => 0];
    }
    if ((string) ($campaign['status'] ?? '') === 'Paused') {
        return ['ok' => false, 'message' => 'Campaign is paused.', 'sent' => 0, 'failed' => 0, 'processed' => 0, 'errors' => [], 'stats' => fetch_campaign_delivery_stats($campaignId)];
    }

    $smtpConfigId = (int) ($campaign['smtp_config_id'] ?? 0);
    $smtpConfig = $smtpConfigId > 0 ? fetch_smtp_config($smtpConfigId) : null;
    if (!$smtpConfig) {
        return ['ok' => false, 'message' => 'SMTP configuration missing.', 'sent' => 0, 'failed' => 0, 'processed' => 0];
    }

    $htmlBody = (string) ($campaign['html_content'] ?? '');
    if ($htmlBody === '') {
        return ['ok' => false, 'message' => 'Campaign email content is empty.', 'sent' => 0, 'failed' => 0, 'processed' => 0];
    }
    $textBody = (string) ($campaign['text_content'] ?? '');
    $subject = (string) ($campaign['subject'] ?? '');
    $fromEmail = (string) ($smtpConfig['from_address'] ?? '');
    $fromName = trim((string) ($campaign['from_name'] ?? ''));
    if ($fromName === '') {
        $fromName = 'Mailr';
    }

    $pdo = db();
    $totalSent = 0;
    $totalFailed = 0;
    $processed = 0;
    $errors = [];
    $retryPolicy = parse_retry_policy();
    $rateLimited = false;

    while (true) {
        $campaign = fetch_campaign($campaignId);
        if (!$campaign || (string) ($campaign['status'] ?? '') === 'Paused') {
            break;
        }
        $limit = $maxRecipients !== null ? min($batchSize, max(0, $maxRecipients - $processed)) : $batchSize;
        if ($limit <= 0) {
            break;
        }

        $waitSeconds = smtp_rate_limit_wait_seconds($smtpConfigId);
        if ($waitSeconds > 0) {
            $rateLimited = true;
            break;
        }

        $select = $pdo->prepare(
            "SELECT * FROM campaign_recipient_deliveries
             WHERE campaign_id = :campaign_id
               AND status IN ('queued', 'failed')
               AND attempts < :max_attempts
               AND (next_attempt_at IS NULL OR next_attempt_at <= :now_ts)
             ORDER BY id ASC
             LIMIT :limit"
        );
        $select->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
        $select->bindValue(':max_attempts', $retryPolicy['max_attempts'], PDO::PARAM_INT);
        $select->bindValue(':now_ts', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $select->bindValue(':limit', $limit, PDO::PARAM_INT);
        $select->execute();
        $batch = $select->fetchAll();
        if (count($batch) === 0) {
            break;
        }

        foreach ($batch as $row) {
            $deliveryId = (int) $row['id'];
            $now = date('Y-m-d H:i:s');
            $markProcessing = $pdo->prepare(
                "UPDATE campaign_recipient_deliveries
                 SET status = 'processing', attempts = attempts + 1, last_attempt_at = :last_attempt_at, updated_at = :updated_at
                 WHERE id = :id
                   AND status IN ('queued','failed')"
            );
            $markProcessing->execute([
                'last_attempt_at' => $now,
                'updated_at' => $now,
                'id' => $deliveryId,
            ]);
            if ($markProcessing->rowCount() === 0) {
                continue;
            }

            $contact = [
                'email' => (string) ($row['email'] ?? ''),
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'name' => trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? ''))),
                'custom_fields' => $row['custom_fields'] ?? null,
            ];

            if (is_email_suppressed((string) $row['email'])) {
                $update = $pdo->prepare(
                    "UPDATE campaign_recipient_deliveries
                     SET status = 'failed', last_error = :last_error, next_attempt_at = NULL, updated_at = :updated_at
                     WHERE id = :id"
                );
                $update->execute([
                    'last_error' => 'Suppressed recipient (unsubscribe/bounce).',
                    'updated_at' => $now,
                    'id' => $deliveryId,
                ]);
                $totalFailed += 1;
                $processed += 1;
                continue;
            }

            $personalSubject = personalize_content($subject, $contact);
            $personalHtml = personalize_content($htmlBody, $contact);
            $personalText = personalize_content($textBody !== '' ? $textBody : strip_tags($htmlBody), $contact);
            if (should_click_track($campaign) || should_open_track($campaign)) {
                $personalHtml = rewrite_email_links_for_tracking(
                    $personalHtml,
                    $campaignId,
                    $deliveryId,
                    should_click_track($campaign),
                    should_open_track($campaign)
                );
            }

            [$ok, $message] = smtp_send($smtpConfig, $fromEmail, $fromName, (string) $row['email'], $personalSubject, $personalHtml, $personalText);

            if ($ok) {
                $update = $pdo->prepare(
                    "UPDATE campaign_recipient_deliveries
                     SET status = 'sent', last_error = NULL, next_attempt_at = NULL, sent_at = :sent_at, updated_at = :updated_at
                     WHERE id = :id"
                );
                $update->execute([
                    'sent_at' => $now,
                    'updated_at' => $now,
                    'id' => $deliveryId,
                ]);
                $totalSent += 1;
            } else {
                $attemptsAfter = ((int) ($row['attempts'] ?? 0)) + 1;
                $retryable = $attemptsAfter < $retryPolicy['max_attempts'];
                $nextAttempt = $retryable ? next_retry_time($attemptsAfter) : null;
                $update = $pdo->prepare(
                    "UPDATE campaign_recipient_deliveries
                     SET status = 'failed',
                         last_error = :last_error,
                         next_attempt_at = :next_attempt_at,
                         updated_at = :updated_at
                     WHERE id = :id"
                );
                $update->execute([
                    'last_error' => $message,
                    'next_attempt_at' => $nextAttempt,
                    'updated_at' => $now,
                    'id' => $deliveryId,
                ]);
                $classified = classify_smtp_failure_reason($message);
                if ($classified === 'bounce') {
                    suppress_contact_email((string) $row['email'], 'bounce', 'smtp', $message);
                }
                $totalFailed += 1;
                if (count($errors) < 10) {
                    $errors[] = ((string) $row['email']) . ' (' . $message . ')';
                }
            }

            $processed += 1;
        }

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }

    $stats = fetch_campaign_delivery_stats($campaignId);
    $remaining = (int) (($stats['queued_count'] ?? 0) + ($stats['processing_count'] ?? 0));
    if ($campaign) {
        if ((string) ($campaign['status'] ?? '') !== 'Paused') {
            $payload = $campaign;
            $payload['updated_at'] = date('Y-m-d H:i:s');
            $payload['status'] = $remaining > 0 ? 'Scheduled' : 'Sent';
            update_campaign($campaignId, $payload);
        }
        if ($rateLimited) {
            add_campaign_event($campaignId, 'rate_limited', 'SMTP rate limit reached. Remaining recipients stay queued for next run.');
        }
    }

    return [
        'ok' => true,
        'message' => $rateLimited ? 'Delivery processing paused due to rate limits; remaining recipients queued.' : 'Delivery processing completed.',
        'sent' => $totalSent,
        'failed' => $totalFailed,
        'processed' => $processed,
        'errors' => $errors,
        'stats' => $stats,
    ];
    } finally {
        db_named_unlock($campaignLock);
    }
}

function process_due_scheduled_campaigns(int $batchSize = 50, int $delayMs = 150): array
{
    $stmt = db()->prepare(
        "SELECT id
         FROM campaigns
         WHERE status = 'Scheduled'
           AND (schedule_at IS NULL OR schedule_at <= :now)
         ORDER BY schedule_at ASC"
    );
    $stmt->execute(['now' => date('Y-m-d H:i:s')]);
    $campaignIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

    $results = [];
    foreach ($campaignIds as $campaignId) {
        $result = process_campaign_delivery($campaignId, $batchSize, $delayMs);
        $stats = fetch_campaign_delivery_stats($campaignId);
        if (($stats['queued_count'] ?? 0) === 0 && ($stats['processing_count'] ?? 0) === 0) {
            $campaign = fetch_campaign($campaignId);
            if ($campaign) {
                $payload = $campaign;
                $payload['updated_at'] = date('Y-m-d H:i:s');
                $payload['status'] = 'Sent';
                update_campaign($campaignId, $payload);
                add_campaign_event($campaignId, 'published', 'Scheduled campaign processed and marked Sent.');
            }
        }
        $results[] = ['campaign_id' => $campaignId, 'result' => $result];
    }

    return $results;
}

function campaign_overview_metrics(?array $campaign, ?array $contactList, array $events, ?array $deliveryStats = null): array
{
    if (!$campaign) {
        return [
            'progress_percent' => 0,
            'stage' => 'Unknown',
            'processed_recipients' => 0,
            'queued_recipients' => 0,
            'sent_recipients' => 0,
            'failed_recipients' => 0,
            'tests_sent' => 0,
        ];
    }

    $total = (int) (($deliveryStats['total'] ?? 0) ?: ($contactList['count'] ?? 0));
    $status = (string) ($campaign['status'] ?? 'Draft');
    $testsSent = 0;
    $failed = 0;
    foreach ($events as $event) {
        if (($event['type'] ?? '') !== 'test_sent') {
            continue;
        }
        $testsSent += 1;
        $details = (string) ($event['details'] ?? '');
        if (preg_match('/Failed:\s*(\d+)/i', $details, $m)) {
            $failed += (int) $m[1];
        }
    }

    if ($deliveryStats !== null && $total > 0) {
        $sentCount = (int) ($deliveryStats['sent_count'] ?? 0);
        $queuedCount = (int) ($deliveryStats['queued_count'] ?? 0);
        $processedCount = (int) ($deliveryStats['processed_count'] ?? 0);
        $failedCount = (int) ($deliveryStats['failed_count'] ?? 0);
        $progressPercent = (int) round((($processedCount > 0 ? $processedCount : $sentCount) / max($total, 1)) * 100);
        $progressPercent = max(0, min(100, $progressPercent));
        $stageName = $status === 'Sent' ? 'Delivered' : ($status === 'Scheduled' ? 'Scheduled' : 'Drafting');

        return [
            'progress_percent' => $progressPercent,
            'stage' => $stageName,
            'processed_recipients' => $processedCount,
            'queued_recipients' => $queuedCount + (int) ($deliveryStats['processing_count'] ?? 0),
            'sent_recipients' => $sentCount,
            'failed_recipients' => $failedCount,
            'tests_sent' => $testsSent,
        ];
    }

    if ($status === 'Sent') {
        return [
            'progress_percent' => 100,
            'stage' => 'Delivered',
            'processed_recipients' => $total,
            'queued_recipients' => 0,
            'sent_recipients' => $total,
            'failed_recipients' => $failed,
            'tests_sent' => $testsSent,
        ];
    }

    if ($status === 'Scheduled') {
        return [
            'progress_percent' => 72,
            'stage' => 'Scheduled',
            'processed_recipients' => 0,
            'queued_recipients' => $total,
            'sent_recipients' => 0,
            'failed_recipients' => $failed,
            'tests_sent' => $testsSent,
        ];
    }

    $readiness = 0;
    $checks = [
        (string) ($campaign['title'] ?? '') !== '',
        (string) ($campaign['subject'] ?? '') !== '',
        (string) ($campaign['html_content'] ?? '') !== '',
        (int) ($campaign['smtp_config_id'] ?? 0) > 0,
        (int) ($campaign['contact_list_id'] ?? 0) > 0 || (string) ($campaign['audience_mode'] ?? '') === 'upload',
        $testsSent > 0,
    ];
    foreach ($checks as $check) {
        if ($check) {
            $readiness += 1;
        }
    }
    $progress = (int) round(($readiness / max(count($checks), 1)) * 60);

    return [
        'progress_percent' => $progress,
        'stage' => 'Drafting',
        'processed_recipients' => 0,
        'queued_recipients' => max($total, 0),
        'sent_recipients' => 0,
        'failed_recipients' => $failed,
        'tests_sent' => $testsSent,
    ];
}

function fetch_smtp_configs(): array
{
    $stmt = db()->query("SELECT * FROM smtp_configs ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function fetch_smtp_config(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM smtp_configs WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $config = $stmt->fetch();
    return $config ?: null;
}

function fetch_contact_lists(): array
{
    $stmt = db()->query("SELECT * FROM contact_lists ORDER BY updated_at DESC");
    return $stmt->fetchAll();
}

function fetch_contact_list_data_points_map(array $listIds): array
{
    $ids = array_values(array_filter(array_map('intval', $listIds)));
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT list_id, email, first_name, last_name, tags, custom_fields
         FROM contacts
         WHERE list_id IN ($placeholders)"
    );
    $stmt->execute($ids);

    $map = [];
    foreach ($ids as $id) {
        $map[$id] = [
            'email_count' => 0,
            'first_name_count' => 0,
            'last_name_count' => 0,
            'tags_count' => 0,
            'custom_fields_count' => 0,
            'custom_keys' => [],
        ];
    }

    foreach ($stmt->fetchAll() as $row) {
        $listId = (int) ($row['list_id'] ?? 0);
        if (!isset($map[$listId])) {
            continue;
        }
        $map[$listId]['email_count'] += trim((string) ($row['email'] ?? '')) !== '' ? 1 : 0;
        $map[$listId]['first_name_count'] += trim((string) ($row['first_name'] ?? '')) !== '' ? 1 : 0;
        $map[$listId]['last_name_count'] += trim((string) ($row['last_name'] ?? '')) !== '' ? 1 : 0;
        $map[$listId]['tags_count'] += trim((string) ($row['tags'] ?? '')) !== '' ? 1 : 0;
        $custom = contact_custom_fields_array($row['custom_fields'] ?? null);
        if ($custom !== []) {
            $map[$listId]['custom_fields_count'] += 1;
            foreach (array_keys($custom) as $key) {
                $map[$listId]['custom_keys'][$key] = true;
            }
        }
    }

    foreach ($map as &$stats) {
        $stats['custom_keys'] = array_values(array_keys($stats['custom_keys']));
        sort($stats['custom_keys']);
    }
    unset($stats);

    return $map;
}

function fetch_test_contacts(): array
{
    $stmt = db()->query("SELECT * FROM test_contacts ORDER BY id DESC");
    return $stmt->fetchAll();
}

function fetch_test_contacts_by_ids(array $ids): array
{
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (count($ids) === 0) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM test_contacts WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

function fetch_settings(): array
{
    $stmt = db()->query("SELECT `key`, `value` FROM settings");
    $rows = $stmt->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }
    return $settings;
}

function save_settings(array $settings): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        "INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
    );
    foreach ($settings as $key => $value) {
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
}

function create_campaign(array $payload): int
{
    $pdo = db();
    $stmt = $pdo->prepare(
        "INSERT INTO campaigns
            (title, subject, preview_text, from_name, smtp_config_id, audience_mode, contact_list_id, suppression_list, segment_filter, schedule_at, send_window, tracking, html_content, text_content, status, created_at, updated_at)
         VALUES
            (:title, :subject, :preview_text, :from_name, :smtp_config_id, :audience_mode, :contact_list_id, :suppression_list, :segment_filter, :schedule_at, :send_window, :tracking, :html_content, :text_content, :status, :created_at, :updated_at)"
    );

    $stmt->execute([
        'title' => $payload['title'],
        'subject' => $payload['subject'],
        'preview_text' => $payload['preview_text'],
        'from_name' => $payload['from_name'],
        'smtp_config_id' => $payload['smtp_config_id'],
        'audience_mode' => $payload['audience_mode'],
        'contact_list_id' => $payload['contact_list_id'],
        'suppression_list' => $payload['suppression_list'],
        'segment_filter' => $payload['segment_filter'],
        'schedule_at' => $payload['schedule_at'],
        'send_window' => $payload['send_window'],
        'tracking' => $payload['tracking'],
        'html_content' => $payload['html_content'],
        'text_content' => $payload['text_content'],
        'status' => $payload['status'],
        'created_at' => $payload['created_at'],
        'updated_at' => $payload['updated_at'],
    ]);

    return (int) $pdo->lastInsertId();
}

function update_campaign(int $id, array $payload): void
{
    $stmt = db()->prepare(
        "UPDATE campaigns SET
            title = :title,
            subject = :subject,
            preview_text = :preview_text,
            from_name = :from_name,
            smtp_config_id = :smtp_config_id,
            audience_mode = :audience_mode,
            contact_list_id = :contact_list_id,
            suppression_list = :suppression_list,
            segment_filter = :segment_filter,
            schedule_at = :schedule_at,
            send_window = :send_window,
            tracking = :tracking,
            html_content = :html_content,
            text_content = :text_content,
            status = :status,
            updated_at = :updated_at
         WHERE id = :id"
    );

    $stmt->execute([
        'id' => $id,
        'title' => $payload['title'],
        'subject' => $payload['subject'],
        'preview_text' => $payload['preview_text'],
        'from_name' => $payload['from_name'],
        'smtp_config_id' => $payload['smtp_config_id'],
        'audience_mode' => $payload['audience_mode'],
        'contact_list_id' => $payload['contact_list_id'],
        'suppression_list' => $payload['suppression_list'],
        'segment_filter' => $payload['segment_filter'],
        'schedule_at' => $payload['schedule_at'],
        'send_window' => $payload['send_window'],
        'tracking' => $payload['tracking'],
        'html_content' => $payload['html_content'],
        'text_content' => $payload['text_content'],
        'status' => $payload['status'],
        'updated_at' => $payload['updated_at'],
    ]);
}

function add_campaign_event(int $campaignId, string $type, string $details): void
{
    $stmt = db()->prepare(
        "INSERT INTO campaign_events (campaign_id, type, details, created_at)
         VALUES (:campaign_id, :type, :details, :created_at)"
    );
    $stmt->execute([
        'campaign_id' => $campaignId,
        'type' => $type,
        'details' => $details,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

function add_smtp_config(array $payload): void
{
    $stmt = db()->prepare(
        "INSERT INTO smtp_configs (name, host, port, username, password, encryption, from_address, status, created_at)
         VALUES (:name, :host, :port, :username, :password, :encryption, :from_address, :status, :created_at)"
    );

    $stmt->execute([
        'name' => $payload['name'],
        'host' => $payload['host'],
        'port' => $payload['port'],
        'username' => $payload['username'],
        'password' => $payload['password'],
        'encryption' => $payload['encryption'],
        'from_address' => $payload['from_address'],
        'status' => $payload['status'],
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

function toggle_smtp_status(int $id): void
{
    $stmt = db()->prepare("SELECT status FROM smtp_configs WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $current = $stmt->fetchColumn();
    if (!$current) {
        return;
    }
    $next = $current === 'Active' ? 'Paused' : 'Active';
    $update = db()->prepare("UPDATE smtp_configs SET status = :status WHERE id = :id");
    $update->execute(['status' => $next, 'id' => $id]);
}

function delete_smtp_config(int $id): void
{
    $stmt = db()->prepare("DELETE FROM smtp_configs WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

function add_test_contact(array $payload): void
{
    $stmt = db()->prepare(
        "INSERT INTO test_contacts (email, name, notes)
         VALUES (:email, :name, :notes)"
    );

    $stmt->execute([
        'email' => $payload['email'],
        'name' => $payload['name'],
        'notes' => $payload['notes'],
    ]);
}

function delete_test_contact(int $id): void
{
    $stmt = db()->prepare("DELETE FROM test_contacts WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

function create_contact_list(string $name): int
{
    $stmt = db()->prepare(
        "INSERT INTO contact_lists (name, count, updated_at)
         VALUES (:name, 0, :updated_at)"
    );
    $stmt->execute([
        'name' => $name,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    return (int) db()->lastInsertId();
}

function delete_contact_list(int $id): void
{
    $stmt = db()->prepare("DELETE FROM contact_lists WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $stmt = db()->prepare("DELETE FROM contacts WHERE list_id = :id");
    $stmt->execute(['id' => $id]);
}

function update_contact_list_count(int $listId): void
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM contacts WHERE list_id = :id");
    $stmt->execute(['id' => $listId]);
    $count = (int) $stmt->fetchColumn();

    $update = db()->prepare("UPDATE contact_lists SET count = :count, updated_at = :updated_at WHERE id = :id");
    $update->execute([
        'count' => $count,
        'updated_at' => date('Y-m-d H:i:s'),
        'id' => $listId,
    ]);
}

function import_contacts_from_csv_report(int $listId, array $file, string $defaultTags = ''): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        throw new RuntimeException('Only CSV files are supported.');
    }

    $storagePath = __DIR__ . '/../storage/uploads/' . uniqid('contacts_', true) . '.csv';
    if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
        throw new RuntimeException('Unable to save upload.');
    }

    $handle = fopen($storagePath, 'r');
    if ($handle === false) {
        throw new RuntimeException('Unable to read upload.');
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        throw new RuntimeException('CSV header missing.');
    }

    $headerMap = [];
    foreach ($header as $index => $name) {
        $headerMap[strtolower(trim((string) $name))] = $index;
    }

    $reservedColumns = [
        'email',
        'first_name',
        'last_name',
        'name',
        'tags',
    ];
    $customColumnIndexes = [];
    foreach ($header as $index => $name) {
        $rawHeader = trim((string) $name);
        $normalized = normalize_placeholder_key($rawHeader);
        if ($normalized === '' || in_array($normalized, $reservedColumns, true)) {
            continue;
        }
        $customColumnIndexes[$normalized] = $index;
    }

    $pdo = db();
    $existingStmt = $pdo->prepare("SELECT email FROM contacts WHERE list_id = :list_id");
    $existingStmt->execute(['list_id' => $listId]);
    $knownEmails = [];
    foreach ($existingStmt->fetchAll(PDO::FETCH_COLUMN) as $existingEmail) {
        $normalized = strtolower(trim((string) $existingEmail));
        if ($normalized !== '') {
            $knownEmails[$normalized] = true;
        }
    }

    $insert = $pdo->prepare(
        "INSERT INTO contacts (list_id, email, first_name, last_name, tags, custom_fields, created_at)
         VALUES (:list_id, :email, :first_name, :last_name, :tags, :custom_fields, :created_at)"
    );

    $imported = 0;
    $duplicates = 0;
    $processedRows = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $processedRows += 1;
        $email = get_csv_value($row, $headerMap, 'email');
        if ($email === '') {
            continue;
        }
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            continue;
        }
        if (isset($knownEmails[$normalizedEmail])) {
            $duplicates += 1;
            continue;
        }

        $firstName = get_csv_value($row, $headerMap, 'first_name');
        $lastName = get_csv_value($row, $headerMap, 'last_name');
        $name = get_csv_value($row, $headerMap, 'name');
        if ($name !== '' && ($firstName === '' && $lastName === '')) {
            $parts = preg_split('/\s+/', $name, 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        $tags = get_csv_value($row, $headerMap, 'tags');
        if ($tags === '' && $defaultTags !== '') {
            $tags = $defaultTags;
        }

        $customFields = [];
        foreach ($customColumnIndexes as $columnKey => $index) {
            $value = isset($row[$index]) ? trim((string) $row[$index]) : '';
            if ($value === '') {
                continue;
            }
            $customFields[$columnKey] = $value;
        }
        $customFieldsJson = $customFields !== [] ? json_encode($customFields, JSON_UNESCAPED_SLASHES) : null;

        $insert->execute([
            'list_id' => $listId,
            'email' => $normalizedEmail,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tags' => $tags,
            'custom_fields' => $customFieldsJson,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $imported += 1;
        $knownEmails[$normalizedEmail] = true;
    }

    fclose($handle);

    update_contact_list_count($listId);

    return [
        'inserted' => $imported,
        'duplicates_skipped' => $duplicates,
        'rows_processed' => $processedRows,
    ];
}

function import_contacts_from_csv(int $listId, array $file, string $defaultTags = ''): int
{
    $report = import_contacts_from_csv_report($listId, $file, $defaultTags);
    return (int) ($report['inserted'] ?? 0);
}

function get_csv_value(array $row, array $headerMap, string $key): string
{
    if (!isset($headerMap[$key])) {
        return '';
    }
    $index = $headerMap[$key];
    return isset($row[$index]) ? trim((string) $row[$index]) : '';
}

function export_contact_list(int $listId): void
{
    $stmt = db()->prepare("SELECT name FROM contact_lists WHERE id = :id");
    $stmt->execute(['id' => $listId]);
    $listName = $stmt->fetchColumn();
    if (!$listName) {
        flash_set('error', 'Contact list not found.');
        redirect_to('/index.php?page=manage-contacts');
    }

    $contactsStmt = db()->prepare(
        "SELECT email, first_name, last_name, tags, custom_fields
         FROM contacts
         WHERE list_id = :id"
    );
    $contactsStmt->execute(['id' => $listId]);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="contacts_' . preg_replace('/\s+/', '_', (string) $listName) . '.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    $rows = $contactsStmt->fetchAll();
    $customHeaders = [];
    foreach ($rows as $row) {
        foreach (array_keys(contact_custom_fields_array($row['custom_fields'] ?? null)) as $key) {
            $customHeaders[$key] = true;
        }
    }
    $customHeaderList = array_keys($customHeaders);
    sort($customHeaderList);

    fputcsv($output, array_merge(['email', 'first_name', 'last_name', 'tags'], $customHeaderList), ',', '"', '\\');
    foreach ($rows as $row) {
        $custom = contact_custom_fields_array($row['custom_fields'] ?? null);
        $csvRow = [
            $row['email'],
            $row['first_name'],
            $row['last_name'],
            $row['tags'],
        ];
        foreach ($customHeaderList as $header) {
            $csvRow[] = (string) ($custom[$header] ?? '');
        }
        fputcsv($output, $csvRow, ',', '"', '\\');
    }
    fclose($output);
    exit;
}

function export_sample_contacts_csv(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mailr_sample_contacts.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['email', 'first_name', 'last_name', 'tags', 'company', 'plan', 'city', 'coupon_code', 'renewal_date', 'cta_url'], ',', '"', '\\');
    fputcsv($output, ['ava@example.com', 'Ava', 'Ng', 'vip', 'Northstar', 'pro', 'Austin', 'SAVE20', '2026-04-01', 'https://example.com/offer'], ',', '"', '\\');
    fputcsv($output, ['liam@example.com', 'Liam', 'Diaz', 'newsletter', 'Acme', 'starter', 'Denver', '', '2026-05-10', 'https://example.com/update'], ',', '"', '\\');
    fclose($output);
    exit;
}

function status_badge_class(string $status): string
{
    return strtolower(preg_replace('/\s+/', '-', $status));
}

function smtp_send(array $config, string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody, string $textBody): array
{
    $host = (string) ($config['host'] ?? '');
    $port = (int) ($config['port'] ?? 0);
    $username = (string) ($config['username'] ?? '');
    $password = (string) ($config['password'] ?? '');
    $encryption = strtolower((string) ($config['encryption'] ?? 'tls'));
    if ($encryption === '') {
        $encryption = 'tls';
    }

    if ($host === '' || $port === 0) {
        return [false, 'SMTP host/port missing.'];
    }
    if ($fromEmail === '') {
        return [false, 'SMTP from address missing.'];
    }

    $transportHost = $host;
    if ($encryption === 'ssl') {
        $transportHost = 'ssl://' . $host;
    }

    $socket = @stream_socket_client(
        $transportHost . ':' . $port,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        return [false, 'SMTP connection failed: ' . $errstr];
    }

    stream_set_timeout($socket, 20);

    $response = smtp_read($socket);
    if (!smtp_is_ok($response, 220)) {
        fclose($socket);
        return [false, 'SMTP connection rejected: ' . $response];
    }

    $hostname = 'mailr.local';
    smtp_write($socket, "EHLO {$hostname}");
    $ehlo = smtp_read_multiline($socket);
    if (!smtp_is_ok($ehlo, 250)) {
        smtp_write($socket, "HELO {$hostname}");
        $helo = smtp_read($socket);
        if (!smtp_is_ok($helo, 250)) {
            fclose($socket);
            return [false, 'SMTP HELO/EHLO failed: ' . $helo];
        }
    }

    if ($encryption === 'tls') {
        smtp_write($socket, 'STARTTLS');
        $startTls = smtp_read($socket);
        if (!smtp_is_ok($startTls, 220)) {
            fclose($socket);
            return [false, 'SMTP STARTTLS failed: ' . $startTls];
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return [false, 'TLS negotiation failed.'];
        }

        smtp_write($socket, "EHLO {$hostname}");
        $ehlo = smtp_read_multiline($socket);
        if (!smtp_is_ok($ehlo, 250)) {
            fclose($socket);
            return [false, 'SMTP EHLO after TLS failed: ' . $ehlo];
        }
    }

    if ($username !== '' && $password !== '') {
        smtp_write($socket, 'AUTH LOGIN');
        $auth = smtp_read($socket);
        if (!smtp_is_ok($auth, 334)) {
            fclose($socket);
            return [false, 'SMTP AUTH failed: ' . $auth];
        }
        smtp_write($socket, base64_encode($username));
        $authUser = smtp_read($socket);
        if (!smtp_is_ok($authUser, 334)) {
            fclose($socket);
            return [false, 'SMTP AUTH username rejected: ' . $authUser];
        }
        smtp_write($socket, base64_encode($password));
        $authPass = smtp_read($socket);
        if (!smtp_is_ok($authPass, 235)) {
            fclose($socket);
            return [false, 'SMTP AUTH password rejected: ' . $authPass];
        }
    }

    $fromEmail = sanitize_header_value($fromEmail);
    $toEmail = sanitize_header_value($toEmail);
    $subject = sanitize_header_value($subject);
    $fromName = sanitize_header_value($fromName);
    $htmlBody = inline_email_css_best_effort($htmlBody);

    smtp_write($socket, 'MAIL FROM:<' . $fromEmail . '>');
    $mailFrom = smtp_read($socket);
    if (!smtp_is_ok($mailFrom, 250)) {
        fclose($socket);
        return [false, 'MAIL FROM rejected: ' . $mailFrom];
    }

    smtp_write($socket, 'RCPT TO:<' . $toEmail . '>');
    $rcptTo = smtp_read($socket);
    if (!smtp_is_ok($rcptTo, 250) && !smtp_is_ok($rcptTo, 251)) {
        fclose($socket);
        return [false, 'RCPT TO rejected: ' . $rcptTo];
    }

    smtp_write($socket, 'DATA');
    $dataStart = smtp_read($socket);
    if (!smtp_is_ok($dataStart, 354)) {
        fclose($socket);
        return [false, 'DATA rejected: ' . $dataStart];
    }

    $message = build_mime_message($fromEmail, $fromName, $toEmail, $subject, $htmlBody, $textBody);
    $message = preg_replace('/\r?\n\./', "\r\n..", $message);

    smtp_write($socket, $message . "\r\n.");
    $dataResult = smtp_read($socket);
    smtp_write($socket, 'QUIT');
    fclose($socket);

    if (!smtp_is_ok($dataResult, 250)) {
        return [false, 'Message rejected: ' . $dataResult];
    }

    return [true, 'Sent'];
}

function inline_email_css_best_effort(string $html): string
{
    if (trim($html) === '' || !class_exists('DOMDocument') || stripos($html, '<style') === false) {
        return $html;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $wrapped = str_contains(strtolower($html), '<html') || str_contains(strtolower($html), '<body')
        ? $html
        : '<!DOCTYPE html><html><body>' . $html . '</body></html>';

    $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    if (!$loaded) {
        libxml_clear_errors();
        return $html;
    }

    $xpath = new DOMXPath($dom);
    $styleNodes = $dom->getElementsByTagName('style');
    $cssText = '';
    $nodesToRemove = [];
    foreach ($styleNodes as $styleNode) {
        $cssText .= "\n" . (string) $styleNode->textContent;
        $nodesToRemove[] = $styleNode;
    }

    foreach (parse_simple_css_rules($cssText) as $rule) {
        $selector = (string) ($rule['selector'] ?? '');
        $declarations = (string) ($rule['declarations'] ?? '');
        if ($selector === '' || $declarations === '') {
            continue;
        }
        $query = css_selector_to_xpath($selector);
        if ($query === null) {
            continue;
        }
        foreach ($xpath->query($query) ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $existing = (string) $node->getAttribute('style');
            $node->setAttribute('style', merge_inline_style_strings($existing, $declarations));
        }
    }

    foreach ($nodesToRemove as $node) {
        if ($node->parentNode) {
            $node->parentNode->removeChild($node);
        }
    }

    $result = '';
    $bodyNode = $dom->getElementsByTagName('body')->item(0);
    if ($bodyNode instanceof DOMElement) {
        foreach ($bodyNode->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
        return $result !== '' ? $result : $html;
    }
    return $dom->saveHTML() ?: $html;
}

function parse_simple_css_rules(string $css): array
{
    $css = preg_replace('~/\*.*?\*/~s', '', $css) ?? $css;
    preg_match_all('/([^{}]+)\{([^{}]+)\}/', $css, $matches, PREG_SET_ORDER);
    $rules = [];
    foreach ($matches as $match) {
        $selectors = explode(',', trim((string) ($match[1] ?? '')));
        $declarations = trim((string) ($match[2] ?? ''));
        foreach ($selectors as $selector) {
            $selector = trim($selector);
            if ($selector === '' || str_contains($selector, '@')) {
                continue;
            }
            $rules[] = [
                'selector' => $selector,
                'declarations' => $declarations,
            ];
        }
    }
    return $rules;
}

function css_selector_to_xpath(string $selector): ?string
{
    $selector = trim($selector);
    if ($selector === '' || preg_match('/[>+~:\[]/', $selector)) {
        return null;
    }
    $parts = preg_split('/\s+/', $selector);
    if (!is_array($parts) || $parts === []) {
        return null;
    }

    $xpath = '.';
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (!preg_match('/^([a-zA-Z][\w-]*)?(#[\w-]+)?((?:\.[\w-]+)*)$/', $part, $m)) {
            return null;
        }
        $tag = $m[1] !== '' ? $m[1] : '*';
        $id = isset($m[2]) && $m[2] !== '' ? substr($m[2], 1) : '';
        $classes = [];
        if (!empty($m[3])) {
            $classes = array_values(array_filter(explode('.', ltrim($m[3], '.'))));
        }
        $segment = '//' . $tag;
        $predicates = [];
        if ($id !== '') {
            $predicates[] = '@id=' . xpath_literal($id);
        }
        foreach ($classes as $className) {
            $predicates[] = "contains(concat(' ', normalize-space(@class), ' '), " . xpath_literal(' ' . $className . ' ') . ')';
        }
        if ($predicates !== []) {
            $segment .= '[' . implode(' and ', $predicates) . ']';
        }
        $xpath .= $segment;
    }
    return $xpath;
}

function merge_inline_style_strings(string $existing, string $incoming): string
{
    $existingMap = parse_style_declarations($existing);
    $incomingMap = parse_style_declarations($incoming);
    foreach ($incomingMap as $prop => $value) {
        if (!array_key_exists($prop, $existingMap)) {
            $existingMap[$prop] = $value;
        }
    }
    $chunks = [];
    foreach ($existingMap as $prop => $value) {
        $chunks[] = $prop . ': ' . $value;
    }
    return implode('; ', $chunks) . (count($chunks) ? ';' : '');
}

function parse_style_declarations(string $style): array
{
    $map = [];
    foreach (explode(';', $style) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $prop = strtolower(trim($parts[0]));
        $value = trim($parts[1]);
        if ($prop === '' || $value === '') {
            continue;
        }
        $map[$prop] = $value;
    }
    return $map;
}

function xpath_literal(string $value): string
{
    if (!str_contains($value, "'")) {
        return "'" . $value . "'";
    }
    if (!str_contains($value, '"')) {
        return '"' . $value . '"';
    }
    $parts = explode("'", $value);
    $pieces = [];
    foreach ($parts as $index => $part) {
        if ($part !== '') {
            $pieces[] = "'" . $part . "'";
        }
        if ($index < count($parts) - 1) {
            $pieces[] = "\"'\"";
        }
    }
    return 'concat(' . implode(',', $pieces) . ')';
}

function build_mime_message(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody, string $textBody): string
{
    $boundary = 'mailr_' . bin2hex(random_bytes(8));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromHeader = $fromName !== '' ? sprintf('%s <%s>', $fromName, $fromEmail) : $fromEmail;

    $headers = [
        'From: ' . $fromHeader,
        'To: ' . $toEmail,
        'Subject: ' . $encodedSubject,
        'Date: ' . date(DATE_RFC2822),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@mailr.local>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $textBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
    $textBody = normalize_line_endings($textBody);
    $htmlBody = normalize_line_endings($htmlBody);
    [$encodedTextBody, $textEncoding] = encode_mime_body($textBody);
    [$encodedHtmlBody, $htmlEncoding] = encode_mime_body($htmlBody);

    $body = [];
    $body[] = '--' . $boundary;
    $body[] = 'Content-Type: text/plain; charset=UTF-8';
    $body[] = 'Content-Transfer-Encoding: ' . $textEncoding;
    $body[] = '';
    $body[] = $encodedTextBody;
    $body[] = '--' . $boundary;
    $body[] = 'Content-Type: text/html; charset=UTF-8';
    $body[] = 'Content-Transfer-Encoding: ' . $htmlEncoding;
    $body[] = '';
    $body[] = $encodedHtmlBody;
    $body[] = '--' . $boundary . '--';
    $body[] = '';

    return implode("\r\n", array_merge($headers, [''], $body));
}

function encode_mime_body(string $content): array
{
    if (function_exists('quoted_printable_encode')) {
        return [quoted_printable_encode($content), 'quoted-printable'];
    }
    return [chunk_split(base64_encode($content), 76, "\r\n"), 'base64'];
}

function smtp_write($socket, string $message): void
{
    fwrite($socket, $message . "\r\n");
}

function smtp_read($socket): string
{
    return (string) fgets($socket, 515);
}

function smtp_read_multiline($socket): string
{
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtp_is_ok(string $response, int $code): bool
{
    return str_starts_with(trim($response), (string) $code);
}

function sanitize_header_value(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function normalize_line_endings(string $content): string
{
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    return str_replace("\n", "\r\n", $content);
}

function personalize_content(string $content, array $contact): string
{
    $name = trim((string) ($contact['name'] ?? ''));
    if ($name === '') {
        $name = trim(((string) ($contact['first_name'] ?? '')) . ' ' . ((string) ($contact['last_name'] ?? '')));
    }
    $parts = preg_split('/\s+/', trim($name));
    $firstName = $parts[0] ?? '';
    $replacements = [
        '{{first_name}}' => $firstName,
        '{{name}}' => $name,
        '{{email}}' => (string) ($contact['email'] ?? ''),
        '{{company}}' => (string) ($contact['company'] ?? ''),
        '{{cta_url}}' => (string) ($contact['cta_url'] ?? ''),
    ];

    $customFields = contact_custom_fields_array($contact['custom_fields'] ?? null);
    foreach ($customFields as $key => $value) {
        if ($key === '') {
            continue;
        }
        $token = '{{' . normalize_placeholder_key((string) $key) . '}}';
        if (!array_key_exists($token, $replacements) || trim((string) ($replacements[$token] ?? '')) === '') {
            $replacements[$token] = (string) $value;
        }
    }

    return strtr($content, $replacements);
}

function fetch_email_templates(): array
{
    $stmt = db()->query(
        "SELECT id, name, category, description, status, updated_at
         FROM email_templates
         ORDER BY updated_at DESC, id DESC"
    );
    return $stmt->fetchAll();
}

function fetch_email_templates_for_editor(bool $activeOnly = true): array
{
    $sql = "SELECT id, name, category, description, html_content, text_content, status, updated_at
            FROM email_templates";
    if ($activeOnly) {
        $sql .= " WHERE status = 'Active'";
    }
    $sql .= " ORDER BY updated_at DESC, id DESC";
    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

function fetch_email_template(int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM email_templates WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_email_template(array $payload): int
{
    $stmt = db()->prepare(
        "INSERT INTO email_templates
         (name, category, description, source_text, html_content, text_content, status, created_at, updated_at)
         VALUES (:name, :category, :description, :source_text, :html_content, :text_content, :status, :created_at, :updated_at)"
    );
    $stmt->execute($payload);
    return (int) db()->lastInsertId();
}

function update_email_template(int $id, array $payload): void
{
    $payload['id'] = $id;
    $stmt = db()->prepare(
        "UPDATE email_templates
         SET name = :name,
             category = :category,
             description = :description,
             source_text = :source_text,
             html_content = :html_content,
             text_content = :text_content,
             status = :status,
             updated_at = :updated_at
         WHERE id = :id"
    );
    $stmt->execute($payload);
}

function delete_email_template(int $id): void
{
    if ($id <= 0) {
        return;
    }
    $stmt = db()->prepare("DELETE FROM email_templates WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

function plain_text_from_html(string $html): string
{
    $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $text = preg_replace("/\R{3,}/", "\n\n", (string) $text);
    return trim((string) $text);
}

function generate_email_template_html(string $promptText, array $options = []): array
{
    $promptText = trim($promptText);
    if ($promptText === '') {
        return [
            'ok' => false,
            'provider' => 'none',
            'message' => 'Provide source text or a brief before generating.',
            'html' => '',
            'text' => '',
        ];
    }

    $provider = strtolower(trim((string) (getenv('MAILR_AI_PROVIDER') ?: 'auto')));
    $apiKey = (string) (getenv('MAILR_OPENAI_API_KEY') ?: getenv('OPENAI_API_KEY') ?: '');
    $hfApiKey = (string) (getenv('MAILR_HF_API_KEY') ?: getenv('HUGGINGFACE_API_KEY') ?: getenv('HF_TOKEN') ?: '');
    $hfModel = (string) (getenv('MAILR_HF_MODEL') ?: 'Qwen/Qwen2.5-7B-Instruct');
    $hfEndpoint = (string) (getenv('MAILR_HF_ENDPOINT') ?: '');
    $openaiModel = (string) (getenv('MAILR_OPENAI_MODEL') ?: 'gpt-4.1-mini');
    $ollamaUrl = (string) (getenv('MAILR_OLLAMA_URL') ?: 'http://127.0.0.1:11434');
    $ollamaModel = (string) (getenv('MAILR_OLLAMA_MODEL') ?: 'llama3.2:3b');

    $remoteErrors = [];

    if (in_array($provider, ['auto', 'ollama'], true)) {
        $remote = ollama_generate_email_template_html($ollamaUrl, $ollamaModel, $promptText, $options);
        if (($remote['ok'] ?? false) === true) {
            return $remote;
        }
        $remoteErrors[] = (string) ($remote['message'] ?? 'Ollama generation failed.');
        if ($provider === 'ollama') {
            $fallbackHtml = local_beautify_email_template_html($promptText, $options);
            return [
                'ok' => true,
                'provider' => 'local',
                'message' => 'Ollama unavailable. Generated with local beautifier.',
                'html' => $fallbackHtml,
                'text' => plain_text_from_html($fallbackHtml),
            ];
        }
    }

    if ($hfApiKey !== '' && in_array($provider, ['auto', 'huggingface', 'hf'], true)) {
        $remote = huggingface_generate_email_template_html($hfApiKey, $hfModel, $promptText, $options, $hfEndpoint);
        if (($remote['ok'] ?? false) === true) {
            return $remote;
        }
        $remoteErrors[] = (string) ($remote['message'] ?? 'Hugging Face generation failed.');
    }

    if ($apiKey !== '' && in_array($provider, ['auto', 'openai'], true)) {
        $remote = openai_generate_email_template_html($apiKey, $openaiModel, $promptText, $options);
        if (($remote['ok'] ?? false) === true) {
            return $remote;
        }
        $remoteErrors[] = (string) ($remote['message'] ?? 'OpenAI generation failed.');
    }

    $fallbackHtml = local_beautify_email_template_html($promptText, $options);
    return [
        'ok' => true,
        'provider' => 'local',
        'message' => $remoteErrors !== []
            ? ('AI request unavailable (' . implode(' | ', array_unique(array_filter($remoteErrors))) . '). Generated with local beautifier.')
            : 'Generated with local beautifier. For better results, run free Ollama locally and set MAILR_AI_PROVIDER=ollama.',
        'html' => $fallbackHtml,
        'text' => plain_text_from_html($fallbackHtml),
    ];
}

function huggingface_generate_email_template_html(string $apiKey, string $model, string $promptText, array $options = [], string $customEndpoint = ''): array
{
    $brand = trim((string) ($options['brand'] ?? 'Mailr'));
    $tone = trim((string) ($options['tone'] ?? 'clean, modern, conversion-focused'));
    $category = trim((string) ($options['category'] ?? 'General'));

    $endpoint = trim($customEndpoint) !== ''
        ? trim($customEndpoint)
        : 'https://router.huggingface.co/v1/chat/completions';

    $systemPrompt = "You generate production-ready HTML email templates. Return only HTML markup (no markdown fences, no explanations). Use inline styles suitable for email clients. Keep width 600px and include a header, body sections, CTA button, and footer. Preserve placeholders like {{first_name}}, {{cta_url}}, {{company}}.";
    $userPrompt = "Brand: {$brand}\nCategory: {$category}\nTone: {$tone}\n\nSource brief/text:\n{$promptText}";

    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0.5,
        'max_tokens' => 1800,
        'stream' => false,
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        return ['ok' => false, 'provider' => 'huggingface', 'message' => 'Failed to encode Hugging Face request.'];
    }

    $responseBody = null;
    $statusCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return ['ok' => false, 'provider' => 'huggingface', 'message' => 'Failed to initialize HTTP client.'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 60,
        ]);
        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($responseBody === false) {
            return ['ok' => false, 'provider' => 'huggingface', 'message' => $curlErr !== '' ? $curlErr : 'Hugging Face request failed.'];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'content' => $payload,
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = @file_get_contents($endpoint, false, $context);
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                    $statusCode = (int) $m[1];
                    break;
                }
            }
        }
        if ($responseBody === false) {
            return ['ok' => false, 'provider' => 'huggingface', 'message' => 'Could not reach Hugging Face inference endpoint.'];
        }
    }

    $data = json_decode((string) $responseBody, true);
    if (!is_array($data)) {
        return ['ok' => false, 'provider' => 'huggingface', 'message' => 'Hugging Face response was not valid JSON.'];
    }

    if ($statusCode >= 400) {
        $rawError = $data['error'] ?? null;
        if (is_array($rawError)) {
            $message = trim((string) ($rawError['message'] ?? ''));
            if ($message === '') {
                $encoded = json_encode($rawError, JSON_UNESCAPED_SLASHES);
                $message = $encoded !== false ? $encoded : 'Hugging Face generation failed.';
            }
        } elseif (is_string($rawError) && trim($rawError) !== '') {
            $message = trim($rawError);
        } else {
            $message = 'Hugging Face generation failed.';
        }
        return ['ok' => false, 'provider' => 'huggingface', 'message' => $message];
    }

    $html = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
    if ($html === '' && isset($data[0]) && is_array($data[0])) {
        $html = trim((string) ($data[0]['generated_text'] ?? ''));
    } elseif ($html === '' && isset($data['generated_text'])) {
        $html = trim((string) $data['generated_text']);
    }

    if ($html === '') {
        return ['ok' => false, 'provider' => 'huggingface', 'message' => 'Hugging Face returned empty output.'];
    }

    $html = preg_replace('/^```(?:html)?\s*|\s*```$/i', '', $html);
    $html = trim((string) $html);

    return [
        'ok' => true,
        'provider' => 'huggingface',
        'message' => 'Generated with Hugging Face Inference.',
        'html' => $html,
        'text' => plain_text_from_html($html),
    ];
}

function ollama_generate_email_template_html(string $baseUrl, string $model, string $promptText, array $options = []): array
{
    $brand = trim((string) ($options['brand'] ?? 'Mailr'));
    $tone = trim((string) ($options['tone'] ?? 'clean, modern, conversion-focused'));
    $category = trim((string) ($options['category'] ?? 'General'));

    $baseUrl = rtrim($baseUrl, '/');
    $endpoint = $baseUrl . '/api/generate';

    $prompt = "You generate production-ready HTML email templates.\n"
        . "Return only HTML (no markdown fences, no explanation).\n"
        . "Use inline styles suitable for email clients.\n"
        . "Use a max width of 600px, include a header, body sections, CTA button, and footer.\n"
        . "Preserve placeholders like {{first_name}}, {{cta_url}}, {{company}}.\n\n"
        . "Brand: {$brand}\nCategory: {$category}\nTone: {$tone}\n\n"
        . "Source brief/text:\n{$promptText}\n";

    $payload = json_encode([
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.5,
        ],
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        return ['ok' => false, 'provider' => 'ollama', 'message' => 'Failed to encode Ollama request.'];
    }

    $responseBody = null;
    $statusCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return ['ok' => false, 'provider' => 'ollama', 'message' => 'Failed to initialize HTTP client.'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 45,
        ]);
        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($responseBody === false) {
            return ['ok' => false, 'provider' => 'ollama', 'message' => $curlErr !== '' ? $curlErr : 'Ollama request failed.'];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 45,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = @file_get_contents($endpoint, false, $context);
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                    $statusCode = (int) $m[1];
                    break;
                }
            }
        }
        if ($responseBody === false) {
            return ['ok' => false, 'provider' => 'ollama', 'message' => 'Could not reach Ollama at ' . $endpoint];
        }
    }

    $data = json_decode((string) $responseBody, true);
    if (!is_array($data)) {
        return ['ok' => false, 'provider' => 'ollama', 'message' => 'Ollama response was not valid JSON.'];
    }

    $html = trim((string) ($data['response'] ?? ''));
    if ($statusCode >= 400 || $html === '') {
        $errorMsg = (string) ($data['error'] ?? '');
        if ($errorMsg === '') {
            $errorMsg = $html === '' ? 'Ollama returned empty output.' : 'Ollama generation failed.';
        }
        return ['ok' => false, 'provider' => 'ollama', 'message' => $errorMsg];
    }

    $html = preg_replace('/^```(?:html)?\s*|\s*```$/i', '', $html);
    $html = trim((string) $html);

    return [
        'ok' => true,
        'provider' => 'ollama',
        'message' => 'Generated with Ollama (free local AI).',
        'html' => $html,
        'text' => plain_text_from_html($html),
    ];
}

function openai_generate_email_template_html(string $apiKey, string $model, string $promptText, array $options = []): array
{
    $brand = trim((string) ($options['brand'] ?? 'Mailr'));
    $tone = trim((string) ($options['tone'] ?? 'clean, modern, conversion-focused'));
    $category = trim((string) ($options['category'] ?? 'General'));

    $system = "You generate production-ready HTML email templates. Return only HTML markup (no markdown fences, no explanations). Use inline styles suitable for email clients. Keep width 600px, include a strong header, body copy, CTA button, and footer. Preserve placeholders like {{first_name}}, {{cta_url}}, {{company}} if present.";
    $user = "Brand: {$brand}\nCategory: {$category}\nTone: {$tone}\n\nSource brief/text:\n{$promptText}\n\nOutput a complete HTML email body fragment using semantic but email-safe markup with inline styles.";

    $payload = json_encode([
        'model' => $model,
        'input' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'max_output_tokens' => 1800,
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        return ['ok' => false, 'provider' => 'openai', 'message' => 'Failed to encode AI request.'];
    }

    $responseBody = null;
    $statusCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.openai.com/v1/responses');
        if ($ch === false) {
            return ['ok' => false, 'provider' => 'openai', 'message' => 'Failed to initialize HTTP client.'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
        ]);
        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($responseBody === false) {
            return ['ok' => false, 'provider' => 'openai', 'message' => $curlErr !== '' ? $curlErr : 'AI request failed.'];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'content' => $payload,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = @file_get_contents('https://api.openai.com/v1/responses', false, $context);
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                    $statusCode = (int) $m[1];
                    break;
                }
            }
        }
        if ($responseBody === false) {
            return ['ok' => false, 'provider' => 'openai', 'message' => 'AI request failed.'];
        }
    }

    $data = json_decode((string) $responseBody, true);
    if (!is_array($data)) {
        return ['ok' => false, 'provider' => 'openai', 'message' => 'AI response was not valid JSON.'];
    }

    $html = trim((string) ($data['output_text'] ?? ''));
    if ($html === '' && isset($data['output']) && is_array($data['output'])) {
        foreach ($data['output'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            foreach (($item['content'] ?? []) as $content) {
                if (is_array($content) && ($content['type'] ?? '') === 'output_text') {
                    $html .= (string) ($content['text'] ?? '');
                }
            }
        }
        $html = trim($html);
    }

    if ($statusCode >= 400 || $html === '') {
        $message = (string) ($data['error']['message'] ?? 'AI generation failed.');
        if ($message === '' && $html === '') {
            $message = 'AI generation returned empty output.';
        }
        return ['ok' => false, 'provider' => 'openai', 'message' => $message];
    }

    $html = preg_replace('/^```(?:html)?\s*|\s*```$/i', '', $html);
    $html = trim((string) $html);

    return [
        'ok' => true,
        'provider' => 'openai',
        'message' => 'Generated with OpenAI.',
        'html' => $html,
        'text' => plain_text_from_html($html),
    ];
}

function local_beautify_email_template_html(string $promptText, array $options = []): string
{
    $brand = trim((string) ($options['brand'] ?? 'Mailr'));
    $accent = trim((string) ($options['accent'] ?? '#1f7a6d'));
    if ($accent === '') {
        $accent = '#1f7a6d';
    }

    $clean = trim($promptText);
    $lines = preg_split('/\R+/', $clean) ?: [];
    $title = '';
    $paragraphs = [];
    $bullets = [];
    $ctaUrl = '{{cta_url}}';
    $ctaLabel = 'Learn More';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if ($title === '') {
            $title = $line;
            continue;
        }
        if (preg_match('/^(?:[-*•]|\d+\.)\s+(.+)$/u', $line, $m)) {
            $bullets[] = trim($m[1]);
            continue;
        }
        if (preg_match('#https?://\S+#i', $line, $m)) {
            $ctaUrl = trim($m[0]);
        }
        if (stripos($line, 'cta:') === 0) {
            $ctaLabel = trim(substr($line, 4)) ?: $ctaLabel;
            continue;
        }
        $paragraphs[] = $line;
    }

    if ($title === '') {
        $title = 'Hello {{first_name}},';
    }
    if ($paragraphs === []) {
        $paragraphs[] = 'We have an update to share with you.';
        $paragraphs[] = 'Use the button below to see the details and take the next step.';
    }

    $titleHtml = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $paragraphHtml = '';
    foreach ($paragraphs as $line) {
        $paragraphHtml .= '<p style="margin:0 0 14px;color:#313131;font-size:15px;line-height:1.6;">' .
            nl2br(htmlspecialchars($line, ENT_QUOTES, 'UTF-8')) .
            '</p>';
    }

    $bulletsHtml = '';
    if ($bullets !== []) {
        $bulletsHtml .= '<ul style="margin:0 0 16px 18px;padding:0;color:#313131;font-size:15px;line-height:1.6;">';
        foreach ($bullets as $bullet) {
            $bulletsHtml .= '<li style="margin:0 0 8px;">' . htmlspecialchars($bullet, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $bulletsHtml .= '</ul>';
    }

    $brandEsc = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
    $ctaUrlEsc = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');
    $ctaLabelEsc = htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8');
    $accentEsc = htmlspecialchars($accent, ENT_QUOTES, 'UTF-8');

    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f4f0ea;margin:0;padding:24px 0;font-family:Arial,Helvetica,sans-serif;">'
        . '<tr><td align="center">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e6e0d7;">'
        . '<tr><td style="background:' . $accentEsc . ';padding:22px 28px;">'
        . '<div style="color:#eaf7f4;font-size:12px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;">' . $brandEsc . '</div>'
        . '<h1 style="margin:10px 0 0;color:#ffffff;font-size:24px;line-height:1.25;">' . $titleHtml . '</h1>'
        . '</td></tr>'
        . '<tr><td style="padding:28px 28px 18px;">'
        . $paragraphHtml
        . $bulletsHtml
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 14px;"><tr><td style="border-radius:999px;background:' . $accentEsc . ';">'
        . '<a href="' . $ctaUrlEsc . '" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;">' . $ctaLabelEsc . '</a>'
        . '</td></tr></table>'
        . '<p style="margin:0;color:#6c6a66;font-size:13px;line-height:1.6;">You are receiving this because you subscribed to updates. Replace this footer with your compliance text.</p>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '</table>';
}


function handle_request(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $getAction = (string) ($_GET['action'] ?? '');
        if ($getAction === 'export_list') {
            require_auth_or_redirect('/index.php?page=manage-contacts');
            $listId = (int) ($_GET['list_id'] ?? 0);
            if ($listId > 0) {
                export_contact_list($listId);
            }
        }
        if ($getAction === 'download_sample_contacts_csv') {
            require_auth_or_redirect('/index.php?page=manage-contacts');
            export_sample_contacts_csv();
        }
        if ($getAction === 'track_click') {
            handle_track_click();
        }
        if ($getAction === 'track_open') {
            handle_track_open();
        }
        if ($getAction === 'unsubscribe') {
            handle_unsubscribe();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        handle_login();
        return;
    }

    if ($action === 'logout') {
        csrf_enforce();
        auth_logout();
        flash_set('success', 'Signed out.');
        redirect_to('/index.php?page=login');
    }

    require_auth_or_redirect('/index.php?page=login');
    csrf_enforce();

    switch ($action) {
        case 'save_campaign':
        case 'send_test':
        case 'publish_campaign':
            handle_campaign_action($action);
            break;
        case 'add_smtp_config':
            handle_add_smtp_config();
            break;
        case 'toggle_smtp_status':
            toggle_smtp_status((int) ($_POST['smtp_id'] ?? 0));
            flash_set('success', 'SMTP status updated.');
            redirect_to('/index.php?page=configs-test');
            break;
        case 'delete_smtp_config':
            delete_smtp_config((int) ($_POST['smtp_id'] ?? 0));
            flash_set('success', 'SMTP config removed.');
            redirect_to('/index.php?page=configs-test');
            break;
        case 'save_settings':
            handle_save_settings();
            break;
        case 'add_test_contact':
            handle_add_test_contact();
            break;
        case 'delete_test_contact':
            delete_test_contact((int) ($_POST['test_contact_id'] ?? 0));
            flash_set('success', 'Test contact removed.');
            redirect_to('/index.php?page=configs-test');
            break;
        case 'add_contact_list':
            handle_add_contact_list();
            break;
        case 'import_contacts':
            handle_import_contacts();
            break;
        case 'append_contacts_to_list':
            handle_append_contacts_to_list();
            break;
        case 'delete_contact_list':
            delete_contact_list((int) ($_POST['list_id'] ?? 0));
            flash_set('success', 'Contact list deleted.');
            redirect_to('/index.php?page=manage-contacts');
            break;
        case 'save_email_template':
            handle_save_email_template();
            break;
        case 'delete_email_template':
            handle_delete_email_template();
            break;
        case 'generate_template_ai':
            handle_generate_template_ai();
            break;
        case 'pause_campaign_delivery':
            pause_campaign_delivery((int) ($_POST['campaign_id'] ?? 0));
            flash_set('success', 'Campaign paused.');
            redirect_to('/index.php?page=campaign-overview&campaign_id=' . (int) ($_POST['campaign_id'] ?? 0));
            break;
        case 'resume_campaign_delivery':
            resume_campaign_delivery((int) ($_POST['campaign_id'] ?? 0));
            flash_set('success', 'Campaign resumed.');
            redirect_to('/index.php?page=campaign-overview&campaign_id=' . (int) ($_POST['campaign_id'] ?? 0));
            break;
    }
}

function handle_campaign_action(string $action): void
{
    $ajax = is_ajax_request() && in_array($action, ['send_test', 'publish_campaign'], true);
    $campaignId = (int) ($_POST['campaign_id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $previewText = trim((string) ($_POST['preview_text'] ?? ''));
    $fromName = trim((string) ($_POST['from_name'] ?? ''));
    $smtpConfigId = (int) ($_POST['smtp_config_id'] ?? 0);
    $audienceMode = $_POST['audience_mode'] ?? 'list';
    $contactListId = (int) ($_POST['contact_list_id'] ?? 0);
    $uploadListName = trim((string) ($_POST['upload_list_name'] ?? ''));
    $suppressionList = trim((string) ($_POST['suppression_list'] ?? ''));
    $segmentFilter = trim((string) ($_POST['segment_filter'] ?? ''));
    $scheduleAt = trim((string) ($_POST['schedule_at'] ?? ''));
    $htmlContent = trim((string) ($_POST['html_content'] ?? ''));
    $textContent = trim((string) ($_POST['text_content'] ?? ''));
    if ($scheduleAt !== '') {
        $scheduleAt = date('Y-m-d H:i:s', strtotime($scheduleAt));
    }
    $sendWindow = trim((string) ($_POST['send_window'] ?? 'All day'));
    $tracking = trim((string) ($_POST['tracking'] ?? 'Open + click tracking'));

    $errors = [];
    if ($title === '') {
        $errors[] = 'Campaign title is required.';
    }
    if ($subject === '') {
        $errors[] = 'Email subject is required.';
    }
    if ($smtpConfigId === 0) {
        $errors[] = 'Please select an SMTP configuration.';
    }
    if (($action === 'send_test' || $action === 'publish_campaign') && $htmlContent === '') {
        $errors[] = 'Email content is required before sending or publishing.';
    }

    if ($audienceMode === 'list') {
        if ($contactListId === 0) {
            $errors[] = 'Please choose a contact list.';
        }
    } else {
        if ($uploadListName === '') {
            $errors[] = 'Provide a name for the uploaded list.';
        }
        if (!isset($_FILES['upload_csv']) || $_FILES['upload_csv']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Upload a CSV file for the new list.';
        }
    }

    if ($errors) {
        if ($ajax) {
            json_response([
                'ok' => false,
                'message' => implode(' ', $errors),
                'campaign_id' => $campaignId,
            ], 422);
        }
        flash_set('error', implode(' ', $errors));
        redirect_to('/index.php?page=create-campaign' . ($campaignId ? '&campaign_id=' . $campaignId : ''));
    }

    if ($audienceMode === 'upload') {
        $contactListId = create_contact_list($uploadListName);
        try {
            import_contacts_from_csv($contactListId, $_FILES['upload_csv']);
        } catch (RuntimeException $e) {
            if ($ajax) {
                json_response([
                    'ok' => false,
                    'message' => $e->getMessage(),
                    'campaign_id' => $campaignId,
                ], 422);
            }
            flash_set('error', $e->getMessage());
            redirect_to('/index.php?page=create-campaign');
        }
    }

    $now = date('Y-m-d H:i:s');
    $payload = [
        'title' => $title,
        'subject' => $subject,
        'preview_text' => $previewText,
        'from_name' => $fromName,
        'smtp_config_id' => $smtpConfigId,
        'audience_mode' => $audienceMode,
        'contact_list_id' => $contactListId,
        'suppression_list' => $suppressionList,
        'segment_filter' => $segmentFilter,
        'schedule_at' => $scheduleAt !== '' ? $scheduleAt : null,
        'send_window' => $sendWindow,
        'tracking' => $tracking,
        'html_content' => $htmlContent,
        'text_content' => $textContent,
        'status' => 'Draft',
        'created_at' => $now,
        'updated_at' => $now,
    ];

    if ($campaignId > 0) {
        update_campaign($campaignId, $payload);
    } else {
        $campaignId = create_campaign($payload);
    }

    if ($action === 'send_test') {
        $selected = $_POST['test_contact_ids'] ?? [];
        if (!is_array($selected) || count($selected) === 0) {
            if ($ajax) {
                json_response([
                    'ok' => false,
                    'message' => 'Select at least one test contact.',
                    'campaign_id' => $campaignId,
                ], 422);
            }
            flash_set('error', 'Select at least one test contact.');
            redirect_to('/index.php?page=create-campaign&campaign_id=' . $campaignId);
        }
        $smtpConfig = fetch_smtp_config($smtpConfigId);
        if (!$smtpConfig) {
            if ($ajax) {
                json_response([
                    'ok' => false,
                    'message' => 'SMTP configuration not found.',
                    'campaign_id' => $campaignId,
                ], 404);
            }
            flash_set('error', 'SMTP configuration not found.');
            redirect_to('/index.php?page=create-campaign&campaign_id=' . $campaignId);
        }

        $recipients = fetch_test_contacts_by_ids($selected);
        if (count($recipients) === 0) {
            if ($ajax) {
                json_response([
                    'ok' => false,
                    'message' => 'No valid test contacts found.',
                    'campaign_id' => $campaignId,
                ], 422);
            }
            flash_set('error', 'No valid test contacts found.');
            redirect_to('/index.php?page=create-campaign&campaign_id=' . $campaignId);
        }

        $fromEmail = (string) ($smtpConfig['from_address'] ?? '');
        $fromName = $fromName !== '' ? $fromName : 'Mailr';
        $htmlBody = $htmlContent;
        $textBody = $textContent !== '' ? $textContent : strip_tags($htmlContent);

        $sent = 0;
        $failed = [];
        foreach ($recipients as $contact) {
            $toEmail = (string) ($contact['email'] ?? '');
            if ($toEmail === '') {
                continue;
            }
            $personalSubject = personalize_content($subject, $contact);
            $personalHtml = personalize_content($htmlBody, $contact);
            $personalText = personalize_content($textBody, $contact);

            [$ok, $message] = smtp_send($smtpConfig, $fromEmail, $fromName, $toEmail, $personalSubject, $personalHtml, $personalText);
            if ($ok) {
                $sent += 1;
            } else {
                $failed[] = $toEmail . ' (' . $message . ')';
            }
        }

        $details = 'Test send attempted. Sent: ' . $sent . ', Failed: ' . count($failed);
        if (count($failed) > 0) {
            $details .= ' - ' . implode('; ', $failed);
        }
        add_campaign_event($campaignId, 'test_sent', $details);

        if (count($failed) > 0) {
            flash_set('error', 'Some test emails failed. See activity log for details.');
        } else {
            flash_set('success', 'Test emails sent successfully.');
        }

        if ($ajax) {
            json_response([
                'ok' => count($failed) === 0,
                'message' => count($failed) > 0
                    ? 'Some test emails failed. See details below.'
                    : 'Test emails sent successfully.',
                'campaign_id' => $campaignId,
                'sent_count' => $sent,
                'failed_count' => count($failed),
                'failed' => $failed,
                'activity_details' => $details,
                'redirect_url' => '/index.php?page=create-campaign&campaign_id=' . $campaignId,
            ]);
        }
    }

    if ($action === 'publish_campaign') {
        if ($contactListId <= 0) {
            if ($ajax) {
                json_response([
                    'ok' => false,
                    'message' => 'A contact list is required to publish.',
                    'campaign_id' => $campaignId,
                ], 422);
            }
            flash_set('error', 'A contact list is required to publish.');
            redirect_to('/index.php?page=create-campaign&campaign_id=' . $campaignId);
        }

        $status = 'Scheduled';
        $payload['status'] = $status;
        $payload['updated_at'] = date('Y-m-d H:i:s');
        update_campaign($campaignId, $payload);
        $queuedCount = queue_campaign_recipients($campaignId, $contactListId);
        $deliveryResult = [
            'ok' => true,
            'errors' => [],
            'stats' => fetch_campaign_delivery_stats($campaignId),
        ];
        $queueMode = $scheduleAt !== '' ? 'scheduled send' : 'queue-only immediate send';
        add_campaign_event($campaignId, 'published', 'Campaign published (' . $queueMode . '). Queued recipients: ' . $queuedCount . '. Delivery will be processed by the queue worker.');
        flash_set('success', $scheduleAt !== '' ? 'Campaign scheduled and recipients queued.' : 'Campaign published and queued for background delivery.');

        if ($ajax) {
            $stats = $deliveryResult['stats'] ?? fetch_campaign_delivery_stats($campaignId);
            json_response([
                'ok' => (bool) ($deliveryResult['ok'] ?? true),
                'message' => $scheduleAt !== '' ? 'Campaign scheduled and recipients queued.' : 'Campaign published and queued for background delivery.',
                'campaign_id' => $campaignId,
                'published_status' => $status,
                'delivery_stats' => $stats,
                'failed' => $deliveryResult['errors'] ?? [],
                'redirect_url' => '/index.php?page=create-campaign&campaign_id=' . $campaignId,
                'overview_url' => '/index.php?page=campaign-overview&campaign_id=' . $campaignId,
            ], ($deliveryResult['ok'] ?? true) ? 200 : 500);
        }
    }

    if ($action === 'save_campaign') {
        add_campaign_event($campaignId, 'saved', 'Draft saved.');
        flash_set('success', 'Campaign saved as draft.');
    }

    redirect_to('/index.php?page=create-campaign&campaign_id=' . $campaignId);
}

function handle_add_smtp_config(): void
{
    $name = trim((string) ($_POST['name'] ?? ''));
    $host = trim((string) ($_POST['host'] ?? ''));
    $port = (int) ($_POST['port'] ?? 0);
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));
    $encryption = trim((string) ($_POST['encryption'] ?? 'tls'));
    $fromAddress = trim((string) ($_POST['from_address'] ?? ''));

    if ($name === '' || $host === '' || $fromAddress === '') {
        flash_set('error', 'Name, host, and from address are required.');
        redirect_to('/index.php?page=configs-test');
    }

    add_smtp_config([
        'name' => $name,
        'host' => $host,
        'port' => $port,
        'username' => $username,
        'password' => $password,
        'encryption' => $encryption,
        'from_address' => $fromAddress,
        'status' => 'Active',
    ]);

    flash_set('success', 'SMTP config added.');
    redirect_to('/index.php?page=configs-test');
}

function handle_save_settings(): void
{
    $settings = [
        'default_from_name' => trim((string) ($_POST['default_from_name'] ?? '')),
        'reply_to' => trim((string) ($_POST['reply_to'] ?? '')),
        'timezone' => trim((string) ($_POST['timezone'] ?? 'America/New_York')),
        'default_tracking' => trim((string) ($_POST['default_tracking'] ?? 'Open + click tracking')),
    ];

    save_settings($settings);
    flash_set('success', 'Settings updated.');
    redirect_to('/index.php?page=configs-test');
}

function handle_add_test_contact(): void
{
    $email = trim((string) ($_POST['email'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($email === '') {
        flash_set('error', 'Email is required for test contact.');
        redirect_to('/index.php?page=configs-test');
    }

    add_test_contact([
        'email' => $email,
        'name' => $name,
        'notes' => $notes,
    ]);

    flash_set('success', 'Test contact added.');
    redirect_to('/index.php?page=configs-test');
}

function handle_add_contact_list(): void
{
    $name = trim((string) ($_POST['list_name'] ?? ''));
    if ($name === '') {
        flash_set('error', 'List name is required.');
        redirect_to('/index.php?page=manage-contacts');
    }

    create_contact_list($name);
    flash_set('success', 'Contact list created.');
    redirect_to('/index.php?page=manage-contacts');
}

function handle_import_contacts(): void
{
    $listName = trim((string) ($_POST['list_name'] ?? ''));
    $tags = trim((string) ($_POST['tags'] ?? ''));
    if ($listName === '') {
        flash_set('error', 'Provide a list name for import.');
        redirect_to('/index.php?page=manage-contacts');
    }

    if (!isset($_FILES['upload_csv']) || $_FILES['upload_csv']['error'] === UPLOAD_ERR_NO_FILE) {
        flash_set('error', 'Upload a CSV file to import.');
        redirect_to('/index.php?page=manage-contacts');
    }

    $listId = create_contact_list($listName);
    try {
        $report = import_contacts_from_csv_report($listId, $_FILES['upload_csv'], $tags);
    } catch (RuntimeException $e) {
        flash_set('error', $e->getMessage());
        redirect_to('/index.php?page=manage-contacts');
    }

    $inserted = (int) ($report['inserted'] ?? 0);
    $duplicates = (int) ($report['duplicates_skipped'] ?? 0);
    $message = 'Imported ' . $inserted . ' contacts.';
    if ($duplicates > 0) {
        $message .= ' Skipped ' . $duplicates . ' duplicate email(s).';
    }
    flash_set('success', $message);
    redirect_to('/index.php?page=manage-contacts');
}

function handle_append_contacts_to_list(): void
{
    $listId = (int) ($_POST['list_id'] ?? 0);
    $tags = trim((string) ($_POST['tags'] ?? ''));
    if ($listId <= 0) {
        flash_set('error', 'Select a valid contact list.');
        redirect_to('/index.php?page=manage-contacts');
    }

    if (!isset($_FILES['upload_csv']) || $_FILES['upload_csv']['error'] === UPLOAD_ERR_NO_FILE) {
        flash_set('error', 'Upload a CSV file to update the list.');
        redirect_to('/index.php?page=manage-contacts');
    }

    try {
        $report = import_contacts_from_csv_report($listId, $_FILES['upload_csv'], $tags);
    } catch (RuntimeException $e) {
        flash_set('error', $e->getMessage());
        redirect_to('/index.php?page=manage-contacts');
    }

    $inserted = (int) ($report['inserted'] ?? 0);
    $duplicates = (int) ($report['duplicates_skipped'] ?? 0);
    flash_set('success', 'List updated. Added ' . $inserted . ' new contact(s)' . ($duplicates > 0 ? ' and skipped ' . $duplicates . ' duplicate email(s).' : '.'));
    redirect_to('/index.php?page=manage-contacts');
}

function handle_save_email_template(): void
{
    $templateId = (int) ($_POST['template_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $sourceText = trim((string) ($_POST['source_text'] ?? ''));
    $htmlContent = trim((string) ($_POST['html_content'] ?? ''));
    $textContent = trim((string) ($_POST['text_content'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'Active'));
    if (!in_array($status, ['Active', 'Paused'], true)) {
        $status = 'Active';
    }

    if ($name === '') {
        flash_set('error', 'Template name is required.');
        redirect_to('/index.php?page=templates' . ($templateId > 0 ? '&template_id=' . $templateId : ''));
    }

    if ($htmlContent === '') {
        flash_set('error', 'Template HTML content is required.');
        redirect_to('/index.php?page=templates' . ($templateId > 0 ? '&template_id=' . $templateId : ''));
    }

    if ($textContent === '') {
        $textContent = plain_text_from_html($htmlContent);
    }

    $now = date('Y-m-d H:i:s');
    $payload = [
        'name' => $name,
        'category' => $category !== '' ? $category : null,
        'description' => $description !== '' ? $description : null,
        'source_text' => $sourceText !== '' ? $sourceText : null,
        'html_content' => $htmlContent,
        'text_content' => $textContent,
        'status' => $status,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    if ($templateId > 0) {
        unset($payload['created_at']);
        update_email_template($templateId, $payload);
        flash_set('success', 'Template updated.');
    } else {
        $templateId = create_email_template($payload);
        flash_set('success', 'Template created.');
    }

    redirect_to('/index.php?page=templates&template_id=' . $templateId);
}

function handle_delete_email_template(): void
{
    $templateId = (int) ($_POST['template_id'] ?? 0);
    if ($templateId <= 0) {
        flash_set('error', 'Template not found.');
        redirect_to('/index.php?page=templates');
    }

    delete_email_template($templateId);
    flash_set('success', 'Template deleted.');
    redirect_to('/index.php?page=templates');
}

function handle_generate_template_ai(): void
{
    $ajax = is_ajax_request();
    $sourceText = trim((string) ($_POST['source_text'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $tone = trim((string) ($_POST['tone'] ?? 'Modern and polished'));
    $brand = trim((string) ($_POST['brand'] ?? 'Mailr'));
    $accent = trim((string) ($_POST['accent'] ?? '#1f7a6d'));

    $result = generate_email_template_html($sourceText !== '' ? $sourceText : $name, [
        'brand' => $brand,
        'tone' => $tone,
        'category' => $category,
        'accent' => $accent,
    ]);

    if ($ajax) {
        json_response($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    if (($result['ok'] ?? false) === true) {
        flash_set('success', (string) ($result['message'] ?? 'Template generated.'));
    } else {
        flash_set('error', (string) ($result['message'] ?? 'Template generation failed.'));
    }
    redirect_to('/index.php?page=templates');
}
