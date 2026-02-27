<?php

declare(strict_types=1);

require __DIR__ . '/../src/app.php';

load_env_file();
ensure_storage();
$db = db();

$batchSize = max(1, (int) (getenv('MAILR_SEND_BATCH_SIZE') ?: 50));
$delayMs = max(0, (int) (getenv('MAILR_SEND_BATCH_DELAY_MS') ?: 150));
$concurrency = max(1, (int) (getenv('MAILR_WORKER_CONCURRENCY') ?: 1));

if ($concurrency <= 1) {
    $results = process_due_scheduled_campaigns($batchSize, $delayMs);
} else {
    $stmt = db()->prepare(
        "SELECT id
         FROM campaigns
         WHERE status = 'Scheduled'
           AND (schedule_at IS NULL OR schedule_at <= :now)
         ORDER BY schedule_at ASC"
    );
    $stmt->execute(['now' => date('Y-m-d H:i:s')]);
    $campaignIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

    if ($campaignIds === []) {
        fwrite(STDOUT, "No scheduled campaigns due.\n");
        exit(0);
    }

    if (!function_exists('pcntl_fork')) {
        fwrite(STDOUT, "pcntl extension unavailable; falling back to sequential mode.\n");
        $results = process_due_scheduled_campaigns($batchSize, $delayMs);
    } else {
        $results = [];
        $tmpDir = sys_get_temp_dir();
        $active = [];

        $runCampaign = static function (int $campaignId) use ($batchSize, $delayMs): array {
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
            return ['campaign_id' => $campaignId, 'result' => $result];
        };

        while ($campaignIds !== [] || $active !== []) {
            while ($campaignIds !== [] && count($active) < $concurrency) {
                $campaignId = array_shift($campaignIds);
                $resultFile = $tmpDir . '/mailr_worker_' . getmypid() . '_' . $campaignId . '_' . bin2hex(random_bytes(4)) . '.json';
                $pid = pcntl_fork();
                if ($pid === -1) {
                    fwrite(STDERR, "Failed to fork worker for campaign #{$campaignId}.\n");
                    continue;
                }
                if ($pid === 0) {
                    $payload = $runCampaign($campaignId);
                    file_put_contents($resultFile, json_encode($payload, JSON_UNESCAPED_SLASHES));
                    exit(0);
                }
                $active[$pid] = $resultFile;
            }

            $finishedPid = pcntl_wait($status);
            if ($finishedPid > 0 && isset($active[$finishedPid])) {
                $resultFile = $active[$finishedPid];
                unset($active[$finishedPid]);
                if (is_file($resultFile)) {
                    $content = file_get_contents($resultFile);
                    @unlink($resultFile);
                    if ($content !== false) {
                        $decoded = json_decode($content, true);
                        if (is_array($decoded)) {
                            $results[] = $decoded;
                        }
                    }
                }
            }
        }
    }
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
