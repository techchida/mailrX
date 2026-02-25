<?php

declare(strict_types=1);

$campaign = $data['campaign'] ?? null;
$events = $data['events'] ?? [];
$contactList = $data['contact_list'] ?? null;
$recipients = $data['recipients'] ?? [];
$smtpConfig = $data['smtp_config'] ?? null;
$overview = $data['overview'] ?? [];
$deliveryStats = $data['delivery_stats'] ?? null;
$clickStats = $data['click_stats'] ?? ['total_clicks' => 0, 'unique_clickers' => 0];
$recipientQuery = trim((string) ($data['recipient_query'] ?? ($_GET['recipient_q'] ?? '')));
$recipientPagination = $data['recipient_pagination'] ?? ['page' => 1, 'total_pages' => 1, 'total' => count($recipients)];

if (!$campaign):
  ?>
  <section class="page-header">
    <div>
      <h1>Campaign Overview</h1>
      <p>Select a campaign from the dashboard to view delivery status, progress, and recipients.</p>
    </div>
    <div class="actions">
      <a class="button" href="/index.php?page=dashboard">Back to Dashboard</a>
    </div>
  </section>
  <?php
  return;
endif;

$progress = (int) ($overview['progress_percent'] ?? 0);
$stage = (string) ($overview['stage'] ?? 'Unknown');
$recipientTotal = (int) ($contactList['count'] ?? 0);
$processedRecipients = (int) ($overview['processed_recipients'] ?? 0);
$queuedRecipients = (int) ($overview['queued_recipients'] ?? 0);
$sentRecipients = (int) ($overview['sent_recipients'] ?? 0);
$failedRecipients = (int) ($overview['failed_recipients'] ?? 0);
$testsSent = (int) ($overview['tests_sent'] ?? 0);
$status = (string) ($campaign['status'] ?? 'Draft');
$statusClass = status_badge_class($status);
$subject = (string) ($campaign['subject'] ?? '');
$previewText = (string) ($campaign['preview_text'] ?? '');
$fromName = (string) ($campaign['from_name'] ?? '');
$filteredRecipientCount = (int) ($recipientPagination['total'] ?? count($recipients));
$currentRecipientPage = (int) ($recipientPagination['page'] ?? 1);
$recipientTotalPages = (int) ($recipientPagination['total_pages'] ?? 1);
$openUniqueCount = (int) ($deliveryStats['open_unique_count'] ?? 0);
$openTotalCount = (int) ($deliveryStats['open_total_count'] ?? 0);
$openRate = (float) ($deliveryStats['open_rate'] ?? 0);
$clickRate = (float) ($deliveryStats['click_rate'] ?? 0);

$timelineSteps = [
  ['label' => 'Drafted', 'done' => true],
  ['label' => 'Tested', 'done' => $testsSent > 0],
  ['label' => 'Scheduled', 'done' => in_array($status, ['Scheduled', 'Sent'], true)],
  ['label' => 'Sent', 'done' => $status === 'Sent'],
];
$canPause = in_array($status, ['Scheduled', 'Sent'], true) && $queuedRecipients > 0;
$canResume = $status === 'Paused';
?>

