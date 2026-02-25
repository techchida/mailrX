<?php

declare(strict_types=1);

require __DIR__ . '/../src/app.php';

load_env_file();
ensure_storage();
$db = db();

$batchSize = max(1, (int) (getenv('MAILR_SEND_BATCH_SIZE') ?: 50));
$delayMs = max(0, (int) (getenv('MAILR_SEND_BATCH_DELAY_MS') ?: 150));

$lockName = 'mailr_queue_worker';
if (!db_named_lock($lockName, 0)) {
    fwrite(STDOUT, "Queue worker already running.\n");
    exit(0);
}

try {
    $results = process_due_scheduled_campaigns($batchSize, $delayMs);
} finally {
    db_named_unlock($lockName);
}

if (count($results) === 0) {
    fwrite(STDOUT, "No scheduled campaigns due.\n");
    exit(0);
}

foreach ($results as $row) {
    $campaignId = (int) ($row['campaign_id'] ?? 0);
    $result = $row['result'] ?? [];
    $stats = $result['stats'] ?? [];
    $line = sprintf(
        "Campaign #%d processed | sent=%d failed=%d queued=%d\n",
        $campaignId,
        (int) ($stats['sent_count'] ?? 0),
        (int) ($stats['failed_count'] ?? 0),
        (int) (($stats['queued_count'] ?? 0) + ($stats['processing_count'] ?? 0))
    );
    fwrite(STDOUT, $line);
}
