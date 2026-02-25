<?php

declare(strict_types=1);

$campaigns = $data['campaigns'] ?? [];
$allCampaigns = $data['campaigns_all'] ?? $campaigns;
$campaignStatsMap = $data['campaign_stats_map'] ?? [];
$pagination = $data['campaign_pagination'] ?? ['page' => 1, 'total_pages' => 1, 'total' => count($allCampaigns)];

$statusCounts = [
    'Draft' => 0,
    'Scheduled' => 0,
    'Sent' => 0,
];

foreach ($allCampaigns as $campaign) {
    $status = $campaign['status'] ?? 'Draft';
    if (!array_key_exists($status, $statusCounts)) {
        $statusCounts[$status] = 0;
    }
    $statusCounts[$status] += 1;
}

$totalCampaigns = count($allCampaigns);
$totalAudience = 0;
$latestUpdated = null;
foreach ($allCampaigns as $campaign) {
    $listName = (string) ($campaign['list_name'] ?? '');
    if ($listName !== '') {
        $totalAudience += 1;
    }
    $updatedAt = (string) ($campaign['updated_at'] ?? '');
    if ($updatedAt !== '' && ($latestUpdated === null || strcmp($updatedAt, $latestUpdated) > 0)) {
        $latestUpdated = $updatedAt;
    }
}
?>

<svg aria-hidden="true" class="ui-icon-sprite dashboard-sprite">
  <symbol id="d-icon-chart" viewBox="0 0 24 24">
    <path d="M4 19h16v2H4v-2zm2-2V9h3v8H6zm5 0V4h3v13h-3zm5 0v-6h3v6h-3z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-rocket" viewBox="0 0 24 24">
    <path d="M14 3c4 0 7 3 7 7 0 4.6-3.2 8.8-8.6 10.7l-1.8-1.8C12.5 13.2 16.7 10 21 10c0-2.8-2.2-5-5-5-4.3 0-7.5 4.2-8.9 10.4L5.3 13.6C7.2 8.2 11.4 5 16 5V3h-2zm-9.5 14L7 19.5 4.5 22 2 19.5 4.5 17z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-mail" viewBox="0 0 24 24">
    <path d="M3 6h18v12H3V6zm2 2v.4l7 4.8 7-4.8V8l-7 4.8L5 8z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-clock" viewBox="0 0 24 24">
    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 5h-2v6l5 3 1-1.7-4-2.3V7z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-users" viewBox="0 0 24 24">
    <path d="M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm-8 1a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm8 1c-2.2 0-4 1.3-4 3v2h8v-2c0-1.7-1.8-3-4-3zM8 14c-2.8 0-5 1.6-5 3.5V19h7v-1c0-1.2.5-2.2 1.4-3C10.6 14.4 9.3 14 8 14z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-bolt" viewBox="0 0 24 24">
    <path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-eye" viewBox="0 0 24 24">
    <path d="M12 5c5.5 0 9.8 4.1 11 7-1.2 2.9-5.5 7-11 7S2.2 14.9 1 12c1.2-2.9 5.5-7 11-7zm0 2c-4.2 0-7.7 3-9 5 1.3 2 4.8 5 9 5s7.7-3 9-5c-1.3-2-4.8-5-9-5zm0 2.5a2.5 2.5 0 1 1-2.5 2.5A2.5 2.5 0 0 1 12 9.5z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-edit" viewBox="0 0 24 24">
    <path d="M4 20l4.5-1 9-9-3.5-3.5-9 9L4 20zm10-13.5l3.5 3.5 1.5-1.5a1.5 1.5 0 0 0 0-2.1l-1.4-1.4a1.5 1.5 0 0 0-2.1 0L14 6.5z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-plus" viewBox="0 0 24 24">
    <path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5z" fill="currentColor"/>
  </symbol>
  <symbol id="d-icon-send" viewBox="0 0 24 24">
    <path d="M3 11.5L21 3l-6.8 18-2.8-6.9L3 11.5zm10 1.2l1.4 3.4L17.7 7 8.6 11.3l3.4 1.4 5-5-.3 1.2-3.7 3.8z" fill="currentColor"/>
  </symbol>
</svg>