<section class="overview-hero">
  <div class="overview-hero-main card">
    <div class="overview-hero-head">
      <div>
        <div class="eyebrow">Campaign Overview</div>
        <h1><?php echo htmlspecialchars((string) $campaign['title']); ?></h1>
        <p><?php echo htmlspecialchars($subject); ?></p>
      </div>
      <div class="overview-hero-actions">
        <span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
        <a class="ghost" href="/index.php?page=create-campaign&campaign_id=<?php echo (int) $campaign['id']; ?>">Edit
          Campaign</a>
        <?php if ($canPause): ?>
          <form method="post">
            <input type="hidden" name="campaign_id" value="<?php echo (int) $campaign['id']; ?>">
            <button class="ghost" type="submit" name="action" value="pause_campaign_delivery">Pause</button>
          </form>
        <?php endif; ?>
        <?php if ($canResume): ?>
          <form method="post">
            <input type="hidden" name="campaign_id" value="<?php echo (int) $campaign['id']; ?>">
            <button class="button" type="submit" name="action" value="resume_campaign_delivery">Resume</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="overview-progress-block">
      <div class="overview-progress-meta">
        <div>
          <div class="overview-progress-label">Delivery Progress</div>
          <div class="overview-progress-stage"><?php echo htmlspecialchars($stage); ?></div>
        </div>
        <div class="overview-progress-value"><?php echo $progress; ?>%</div>
      </div>
      <div class="overview-progress-track" aria-label="Campaign progress">
        <div class="overview-progress-fill" style="width: <?php echo max(0, min(100, $progress)); ?>%"></div>
      </div>
      <div class="overview-timeline" role="list" aria-label="Campaign lifecycle">
        <?php foreach ($timelineSteps as $step): ?>
          <div class="timeline-step <?php echo $step['done'] ? 'done' : ''; ?>" role="listitem">
            <span class="timeline-dot"></span>
            <span><?php echo htmlspecialchars($step['label']); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="overview-meta-grid">
      <div class="overview-meta-item">
        <span>Campaign ID</span>
        <strong>#<?php echo (int) $campaign['id']; ?></strong>
      </div>
      <div class="overview-meta-item">
        <span>Audience</span>
        <strong><?php echo htmlspecialchars((string) ($contactList['name'] ?? ($campaign['list_name'] ?? 'Not selected'))); ?></strong>
      </div>
      <div class="overview-meta-item">
        <span>SMTP</span>
        <strong><?php echo htmlspecialchars((string) ($smtpConfig['name'] ?? 'Not selected')); ?></strong>
      </div>
      <div class="overview-meta-item">
        <span>Schedule</span>
        <strong><?php echo !empty($campaign['schedule_at']) ? htmlspecialchars((string) $campaign['schedule_at']) : 'Immediate on publish'; ?></strong>
      </div>
      <div class="overview-meta-item">
        <span>Tracking</span>
        <strong><?php echo htmlspecialchars((string) ($campaign['tracking'] ?? 'Open + click tracking')); ?></strong>
      </div>
      <div class="overview-meta-item">
        <span>Updated</span>
        <strong><?php echo htmlspecialchars((string) ($campaign['updated_at'] ?? '')); ?></strong>
      </div>
    </div>
  </div>

  <aside class="overview-hero-side">
    <div class="card kpi-stack">
      <div class="kpi-card">
        <div class="kpi-label">Recipients</div>
        <div class="kpi-value"><?php echo $recipientTotal; ?></div>
        <div class="kpi-sub">Total in selected audience list</div>
      </div>
      <div class="kpi-grid">
        <div class="kpi-mini">
          <span>Sent</span>
          <strong><?php echo $sentRecipients; ?></strong>
        </div>
        <div class="kpi-mini">
          <span>Queued</span>
          <strong><?php echo $queuedRecipients; ?></strong>
        </div>
        <div class="kpi-mini">
          <span>Opens</span>
          <strong>
            <?php echo $openUniqueCount; ?><?php if ($sentRecipients > 0): ?>
              (<?php echo number_format($openRate * 100, 1); ?>%)<?php endif; ?>
          </strong>
        </div>
        <div class="kpi-mini">
          <span>Processed</span>
          <strong><?php echo $processedRecipients; ?></strong>
        </div>
        <div class="kpi-mini <?php echo $failedRecipients > 0 ? 'warn' : ''; ?>">
          <span>Failed</span>
          <strong><?php echo $failedRecipients; ?></strong>
        </div>
        <div class="kpi-mini">
          <span>Clicks</span>
          <strong><?php echo (int) ($clickStats['total_clicks'] ?? 0); ?></strong>
        </div>
        <div class="kpi-mini">
          <span>Unique Clickers</span>
          <strong><?php echo (int) ($clickStats['unique_clickers'] ?? 0); ?><?php if ($sentRecipients > 0): ?>
              (<?php echo number_format($clickRate * 100, 1); ?>%)<?php endif; ?></strong>
        </div>
      </div>
    </div>


  </aside>