<section class="dashboard-hero card">
  <div class="dashboard-hero-main">
    <div class="eyebrow"><svg class="icon"><use href="#d-icon-chart"></use></svg>Campaign Control Center</div>
    <h1>Campaigns Dashboard</h1>
    <p>Monitor campaign health, move from draft to publish faster, and keep delivery operations visible in one place.</p>
    <div class="dashboard-hero-actions">
      <a class="button" href="/index.php?page=create-campaign"><svg class="icon"><use href="#d-icon-plus"></use></svg>Create Campaign</a>
      <a class="ghost" href="/index.php?page=configs-test"><svg class="icon"><use href="#d-icon-bolt"></use></svg>SMTP & Test Setup</a>
      <a class="ghost" href="/index.php?page=manage-contacts"><svg class="icon"><use href="#d-icon-users"></use></svg>Manage Contacts</a>
    </div>
  </div>
  <div class="dashboard-hero-side">
    <div class="hero-glance-card">
      <div class="hero-glance-label">Total Campaigns</div>
      <div class="hero-glance-value"><?php echo $totalCampaigns; ?></div>
      <div class="hero-glance-foot">Across all states</div>
    </div>
    <div class="hero-glance-grid">
      <div class="hero-mini"><span>Drafts</span><strong><?php echo (int) $statusCounts['Draft']; ?></strong></div>
      <div class="hero-mini"><span>Scheduled</span><strong><?php echo (int) $statusCounts['Scheduled']; ?></strong></div>
      <div class="hero-mini"><span>Sent</span><strong><?php echo (int) $statusCounts['Sent']; ?></strong></div>
      <div class="hero-mini"><span>Last Update</span><strong><?php echo htmlspecialchars((string) ($latestUpdated ?? '—')); ?></strong></div>
    </div>
  </div>
</section>

<section class="dashboard-kpi-grid">
  <div class="dashboard-kpi-card card kpi-accent-green">
    <div class="dashboard-kpi-head"><span class="dashboard-kpi-icon"><svg class="icon"><use href="#d-icon-mail"></use></svg></span><span>Ready To Launch</span></div>
    <div class="dashboard-kpi-value"><?php echo (int) $statusCounts['Draft']; ?></div>
    <div class="dashboard-kpi-sub">Draft campaigns waiting for testing or publishing.</div>
  </div>
  <div class="dashboard-kpi-card card kpi-accent-blue">
    <div class="dashboard-kpi-head"><span class="dashboard-kpi-icon"><svg class="icon"><use href="#d-icon-clock"></use></svg></span><span>Scheduled Pipeline</span></div>
    <div class="dashboard-kpi-value"><?php echo (int) $statusCounts['Scheduled']; ?></div>
    <div class="dashboard-kpi-sub">Queued sends with delivery windows or scheduled times.</div>
  </div>
  <div class="dashboard-kpi-card card kpi-accent-gold">
    <div class="dashboard-kpi-head"><span class="dashboard-kpi-icon"><svg class="icon"><use href="#d-icon-send"></use></svg></span><span>Delivered Campaigns</span></div>
    <div class="dashboard-kpi-value"><?php echo (int) $statusCounts['Sent']; ?></div>
    <div class="dashboard-kpi-sub">Campaigns already published and processed.</div>
  </div>
</section>

<section class="card dashboard-campaigns-card">
  <div class="dashboard-campaigns-head">
    <div>
      <h2>Campaign List</h2>
      <p class="muted">Delivery performance with quick access to overview and editing.</p>
    </div>
    <div class="chip-row">
      <span class="chip">All <?php echo $totalCampaigns; ?></span>
      <span class="chip muted">Draft <?php echo (int) $statusCounts['Draft']; ?></span>
      <span class="chip muted">Scheduled <?php echo (int) $statusCounts['Scheduled']; ?></span>
      <span class="chip muted">Sent <?php echo (int) $statusCounts['Sent']; ?></span>
    </div>
  </div>

  <?php if ($totalCampaigns === 0): ?>
    <div class="dashboard-empty">
      <div class="dashboard-empty-icon"><svg class="icon"><use href="#d-icon-rocket"></use></svg></div>
      <h3>No campaigns yet</h3>
      <p>Create your first campaign to start building your sending workflow.</p>
      <a class="button" href="/index.php?page=create-campaign"><svg class="icon"><use href="#d-icon-plus"></use></svg>Create Campaign</a>
    </div>
  <?php else: ?>
    <div class="dashboard-list-grid">
      <?php foreach ($campaigns as $campaign): ?>
        <?php
        $stats = $campaignStatsMap[(int) ($campaign['id'] ?? 0)] ?? [
            'total_recipients' => 0,
            'sent_count' => 0,
            'open_rate' => 0.0,
            'click_rate' => 0.0,
        ];
        $sentCount = (int) ($stats['sent_count'] ?? 0);
        $totalRecipients = (int) ($stats['total_recipients'] ?? 0);
        $openRate = (float) ($stats['open_rate'] ?? 0);
        $clickRate = (float) ($stats['click_rate'] ?? 0);
        $subjectLine = trim((string) ($campaign['subject'] ?? ''));
        if ($subjectLine === '') {
            $subjectLine = 'No subject';
        }
        ?>
        <article class="dashboard-campaign-item">
          <div class="dashboard-campaign-item-main">
            <div class="dashboard-campaign-item-top">
              <div class="dashboard-campaign-title-group">
                <h3><?php echo htmlspecialchars((string) $campaign['title']); ?></h3>
                <p class="dashboard-campaign-subject"><?php echo htmlspecialchars($subjectLine); ?></p>
              </div>
              <span class="status <?php echo status_badge_class((string) $campaign['status']); ?>"><?php echo htmlspecialchars((string) $campaign['status']); ?></span>
            </div>
            <div class="dashboard-campaign-item-meta">
              <span class="dashboard-campaign-meta-badge"><svg class="icon"><use href="#d-icon-users"></use></svg><?php echo htmlspecialchars((string) ($campaign['list_name'] ?? 'No audience selected')); ?></span>
              <span class="dashboard-campaign-meta-badge"><svg class="icon"><use href="#d-icon-clock"></use></svg><?php echo htmlspecialchars((string) ($campaign['updated_at'] ?? '')); ?></span>
              <span class="dashboard-campaign-meta-badge"><svg class="icon"><use href="#d-icon-mail"></use></svg>#<?php echo (int) $campaign['id']; ?></span>
            </div>
            <div class="dashboard-campaign-metrics">
              <div class="dashboard-campaign-metric">
                <span class="metric-icon"><svg class="icon"><use href="#d-icon-send"></use></svg></span>
                <span>Sent</span>
                <strong><?php echo $sentCount; ?> / <?php echo $totalRecipients; ?></strong>
              </div>
              <div class="dashboard-campaign-metric">
                <span class="metric-icon"><svg class="icon"><use href="#d-icon-eye"></use></svg></span>
                <span>Open Rate</span>
                <strong><?php echo number_format($openRate * 100, 1); ?>%</strong>
              </div>
              <div class="dashboard-campaign-metric">
                <span class="metric-icon"><svg class="icon"><use href="#d-icon-bolt"></use></svg></span>
                <span>Click Rate</span>
                <strong><?php echo number_format($clickRate * 100, 1); ?>%</strong>
              </div>
            </div>
          </div>
          <div class="dashboard-campaign-item-actions">
            <a class="ghost compact-action" href="/index.php?page=campaign-overview&campaign_id=<?php echo (int) $campaign['id']; ?>"><svg class="icon"><use href="#d-icon-eye"></use></svg><span>Overview</span></a>
            <a class="ghost compact-action" href="/index.php?page=create-campaign&campaign_id=<?php echo (int) $campaign['id']; ?>"><svg class="icon"><use href="#d-icon-edit"></use></svg><span>Edit</span></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
      <div class="list-pagination">
        <?php
        $pageNo = (int) ($pagination['page'] ?? 1);
        $totalPages = (int) ($pagination['total_pages'] ?? 1);
        $prev = max(1, $pageNo - 1);
        $next = min($totalPages, $pageNo + 1);
        ?>
        <a class="ghost <?php echo $pageNo <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo $pageNo <= 1 ? '#' : '/index.php?page=dashboard&campaign_page=' . $prev; ?>">Prev</a>
        <span class="chip muted">Page <?php echo $pageNo; ?> / <?php echo $totalPages; ?></span>
        <a class="ghost <?php echo $pageNo >= $totalPages ? 'is-disabled' : ''; ?>" href="<?php echo $pageNo >= $totalPages ? '#' : '/index.php?page=dashboard&campaign_page=' . $next; ?>">Next</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