</section>

<section class="overview-grid">
  <section class="card overview-panel">
    <div class="overview-panel-head">
      <h2>Recipient Progress</h2>
      <span class="chip"><?php echo $recipientTotal; ?> recipients</span>
    </div>
    <div class="recipient-progress-list">
      <div class="recipient-progress-item">
        <div class="recipient-progress-row"><span>Sent</span><strong><?php echo $sentRecipients; ?></strong></div>
        <div class="recipient-progress-track">
          <div class="recipient-progress-fill sent"
            style="width: <?php echo $recipientTotal > 0 ? (int) round(($sentRecipients / $recipientTotal) * 100) : 0; ?>%">
          </div>
        </div>
      </div>
      <div class="recipient-progress-item">
        <div class="recipient-progress-row"><span>Queued</span><strong><?php echo $queuedRecipients; ?></strong></div>
        <div class="recipient-progress-track">
          <div class="recipient-progress-fill queued"
            style="width: <?php echo $recipientTotal > 0 ? (int) round(($queuedRecipients / $recipientTotal) * 100) : 0; ?>%">
          </div>
        </div>
      </div>
      <div class="recipient-progress-item">
        <div class="recipient-progress-row"><span>Failures</span><strong><?php echo $failedRecipients; ?></strong></div>
        <div class="recipient-progress-track">
          <div class="recipient-progress-fill failed"
            style="width: <?php echo max(4, ($recipientTotal > 0 ? (int) round(($failedRecipients / $recipientTotal) * 100) : 0)); ?>%">
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="card overview-panel">
    <div class="overview-panel-head">
      <h2>Recent Activity</h2>
      <span class="chip muted"><?php echo count($events); ?> events</span>
    </div>
    <?php if (count($events) === 0): ?>
      <p class="muted">No events yet for this campaign.</p>
    <?php else: ?>
      <div class="activity-list activity-list-tight">
        <?php foreach ($events as $event): ?>
          <div class="activity-item">
            <div class="activity-type"><?php echo htmlspecialchars((string) $event['type']); ?></div>
            <div class="activity-details"><?php echo htmlspecialchars((string) $event['details']); ?></div>
            <div class="activity-time"><?php echo htmlspecialchars((string) $event['created_at']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</section>

<section class="card overview-panel recipients-panel">
  <div class="overview-panel-head recipients-head">
    <div>
      <h2>Recipients</h2>
      <p class="muted">Audience members associated with this campaign's selected contact list.</p>
      <?php if ($recipientQuery !== ''): ?>
        <p class="muted recipients-search-note">Showing <?php echo count($recipients); ?> of
          <?php echo $filteredRecipientCount; ?> recipients for "<?php echo htmlspecialchars($recipientQuery); ?>".
        </p>
      <?php endif; ?>
    </div>
    <div class="recipients-head-actions">
      <?php if ($contactList): ?>
        <a class="ghost" href="/index.php?action=export_list&list_id=<?php echo (int) $contactList['id']; ?>">Export
          List</a>
      <?php endif; ?>
      <a class="ghost" href="/index.php?page=manage-contacts">Manage Lists</a>
    </div>
  </div>

  <form class="recipients-search-form" method="get" action="/index.php">
    <input type="hidden" name="page" value="campaign-overview">
    <input type="hidden" name="campaign_id" value="<?php echo (int) $campaign['id']; ?>">
    <div class="recipients-search-input">
      <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10 4a6 6 0 1 0 3.9 10.6l4.7 4.7 1.4-1.4-4.7-4.7A6 6 0 0 0 10 4zm0 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8z"
          fill="currentColor" />
      </svg>
      <input type="search" name="recipient_q" value="<?php echo htmlspecialchars($recipientQuery); ?>"
        placeholder="Search recipients by name, email, tags, status..." autocomplete="off">
    </div>
    <input type="hidden" name="recipients_page" value="1">
    <button type="submit" class="ghost">Search</button>
    <?php if ($recipientQuery !== ''): ?>
      <a class="ghost" href="/index.php?page=campaign-overview&campaign_id=<?php echo (int) $campaign['id']; ?>">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (!$contactList): ?>
    <p class="muted">No contact list is attached to this campaign yet.</p>
  <?php elseif (count($recipients) === 0): ?>
    <p class="muted">
      <?php echo $recipientQuery !== '' ? 'No recipients matched your search.' : 'This contact list currently has no recipients.'; ?>
    </p>
  <?php else: ?>
    <div class="recipient-table-wrap">
      <div class="recipient-table">
        <div class="recipient-row recipient-head-row">
          <div>Recipient</div>
          <div>Tags</div>
          <div>Status</div>
          <div>Opens</div>
          <div>Clicks</div>
          <div>Added</div>
        </div>
        <?php foreach ($recipients as $recipient): ?>
          <?php
          $name = trim(((string) ($recipient['first_name'] ?? '')) . ' ' . ((string) ($recipient['last_name'] ?? '')));
          $rawState = (string) ($recipient['status'] ?? '');
          if ($rawState === '') {
            $recipientState = 'Queued';
            if ($status === 'Sent') {
              $recipientState = 'Sent';
            } elseif ($status === 'Draft') {
              $recipientState = 'Pending';
            }
          } else {
            $recipientState = ucfirst($rawState);
          }
          $addedValue = (string) ($recipient['sent_at'] ?? $recipient['updated_at'] ?? $recipient['created_at'] ?? '');
          ?>
          <div class="recipient-row">
            <div>
              <div class="recipient-primary">
                <?php echo htmlspecialchars($name !== '' ? $name : (string) $recipient['email']); ?>
              </div>
              <div class="recipient-secondary"><?php echo htmlspecialchars((string) $recipient['email']); ?></div>
              <?php if (!empty($recipient['last_error'])): ?>
                <div class="recipient-error"><?php echo htmlspecialchars((string) $recipient['last_error']); ?></div>
              <?php endif; ?>
            </div>
            <div>
              <?php if (!empty($recipient['tags'])): ?>
                <span class="tag-pill"><?php echo htmlspecialchars((string) $recipient['tags']); ?></span>
              <?php else: ?>
                <span class="recipient-secondary">None</span>
              <?php endif; ?>
            </div>
            <div>
              <span
                class="status <?php echo status_badge_class($recipientState); ?>"><?php echo htmlspecialchars($recipientState); ?></span>
            </div>
            <div class="recipient-secondary"><?php echo (int) ($recipient['open_count'] ?? 0); ?></div>
            <div class="recipient-secondary"><?php echo (int) ($recipient['click_count'] ?? 0); ?></div>
            <div class="recipient-secondary"><?php echo htmlspecialchars($addedValue); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($recipientTotalPages > 1): ?>
      <?php
      $recipientPrev = max(1, $currentRecipientPage - 1);
      $recipientNext = min($recipientTotalPages, $currentRecipientPage + 1);
      $baseRecipientQuery = '/index.php?page=campaign-overview&campaign_id=' . (int) $campaign['id']
        . ($recipientQuery !== '' ? '&recipient_q=' . rawurlencode($recipientQuery) : '');
      ?>
      <div class="list-pagination">
        <a class="ghost <?php echo $currentRecipientPage <= 1 ? 'is-disabled' : ''; ?>"
          href="<?php echo $currentRecipientPage <= 1 ? '#' : ($baseRecipientQuery . '&recipients_page=' . $recipientPrev); ?>">Prev</a>
        <span class="chip muted">Page <?php echo $currentRecipientPage; ?> / <?php echo $recipientTotalPages; ?> ·
          <?php echo $filteredRecipientCount; ?> recipients</span>
        <a class="ghost <?php echo $currentRecipientPage >= $recipientTotalPages ? 'is-disabled' : ''; ?>"
          href="<?php echo $currentRecipientPage >= $recipientTotalPages ? '#' : ($baseRecipientQuery . '&recipients_page=' . $recipientNext); ?>">Next</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>