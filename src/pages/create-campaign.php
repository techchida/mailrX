<?php

declare(strict_types=1);

$campaign = $data['campaign'] ?? null;
$smtpConfigs = $data['smtp_configs'] ?? [];
$contactLists = $data['contact_lists'] ?? [];
$testContacts = $data['test_contacts'] ?? [];
$settings = $data['settings'] ?? [];
$events = $data['events'] ?? [];
$savedTemplates = $data['templates'] ?? [];

$audienceMode = $campaign['audience_mode'] ?? 'list';
$scheduleValue = '';
if (!empty($campaign['schedule_at'])) {
  $scheduleValue = date('Y-m-d\TH:i', strtotime((string) $campaign['schedule_at']));
}

$defaultFromName = $campaign['from_name'] ?? ($settings['default_from_name'] ?? '');
$defaultTemplate = '<h2>Hello {{first_name}},</h2><p>We saved a spot for you in this week\'s campaign.</p><p><strong>What\'s new:</strong></p><ul><li>Fresh dashboard insights</li><li>Automated follow-ups</li><li>Live delivery checks</li></ul><p><a href="{{cta_url}}">Explore the update</a></p><p>-- Mailr Team</p>';
$initialHtml = (string) ($campaign['html_content'] ?? '');
if ($initialHtml === '') {
  $initialHtml = $defaultTemplate;
}
$campaignStatus = (string) ($campaign['status'] ?? 'Draft');

$builtinTemplateMap = [
  'starter' => [
    'id' => 'starter',
    'name' => 'Starter Promo',
    'description' => 'Offer + CTA layout with quick bullets.',
    'icon' => 'icon-spark',
    'html' => '<h2>Hello {{first_name}},</h2><p>We saved a spot for you in this week\'s campaign.</p><p><strong>What\'s new:</strong></p><ul><li>Fresh dashboard insights</li><li>Automated follow-ups</li><li>Live delivery checks</li></ul><p><a href="{{cta_url}}">Explore the update</a></p><p>-- Mailr Team</p>',
  ],
  'product' => [
    'id' => 'product',
    'name' => 'Product Update',
    'description' => 'Feature release announcement format.',
    'icon' => 'icon-server',
    'html' => '<h2>New in Mailr</h2><p>Hi {{first_name}}, we just shipped new tools to help you deliver faster.</p><p><strong>Highlights</strong></p><ul><li>Segment-aware personalization</li><li>Campaign performance snapshots</li><li>Delivery health alerts</li></ul><p><a href="{{cta_url}}">See the release notes</a></p>',
  ],
  'event' => [
    'id' => 'event',
    'name' => 'Event Invite',
    'description' => 'Simple invite with date and CTA.',
    'icon' => 'icon-clock',
    'html' => '<h2>You\'re invited</h2><p>{{first_name}}, join us for a 30-minute walkthrough of Mailr\'s latest updates.</p><p><strong>When:</strong> Thursday at 2pm ET</p><p><strong>Where:</strong> Live webinar</p><p><a href="{{cta_url}}">Reserve your seat</a></p>',
  ],
  'blank' => [
    'id' => 'blank',
    'name' => 'Blank Canvas',
    'description' => 'Start from scratch.',
    'icon' => 'icon-pen',
    'html' => '<p>Start writing your email here...</p>',
  ],
];

$templateOptions = [];
foreach ($savedTemplates as $tpl) {
  $html = trim((string) ($tpl['html_content'] ?? ''));
  if ($html === '') {
    continue;
  }
  $templateOptions[] = [
    'id' => 'db:' . (int) $tpl['id'],
    'name' => (string) ($tpl['name'] ?? ('Template #' . (int) ($tpl['id'] ?? 0))),
    'description' => (string) ($tpl['description'] ?? ''),
    'icon' => 'icon-mail',
    'html' => $html,
  ];
}
if ($templateOptions === []) {
  $templateOptions = array_values($builtinTemplateMap);
} else {
  $templateOptions[] = $builtinTemplateMap['blank'];
}
$templateGalleryItems = array_slice($templateOptions, 0, 4);
?>

<svg aria-hidden="true" class="ui-icon-sprite">
  <symbol id="icon-pen" viewBox="0 0 24 24">
    <path
      d="M4 20l4.5-1 9-9-3.5-3.5-9 9L4 20zM13.5 4.5l3.5 3.5 1.5-1.5a1.5 1.5 0 0 0 0-2.1l-1.4-1.4a1.5 1.5 0 0 0-2.1 0l-1.5 1.5z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-mail" viewBox="0 0 24 24">
    <path d="M3 6h18v12H3V6zm2 2v.4l7 4.8 7-4.8V8l-7 4.8L5 8z" fill="currentColor" />
  </symbol>
  <symbol id="icon-server" viewBox="0 0 24 24">
    <path d="M4 5h16v5H4V5zm0 9h16v5H4v-5zm3-6a1 1 0 1 0 0 .01V8zm0 9a1 1 0 1 0 0 .01V17z" fill="currentColor" />
  </symbol>
  <symbol id="icon-users" viewBox="0 0 24 24">
    <path
      d="M16 11a3 3 0 1 0-2.999-3A3 3 0 0 0 16 11zM8 12a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm8 1c-2.2 0-4 1.3-4 3v2h8v-2c0-1.7-1.8-3-4-3zM8 14c-2.8 0-5 1.6-5 3.5V19h7v-1c0-1.2.5-2.2 1.4-3C10.6 14.4 9.3 14 8 14z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-send" viewBox="0 0 24 24">
    <path d="M3 11.5L21 3l-6.8 18-2.8-6.9L3 11.5zm10 1.2l1.4 3.4L17.7 7 8.6 11.3l3.4 1.4 5-5-.3 1.2-3.7 3.8z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-checklist" viewBox="0 0 24 24">
    <path
      d="M9 7h11v2H9V7zm0 8h11v2H9v-2zM4.7 8.7L3.3 7.3l1.4-1.4 1 1 2.5-2.5 1.4 1.4-3.9 3.9-2-2zm0 8L3.3 15.3l1.4-1.4 1 1 2.5-2.5 1.4 1.4-3.9 3.9-2-2z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-spark" viewBox="0 0 24 24">
    <path
      d="M12 2l1.6 4.4L18 8l-4.4 1.6L12 14l-1.6-4.4L6 8l4.4-1.6L12 2zm6 10l.9 2.1L21 15l-2.1.9L18 18l-.9-2.1L15 15l2.1-.9L18 12zM6 14l1.1 2.9L10 18l-2.9 1.1L6 22l-1.1-2.9L2 18l2.9-1.1L6 14z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-clock" viewBox="0 0 24 24">
    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 11h-6v-2h4V6h2v7z" fill="currentColor" />
  </symbol>
  <symbol id="icon-bold" viewBox="0 0 24 24">
    <path d="M8 4h6a4 4 0 0 1 0 8H8V4zm0 10h7a4 4 0 0 1 0 8H8v-8zm4-8H10v4h2a2 2 0 0 0 0-4zm1 10h-3v4h3a2 2 0 0 0 0-4z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-italic" viewBox="0 0 24 24">
    <path d="M10 4v2h3.2l-4.4 12H6v2h8v-2h-3.2l4.4-12H18V4h-8z" fill="currentColor" />
  </symbol>
  <symbol id="icon-underline" viewBox="0 0 24 24">
    <path d="M8 4v7a4 4 0 0 0 8 0V4h-2v7a2 2 0 0 1-4 0V4H8zm-2 14h12v2H6v-2z" fill="currentColor" />
  </symbol>
  <symbol id="icon-strike" viewBox="0 0 24 24">
    <path
      d="M6 11h12v2H6v-2zm6-7c-3 0-5 1.6-5 4 0 1.3.7 2.4 2 3.1h3.9c1.3.4 2.1.9 2.1 1.9 0 1.2-1.1 2-3 2-1.8 0-3.1-.7-4-1.5l-1.4 1.5C7.7 18 9.6 19 12 19c3.3 0 5.2-1.7 5.2-4.1 0-1.1-.5-2-1.4-2.7H8.9c-.9-.4-1.4-.9-1.4-1.7 0-1.1 1.1-1.8 2.6-1.8 1.6 0 2.8.5 3.8 1.3l1.3-1.6C14 4.8 12.4 4 10.1 4H12z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-undo" viewBox="0 0 24 24">
    <path d="M12 5a8 8 0 0 1 7.7 6h-2.1A6 6 0 0 0 12 7H7.8l2.6 2.6L9 11 4 6l5-5 1.4 1.4L7.8 5H12z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-redo" viewBox="0 0 24 24">
    <path d="M12 5a8 8 0 0 0-7.7 6h2.1A6 6 0 0 1 12 7h4.2l-2.6 2.6L15 11l5-5-5-5-1.4 1.4L16.2 5H12z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-list-bullets" viewBox="0 0 24 24">
    <path
      d="M9 6h11v2H9V6zm0 5h11v2H9v-2zm0 5h11v2H9v-2zM5 7.5A1.5 1.5 0 1 1 3.5 6 1.5 1.5 0 0 1 5 7.5zm0 5A1.5 1.5 0 1 1 3.5 11 1.5 1.5 0 0 1 5 12.5zm0 5A1.5 1.5 0 1 1 3.5 16 1.5 1.5 0 0 1 5 17.5z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-list-numbers" viewBox="0 0 24 24">
    <path
      d="M9 6h11v2H9V6zm0 5h11v2H9v-2zm0 5h11v2H9v-2zM3 6h3v1H5v1h1v1H3V8h1V7H3V6zm0 5h2.7v1H4v.5h1.7V14H3v-1.2c0-.7.4-1.1 1.1-1.1H5v-.2H3v-.5zm0 5h2.8c0 1.3-.8 2-2.2 2-.9 0-1.6-.3-2.1-.8l.7-.8c.4.4.8.6 1.4.6.5 0 .8-.2.9-.6H3v-.4h1.5c0-.4-.3-.6-.8-.6-.3 0-.7.1-1 .3l-.6-.9c.5-.4 1.1-.5 1.8-.5 1.2 0 2 .5 2.2 1.5H3V16z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-align-left" viewBox="0 0 24 24">
    <path d="M4 6h14v2H4V6zm0 4h10v2H4v-2zm0 4h14v2H4v-2zm0 4h10v2H4v-2z" fill="currentColor" />
  </symbol>
  <symbol id="icon-align-center" viewBox="0 0 24 24">
    <path d="M5 6h14v2H5V6zm3 4h8v2H8v-2zm-3 4h14v2H5v-2zm3 4h8v2H8v-2z" fill="currentColor" />
  </symbol>
  <symbol id="icon-align-right" viewBox="0 0 24 24">
    <path d="M6 6h14v2H6V6zm10 4h4v2h-4v-2zM6 14h14v2H6v-2zm10 4h4v2h-4v-2z" fill="currentColor" />
  </symbol>
  <symbol id="icon-align-justify" viewBox="0 0 24 24">
    <path d="M4 6h16v2H4V6zm0 4h16v2H4v-2zm0 4h16v2H4v-2zm0 4h16v2H4v-2z" fill="currentColor" />
  </symbol>
  <symbol id="icon-link" viewBox="0 0 24 24">
    <path
      d="M10.6 13.4l-1.4-1.4 5.2-5.2a3 3 0 1 1 4.2 4.2l-2.1 2.1-1.4-1.4 2.1-2.1a1 1 0 0 0-1.4-1.4l-5.2 5.2zM13.4 10.6l1.4 1.4-5.2 5.2a3 3 0 0 1-4.2-4.2l2.1-2.1 1.4 1.4-2.1 2.1a1 1 0 1 0 1.4 1.4l5.2-5.2z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-image" viewBox="0 0 24 24">
    <path d="M4 5h16v14H4V5zm2 2v10h12V7H6zm2 8l2.5-3 2 2.5 3-4L18 15H8zm2-5a1.5 1.5 0 1 0-1.5-1.5A1.5 1.5 0 0 0 10 10z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-erase" viewBox="0 0 24 24">
    <path
      d="M15.1 4.3l4.6 4.6-8.5 8.5H6.6L2 12.8l8.5-8.5a3.3 3.3 0 0 1 4.6 0zM9.4 16l7.5-7.5-3.2-3.2-7.5 7.5 2 2h1.2zM13 19h9v2h-9v-2z"
      fill="currentColor" />
  </symbol>
  <symbol id="icon-plus" viewBox="0 0 24 24">
    <path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5z" fill="currentColor" />
  </symbol>
</svg>

<section class="page-header page-header-create">
  <div>
    <div class="eyebrow">Campaign Builder</div>
    <h1>Create Campaign</h1>
    <p>Compose your email, configure delivery, choose the audience, and send tests before publishing.</p>
  </div>
  <div class="header-meta">
    <div class="status-pill">
      <div class="pill-label">Status</div>
      <div id="campaignStatusPill" class="pill-value <?php echo status_badge_class($campaignStatus); ?>">
        <?php echo htmlspecialchars($campaignStatus); ?>
      </div>
    </div>
    <?php if ($campaign): ?>
      <div class="meta-badge">Campaign #<?php echo (int) $campaign['id']; ?></div>
      <a class="ghost" href="/index.php?page=campaign-overview&campaign_id=<?php echo (int) $campaign['id']; ?>">Open
        Overview</a>
    <?php endif; ?>
  </div>
</section>

<nav class="builder-steps" aria-label="Create campaign steps">
  <a href="#step-basics" class="builder-step">
    <span class="builder-step-icon"><svg class="icon">
        <use href="#icon-pen"></use>
      </svg></span>
    <span class="builder-step-num">1</span>
    <span>Basics</span>
  </a>
  <a href="#step-compose" class="builder-step">
    <span class="builder-step-icon"><svg class="icon">
        <use href="#icon-mail"></use>
      </svg></span>
    <span class="builder-step-num">2</span>
    <span>Compose</span>
  </a>
  <a href="#step-delivery" class="builder-step">
    <span class="builder-step-icon"><svg class="icon">
        <use href="#icon-server"></use>
      </svg></span>
    <span class="builder-step-num">3</span>
    <span>SMTP</span>
  </a>
  <a href="#step-audience" class="builder-step">
    <span class="builder-step-icon"><svg class="icon">
        <use href="#icon-users"></use>
      </svg></span>
    <span class="builder-step-num">4</span>
    <span>Audience</span>
  </a>
  <a href="#step-send" class="builder-step">
    <span class="builder-step-icon"><svg class="icon">
        <use href="#icon-send"></use>
      </svg></span>
    <span class="builder-step-num">5</span>
    <span>Send & Publish</span>
  </a>
</nav>

<form class="form-grid campaign-builder-form" method="post" enctype="multipart/form-data">
  <input type="hidden" name="campaign_id" value="<?php echo (int) ($campaign['id'] ?? 0); ?>" />

  <div class="campaign-builder-layout">
    <div class="campaign-builder-main">
      <section class="card section-card" id="step-basics">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">Step 1</div>
            <h2>Campaign Basics</h2>
            <p class="muted">Set the campaign name and inbox-facing metadata.</p>
          </div>
          <button class="ghost" type="submit" name="action" value="save_campaign" data-loading-text="Saving...">Save
            Draft</button>
        </div>
        <div class="field-grid">
          <label class="field field-with-meta">
            <span class="field-label-row">Campaign Title
              <?php echo ui_info_popover('Internal name used in your dashboard.'); ?></span>
            <input id="campaignTitle" type="text" name="title"
              value="<?php echo htmlspecialchars((string) ($campaign['title'] ?? '')); ?>"
              placeholder="e.g. February Promo" required />
          </label>
          <label class="field field-with-meta">
            <span>Email Subject</span>
            <input id="campaignSubject" type="text" name="subject"
              value="<?php echo htmlspecialchars((string) ($campaign['subject'] ?? '')); ?>"
              placeholder="e.g. Love the savings this week" required />
            <span class="field-meta"><span
                class="helper-inline"><?php echo ui_info_popover('Personalization supported, e.g. {{first_name}} and custom CSV variables like {{plan}}.'); ?></span><span
                class="counter" id="subjectCounter">0</span></span>
          </label>
          <label class="field field-with-meta">
            <span>Preview Text</span>
            <input id="campaignPreviewText" type="text" name="preview_text"
              value="<?php echo htmlspecialchars((string) ($campaign['preview_text'] ?? '')); ?>"
              placeholder="Short snippet for inbox preview" />
            <span class="field-meta"><span
                class="helper-inline"><?php echo ui_info_popover('Shown in inbox preview by many email clients.'); ?></span><span
                class="counter" id="previewCounter">0</span></span>
          </label>
          <label class="field field-with-meta">
            <span class="field-label-row">From Name
              <?php echo ui_info_popover('Displayed as the sender name in recipients\' inboxes.'); ?></span>
            <input id="campaignFromName" type="text" name="from_name"
              value="<?php echo htmlspecialchars((string) $defaultFromName); ?>" placeholder="e.g. Mailr Team" />
          </label>
        </div>
      </section>

      <section class="card section-card" id="step-compose">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">Step 2</div>
            <h2>Compose Email</h2>
            <!-- <p class="muted">Start from a template, edit content, and review HTML + text previews live.</p> -->
          </div>
          <div class="template-row">
            <label class="field compact-field">
              <span>Template</span>
              <select id="templateSelect" class="template-select">
                <?php foreach ($templateOptions as $tplOption): ?>
                  <option value="<?php echo htmlspecialchars((string) $tplOption['id']); ?>"><?php echo htmlspecialchars((string) $tplOption['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <div class="editor-mode-switch" role="tablist" aria-label="Editor mode">
              <button type="button" class="ghost active" data-editor-mode="visual" aria-selected="true">Design</button>
              <button type="button" class="ghost" data-editor-mode="html" aria-selected="false">HTML</button>
            </div>
            <button type="button" class="ghost" id="openPreview">Live Preview</button>
          </div>
        </div>

        <div class="template-gallery" aria-label="Template quick picks">
          <?php foreach ($templateGalleryItems as $tplCard): ?>
            <button type="button" class="template-card" data-template-choice="<?php echo htmlspecialchars((string) $tplCard['id']); ?>">
              <span class="template-card-head"><svg class="icon">
                  <use href="#<?php echo htmlspecialchars((string) ($tplCard['icon'] ?? 'icon-mail')); ?>"></use>
                </svg><span class="template-card-title"><?php echo htmlspecialchars((string) $tplCard['name']); ?></span></span>
              <span class="template-card-copy"><?php echo htmlspecialchars((string) (($tplCard['description'] ?? '') !== '' ? $tplCard['description'] : 'Reusable email template.')); ?></span>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="editor-toolbar" role="toolbar" aria-label="Email editor toolbar">
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="bold" aria-label="Bold" title="Bold">
              <svg class="icon">
                <use href="#icon-bold"></use>
              </svg><span class="sr-only">Bold</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="italic" aria-label="Italic" title="Italic">
              <svg class="icon">
                <use href="#icon-italic"></use>
              </svg><span class="sr-only">Italic</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="underline" aria-label="Underline"
              title="Underline">
              <svg class="icon">
                <use href="#icon-underline"></use>
              </svg><span class="sr-only">Underline</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="strikeThrough" aria-label="Strikethrough"
              title="Strikethrough">
              <svg class="icon">
                <use href="#icon-strike"></use>
              </svg><span class="sr-only">Strikethrough</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="undo" aria-label="Undo" title="Undo">
              <svg class="icon">
                <use href="#icon-undo"></use>
              </svg><span class="sr-only">Undo</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="redo" aria-label="Redo" title="Redo">
              <svg class="icon">
                <use href="#icon-redo"></use>
              </svg><span class="sr-only">Redo</span>
            </button>
          </div>
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="insertUnorderedList" aria-label="Bulleted list"
              title="Bulleted list">
              <svg class="icon">
                <use href="#icon-list-bullets"></use>
              </svg><span class="sr-only">Bulleted list</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="insertOrderedList" aria-label="Numbered list"
              title="Numbered list">
              <svg class="icon">
                <use href="#icon-list-numbers"></use>
              </svg><span class="sr-only">Numbered list</span>
            </button>
          </div>
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="justifyLeft" aria-label="Align left"
              title="Align left">
              <svg class="icon">
                <use href="#icon-align-left"></use>
              </svg><span class="sr-only">Align left</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="justifyCenter" aria-label="Align center"
              title="Align center">
              <svg class="icon">
                <use href="#icon-align-center"></use>
              </svg><span class="sr-only">Align center</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="justifyRight" aria-label="Align right"
              title="Align right">
              <svg class="icon">
                <use href="#icon-align-right"></use>
              </svg><span class="sr-only">Align right</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="justifyFull" aria-label="Justify"
              title="Justify">
              <svg class="icon">
                <use href="#icon-align-justify"></use>
              </svg><span class="sr-only">Justify</span>
            </button>
          </div>
          <div class="toolbar-group">
            <label class="field inline compact-field">
              <span>Style</span>
              <select id="formatBlock" class="template-select small">
                <option value="P">Paragraph</option>
                <option value="H1">Heading 1</option>
                <option value="H2">Heading 2</option>
                <option value="H3">Heading 3</option>
                <option value="BLOCKQUOTE">Quote</option>
              </select>
            </label>
            <label class="field inline compact-field">
              <span>Size</span>
              <select id="fontSizeSelect" class="template-select small">
                <option value="2">Small</option>
                <option value="3" selected>Normal</option>
                <option value="5">Large</option>
                <option value="6">XL</option>
              </select>
            </label>
          </div>
          <div class="toolbar-group">
            <label class="field inline compact-field">
              <span>Text</span>
              <input type="color" id="foreColor" value="#1e1e1e" />
            </label>
            <label class="field inline compact-field">
              <span>Highlight</span>
              <input type="color" id="hiliteColor" value="#f2c14e" />
            </label>
          </div>
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="createLink" aria-label="Insert link"
              title="Insert link">
              <svg class="icon">
                <use href="#icon-link"></use>
              </svg><span class="sr-only">Insert link</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="insertImage" aria-label="Insert image"
              title="Insert image">
              <svg class="icon">
                <use href="#icon-image"></use>
              </svg><span class="sr-only">Insert image</span>
            </button>
            <button type="button" class="ghost icon-btn" data-command="removeFormat" aria-label="Clear formatting"
              title="Clear formatting">
              <svg class="icon">
                <use href="#icon-erase"></use>
              </svg><span class="sr-only">Clear formatting</span>
            </button>
          </div>
          <div class="toolbar-divider"></div>
          <label class="field inline compact-field">
            <span>Insert</span>
            <select id="placeholderSelect" class="template-select">
              <option value="{{first_name}}">First name</option>
              <option value="{{company}}">Company</option>
              <option value="{{cta_url}}">CTA URL</option>
            </select>
          </label>
          <button type="button" class="ghost icon-btn" id="insertPlaceholder" aria-label="Insert placeholder"
            title="Insert placeholder">
            <svg class="icon">
              <use href="#icon-plus"></use>
            </svg><span class="sr-only">Insert placeholder</span>
          </button>
        </div>

        <div class="editor-shell">
          <div class="editor-pane" id="visualEditorPane">
            <div id="editor" class="editor" contenteditable="true"><?php echo $initialHtml; ?></div>
            <div id="linkInspector" class="link-inspector" hidden>
              <div class="link-inspector-url-row">
                <span class="link-inspector-label">Link</span>
                <a id="linkInspectorUrl" href="#" target="_blank" rel="noopener noreferrer"
                  class="link-inspector-url">—</a>
              </div>
              <div class="link-inspector-actions">
                <button type="button" class="ghost" id="linkInspectorEdit">Edit</button>
                <button type="button" class="ghost" id="linkInspectorRemove">Remove</button>
              </div>
            </div>
            <div id="imageInspector" class="link-inspector image-inspector" hidden>
              <div class="link-inspector-url-row">
                <span class="link-inspector-label">Image</span>
                <a id="imageInspectorSrc" href="#" target="_blank" rel="noopener noreferrer"
                  class="link-inspector-url">—</a>
              </div>
              <label class="field compact-field">
                <span>Alt Text</span>
                <input id="imageInspectorAlt" type="text" placeholder="Describe the image" />
              </label>
              <div class="image-size-controls">
                <label class="field inline compact-field">
                  <span>Width</span>
                  <input id="imageInspectorWidth" type="range" min="80" max="700" step="10" value="320" />
                </label>
                <label class="field inline compact-field">
                  <span>px</span>
                  <input id="imageInspectorWidthNumber" type="number" min="40" max="1200" step="10" value="320" />
                </label>
              </div>
              <div class="image-preset-row">
                <button type="button" class="ghost" data-image-width="25%">25%</button>
                <button type="button" class="ghost" data-image-width="50%">50%</button>
                <button type="button" class="ghost" data-image-width="100%">100%</button>
                <button type="button" class="ghost" data-image-width="auto">Auto</button>
              </div>
              <div class="link-inspector-actions">
                <button type="button" class="ghost" id="imageInspectorReplace">Replace URL</button>
                <button type="button" class="ghost" id="imageInspectorRemoveImage">Remove</button>
              </div>
            </div>
          </div>
          <div id="codeEditorPane" class="code-editor-pane" hidden>
            <div class="code-editor-head">
              <span class="code-editor-label">HTML Source</span>
              <div class="code-editor-head-actions">
                <span class="muted">Direct HTML editing mode</span>
                <button type="button" class="ghost" id="formatHtmlSource">Format HTML</button>
              </div>
            </div>
            <textarea id="htmlCodeEditor" class="code-editor" spellcheck="false"></textarea>
          </div>
          <div class="editor-footer-note">
            <span class="muted">Use the <strong>Live Preview</strong> button to review HTML and text versions in a
              modal.</span>
          </div>
        </div>
        <textarea name="html_content" id="html_content"
          hidden><?php echo htmlspecialchars((string) $initialHtml); ?></textarea>
        <textarea name="text_content" id="text_content"
          hidden><?php echo htmlspecialchars((string) ($campaign['text_content'] ?? '')); ?></textarea>
      </section>

      <section class="card section-card" id="step-delivery">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">Step 3</div>
            <h2>Select SMTP Configuration</h2>
            <p class="muted">Choose the sender infrastructure used for test sends and publishing.</p>
          </div>
          <a class="ghost" href="/index.php?page=configs-test">Manage Configs</a>
        </div>
        <div class="option-grid">
          <?php foreach ($smtpConfigs as $config): ?>
            <label class="option-card smtp-option-card">
              <input type="radio" name="smtp_config_id" value="<?php echo (int) $config['id']; ?>" <?php echo ((int) ($campaign['smtp_config_id'] ?? 0) === (int) $config['id']) ? 'checked' : ''; ?> required />
              <div>
                <div class="option-title"><?php echo htmlspecialchars((string) $config['name']); ?></div>
                <div class="option-sub">
                  <?php echo htmlspecialchars((string) $config['host']); ?>:<?php echo (int) ($config['port'] ?? 0); ?> •
                  <?php echo htmlspecialchars((string) ($config['encryption'] ?? 'tls')); ?>
                </div>
                <div class="option-sub">From: <?php echo htmlspecialchars((string) $config['from_address']); ?></div>
              </div>
              <span class="badge"><?php echo htmlspecialchars((string) $config['status']); ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="card section-card" id="step-audience">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">Step 4</div>
            <h2>Choose Audience</h2>
            <p class="muted">Use an existing list or upload a new CSV for this campaign.</p>
          </div>
        </div>
        <div class="toggle-row" role="radiogroup" aria-label="Audience source">
          <label class="toggle">
            <input type="radio" name="audience_mode" value="list" <?php echo $audienceMode === 'list' ? 'checked' : ''; ?> />
            <span>Select existing list</span>
          </label>
          <label class="toggle">
            <input type="radio" name="audience_mode" value="upload" <?php echo $audienceMode === 'upload' ? 'checked' : ''; ?> />
            <span>Upload a new list</span>
          </label>
        </div>

        <div class="field-grid audience-block audience-mode-list">
          <label class="field field-with-meta">
            <span>Contact List</span>
            <select id="contactListSelect" name="contact_list_id">
              <option value="">Choose a list</option>
              <?php foreach ($contactLists as $list): ?>
                <option value="<?php echo (int) $list['id']; ?>" data-count="<?php echo (int) $list['count']; ?>" <?php echo ((int) ($campaign['contact_list_id'] ?? 0) === (int) $list['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars((string) $list['name']); ?> (<?php echo (int) $list['count']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <span class="helper" id="selectedListHint">Choose the target list for this campaign.</span>
          </label>
        </div>

        <div class="field-grid audience-block audience-mode-upload">
          <label class="field">
            <span>Upload List Name</span>
            <input type="text" name="upload_list_name" placeholder="e.g. New Leads" />
          </label>
          <label class="field">
            <span>Upload CSV</span>
            <input id="uploadCsvInput" type="file" name="upload_csv" />
            <span
              class="helper-inline"><?php echo ui_info_popover('CSV headers: email, first_name, last_name, tags. Extra columns are imported as custom placeholders (for example {{plan}} and {{city}}).'); ?></span>
            <span class="helper-link-row"><svg class="icon">
                <use href="#icon-users"></use>
              </svg><a class="ghost" href="/index.php?action=download_sample_contacts_csv">Download sample
                CSV</a></span>
          </label>
        </div>
        <div class="field-grid audience-shared-grid">
          <label class="field">
            <span>Suppression List</span>
            <input type="text" name="suppression_list"
              value="<?php echo htmlspecialchars((string) ($campaign['suppression_list'] ?? '')); ?>"
              placeholder="optional list name" />
          </label>
          <label class="field">
            <span>Segment Filter</span>
            <input type="text" name="segment_filter"
              value="<?php echo htmlspecialchars((string) ($campaign['segment_filter'] ?? '')); ?>"
              placeholder="e.g. plan:pro AND region:NA" />
          </label>
        </div>
      </section>

      <section class="card section-card" id="step-send">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">Step 5</div>
            <h2>Send Test & Publish</h2>
            <p class="muted">Send to internal recipients first, then publish immediately or schedule a send.</p>
          </div>
        </div>
        <div class="field-grid send-grid">
          <label class="field field-span-2">
            <span>Test Contacts</span>
            <div class="checklist" id="testContactChecklist">
              <?php foreach ($testContacts as $contact): ?>
                <label>
                  <input type="checkbox" name="test_contact_ids[]" value="<?php echo (int) $contact['id']; ?>" />
                  <span><?php echo htmlspecialchars((string) $contact['email']); ?> ·
                    <?php echo htmlspecialchars((string) $contact['name']); ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </label>
          <label class="field">
            <span class="field-label-row">Schedule Send
              <?php echo ui_info_popover('Leave empty to send immediately when publishing.'); ?></span>
            <input id="scheduleAtInput" type="datetime-local" name="schedule_at"
              value="<?php echo htmlspecialchars((string) $scheduleValue); ?>" />
          </label>
          <label class="field">
            <span>Send Window</span>
            <select name="send_window">
              <?php
              $sendWindow = $campaign['send_window'] ?? 'All day';
              foreach (['All day', 'Business hours only', 'Evening only'] as $option) {
                $selected = $sendWindow === $option ? 'selected' : '';
                echo '<option ' . $selected . '>' . htmlspecialchars($option) . '</option>';
              }
              ?>
            </select>
          </label>
          <label class="field">
            <span>Tracking</span>
            <select name="tracking">
              <?php
              $trackingValue = $campaign['tracking'] ?? ($settings['default_tracking'] ?? 'Open + click tracking');
              foreach (['Open + click tracking', 'Click only', 'Disable tracking'] as $option) {
                $selected = $trackingValue === $option ? 'selected' : '';
                echo '<option ' . $selected . '>' . htmlspecialchars($option) . '</option>';
              }
              ?>
            </select>
          </label>
        </div>
        <div class="button-row button-row-primary">
          <button class="ghost" type="submit" name="action" value="send_test" data-loading-text="Sending...">Send
            Test</button>
          <button class="button" type="submit" name="action" value="publish_campaign"
            data-loading-text="Publishing...">Publish Campaign</button>
        </div>
        <div id="sendTestFeedback" class="inline-feedback" hidden aria-live="polite"></div>
      </section>
    </div>

    <aside class="campaign-builder-sidebar">
      <section class="card sidebar-card sticky-panel">
        <div class="sidebar-card-head">
          <h3><span class="icon-label"><svg class="icon">
                <use href="#icon-checklist"></use>
              </svg>Ready To Send</span></h3>
          <span class="sidebar-badge" id="readinessBadge">0/6</span>
        </div>
        <ul class="readiness-list" id="readinessList">
          <li data-check="title"><span class="dot"></span><span>Campaign title</span></li>
          <li data-check="subject"><span class="dot"></span><span>Email subject</span></li>
          <li data-check="content"><span class="dot"></span><span>Email content</span></li>
          <li data-check="smtp"><span class="dot"></span><span>SMTP selected</span></li>
          <li data-check="audience"><span class="dot"></span><span>Audience selected/uploaded</span></li>
          <li data-check="tests"><span class="dot"></span><span>Test recipients selected</span></li>
        </ul>
        <div class="sidebar-actions">
          <button class="ghost" type="submit" name="action" value="save_campaign" data-loading-text="Saving...">Save
            Draft</button>
          <button class="ghost" type="submit" name="action" value="send_test" data-loading-text="Sending...">Send
            Test</button>
          <button class="button" type="submit" name="action" value="publish_campaign"
            data-loading-text="Publishing...">Publish Campaign</button>
        </div>
      </section>


      <section class="card sidebar-card">
        <div class="sidebar-card-head">
          <h3><span class="icon-label"><svg class="icon">
                <use href="#icon-spark"></use>
              </svg>Quick Tips</span></h3>
        </div>
        <ul class="tip-list">
          <li>Keep subject lines under 70 characters for better mobile inbox previews.</li>
          <li>Send a test to at least one inbox on Gmail and Outlook before publishing.</li>
          <li>Use placeholders like `{{first_name}}` and verify fallbacks in your content.</li>
        </ul>
      </section>
    </aside>
  </div>
</form>

<?php if ($campaign): ?>
  <section class="card section-card">
    <div class="section-card-head">
      <div>
        <div class="section-kicker">Activity</div>
        <h2>Recent Activity</h2>
      </div>
    </div>
    <?php if (count($events) === 0): ?>
      <p class="muted">No activity yet. Save, test, or publish to track updates.</p>
    <?php else: ?>
      <div class="activity-list">
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
<?php endif; ?>

<dialog id="previewModal" class="preview-modal">
  <div class="modal-head">
    <h3>Email Preview</h3>
    <button type="button" class="ghost" id="closePreview">Close</button>
  </div>
  <div class="modal-body">
    <div class="preview-header modal-preview-header">
      <div>
        <strong>Preview</strong>
        <span class="muted">Rendered HTML and text-only fallback</span>
      </div>
      <div class="preview-tabs">
        <button type="button" class="ghost active" data-modal-preview="html">HTML</button>
        <button type="button" class="ghost" data-modal-preview="text">Text</button>
      </div>
    </div>
    <div id="modalPreview" class="preview-body"></div>
    <pre id="modalTextPreview" class="preview-text" hidden></pre>
  </div>
</dialog>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.1/beautify-html.min.js"></script>

<script>
  const editor = document.getElementById('editor');
  const visualEditorPane = document.getElementById('visualEditorPane');
  const codeEditorPane = document.getElementById('codeEditorPane');
  const htmlCodeEditor = document.getElementById('htmlCodeEditor');
  const formatHtmlSource = document.getElementById('formatHtmlSource');
  const htmlInput = document.getElementById('html_content');
  const textInput = document.getElementById('text_content');
  const templateSelect = document.getElementById('templateSelect');
  const placeholderSelect = document.getElementById('placeholderSelect');
  const insertPlaceholder = document.getElementById('insertPlaceholder');
  const formatBlock = document.getElementById('formatBlock');
  const fontSizeSelect = document.getElementById('fontSizeSelect');
  const foreColor = document.getElementById('foreColor');
  const hiliteColor = document.getElementById('hiliteColor');
  const openPreview = document.getElementById('openPreview');
  const previewModal = document.getElementById('previewModal');
  const closePreview = document.getElementById('closePreview');
  const modalPreview = document.getElementById('modalPreview');
  const modalTextPreview = document.getElementById('modalTextPreview');
  const sendTestFeedback = document.getElementById('sendTestFeedback');
  const campaignForm = document.querySelector('form.form-grid');
  const titleInput = document.getElementById('campaignTitle');
  const subjectInput = document.getElementById('campaignSubject');
  const previewTextInput = document.getElementById('campaignPreviewText');
  const fromNameInput = document.getElementById('campaignFromName');
  const contactListSelect = document.getElementById('contactListSelect');
  const uploadCsvInput = document.getElementById('uploadCsvInput');
  const scheduleAtInput = document.getElementById('scheduleAtInput');
  const selectedListHint = document.getElementById('selectedListHint');
  const readinessBadge = document.getElementById('readinessBadge');
  const readinessItems = document.querySelectorAll('#readinessList li[data-check]');
  const summaryTitle = document.getElementById('summaryTitle');
  const summarySubject = document.getElementById('summarySubject');
  const summarySmtp = document.getElementById('summarySmtp');
  const summaryAudience = document.getElementById('summaryAudience');
  const summaryTests = document.getElementById('summaryTests');
  const summarySchedule = document.getElementById('summarySchedule');
  const subjectCounter = document.getElementById('subjectCounter');
  const previewCounter = document.getElementById('previewCounter');
  const audienceModeRadios = document.querySelectorAll('input[name="audience_mode"]');
  const campaignStatusPill = document.getElementById('campaignStatusPill');
  const linkInspector = document.getElementById('linkInspector');
  const linkInspectorUrl = document.getElementById('linkInspectorUrl');
  const linkInspectorEdit = document.getElementById('linkInspectorEdit');
  const linkInspectorRemove = document.getElementById('linkInspectorRemove');
  const imageInspector = document.getElementById('imageInspector');
  const imageInspectorSrc = document.getElementById('imageInspectorSrc');
  const imageInspectorAlt = document.getElementById('imageInspectorAlt');
  const imageInspectorWidth = document.getElementById('imageInspectorWidth');
  const imageInspectorWidthNumber = document.getElementById('imageInspectorWidthNumber');
  const imageInspectorReplace = document.getElementById('imageInspectorReplace');
  const imageInspectorRemoveImage = document.getElementById('imageInspectorRemoveImage');
  const imagePresetButtons = document.querySelectorAll('[data-image-width]');
  const editorModeButtons = document.querySelectorAll('[data-editor-mode]');
  let activeEditorLink = null;
  let linkInspectorPinned = false;
  let linkInspectorInteracting = false;
  let activeEditorImage = null;
  let imageInspectorInteracting = false;
  let currentEditorMode = 'visual';
  let codeEditorInstance = null;
  let allowVisualModeForFullHtml = false;
  const uiDialog = window.mailrDialog || {
    alert: async (message) => window.alert(message),
    confirm: async (message) => window.confirm(message),
    prompt: async (message, defaultValue = '') => window.prompt(message, defaultValue)
  };

  const templates = <?php
  $templateJsMap = [];
  foreach ($templateOptions as $tplOption) {
    $templateJsMap[(string) $tplOption['id']] = (string) ($tplOption['html'] ?? '');
  }
  echo json_encode($templateJsMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  ?> || {};

  const applyTemplate = async (choice) => {
    const hasContent = editor.textContent.trim().length > 0;
    if (hasContent && !await uiDialog.confirm('Replace the existing email content?', { title: 'Replace Content', okText: 'Replace' })) {
      return;
    }
    const nextHtml = templates[choice] || templates.blank || Object.values(templates)[0] || '';
    if (isHtmlMode()) {
      setCodeEditorValue(nextHtml);
    } else {
      editor.innerHTML = nextHtml;
    }
    if (templateSelect) {
      templateSelect.value = choice;
    }
    syncContent();
  };

  const isHtmlMode = () => currentEditorMode === 'html';
  const looksLikeFullDocumentHtml = (html) => /<\s*!doctype|<\s*html\b|<\s*head\b|<\s*body\b|<\s*style\b|<\s*meta\b/i.test(String(html || ''));

  const htmlToText = (html) => {
    const temp = document.createElement('div');
    temp.innerHTML = html;
    return temp.innerText.trim();
  };

  const getCodeEditorValue = () => codeEditorInstance ? codeEditorInstance.getValue() : (htmlCodeEditor?.value || '');
  const setCodeEditorValue = (value) => {
    if (codeEditorInstance) {
      codeEditorInstance.setValue(String(value ?? ''));
    } else if (htmlCodeEditor) {
      htmlCodeEditor.value = String(value ?? '');
    }
  };
  const ensureCodeEditor = () => {
    if (codeEditorInstance || !htmlCodeEditor || !window.CodeMirror) {
      return;
    }
    codeEditorInstance = window.CodeMirror.fromTextArea(htmlCodeEditor, {
      mode: 'htmlmixed',
      lineNumbers: true,
      lineWrapping: true,
      tabSize: 2,
      indentUnit: 2,
    });
    codeEditorInstance.on('change', () => {
      if (isHtmlMode()) {
        syncContent();
      }
    });
  };
  const getCurrentHtml = () => {
    if (isHtmlMode()) {
      return getCodeEditorValue().trim();
    }
    return editor.innerHTML.trim();
  };

  const setCurrentHtml = (html) => {
    const next = String(html ?? '');
    editor.innerHTML = next;
    setCodeEditorValue(next);
  };

  const updateEditorModeUI = () => {
    editorModeButtons.forEach((button) => {
      const active = button.dataset.editorMode === currentEditorMode;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    if (visualEditorPane) {
      visualEditorPane.hidden = isHtmlMode();
    }
    if (codeEditorPane) {
      codeEditorPane.hidden = !isHtmlMode();
    }
    document.querySelectorAll('.editor-toolbar [data-command], .editor-toolbar select, .editor-toolbar input[type="color"], #insertPlaceholder').forEach((control) => {
      control.disabled = isHtmlMode();
    });
    hideLinkInspector();
    hideImageInspector();
  };

  const setEditorMode = async (mode) => {
    if (!['visual', 'html'].includes(mode) || mode === currentEditorMode) {
      return;
    }
    if (mode === 'visual') {
      const sourceHtml = getCodeEditorValue();
      if (looksLikeFullDocumentHtml(sourceHtml) && !allowVisualModeForFullHtml) {
        const approved = await uiDialog.confirm(
          'This email contains a full HTML document (head/style/body). Visual mode may alter styling. Continue anyway?',
          { title: 'Switch To Design Mode', okText: 'Continue', cancelText: 'Stay In HTML' }
        );
        if (!approved) {
          return;
        }
        allowVisualModeForFullHtml = true;
      } else if (!looksLikeFullDocumentHtml(sourceHtml)) {
        allowVisualModeForFullHtml = false;
      }
    }
    if (mode === 'html') {
      const sourceHtml = getCodeEditorValue();
      if (!looksLikeFullDocumentHtml(sourceHtml)) {
        allowVisualModeForFullHtml = false;
      }
    }
    if (mode === 'html' && htmlCodeEditor) {
      setCodeEditorValue(editor.innerHTML.trim());
      ensureCodeEditor();
    }
    if (mode === 'visual' && htmlCodeEditor) {
      editor.innerHTML = getCodeEditorValue();
    }
    currentEditorMode = mode;
    updateEditorModeUI();
    syncContent();
    if (mode === 'html' && htmlCodeEditor) {
      ensureCodeEditor();
      if (codeEditorInstance) {
        codeEditorInstance.refresh();
        codeEditorInstance.focus();
      } else {
        htmlCodeEditor.focus();
      }
    } else {
      editor.focus();
    }
  };

  const findLinkInNode = (node) => {
    let current = node;
    while (current && current !== editor) {
      if (current.nodeType === Node.ELEMENT_NODE && current.tagName === 'A') {
        return current;
      }
      current = current.parentNode;
    }
    return null;
  };

  const getEditorSelectionRange = () => {
    const selection = window.getSelection ? window.getSelection() : null;
    if (!selection || selection.rangeCount === 0) {
      return null;
    }
    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;
    if (!container || !(container === editor || editor.contains(container))) {
      return null;
    }
    return range;
  };

  const hideLinkInspector = () => {
    activeEditorLink = null;
    linkInspectorPinned = false;
    if (linkInspector) {
      linkInspector.hidden = true;
    }
  };

  const hideImageInspector = () => {
    activeEditorImage = null;
    if (imageInspector) {
      imageInspector.hidden = true;
    }
  };

  const positionLinkInspector = (range) => {
    if (!linkInspector || !range) {
      return;
    }
    const pane = editor.closest('.editor-pane');
    if (!pane) {
      return;
    }
    const paneRect = pane.getBoundingClientRect();
    const rawRect = range.getBoundingClientRect();
    const rect = (rawRect && (rawRect.width || rawRect.height)) ? rawRect : editor.getBoundingClientRect();
    const inspectorWidth = linkInspector.offsetWidth || 320;
    let left = rect.left - paneRect.left;
    let top = rect.bottom - paneRect.top + 8;

    left = Math.max(8, Math.min(left, paneRect.width - inspectorWidth - 8));
    top = Math.max(8, top);

    linkInspector.style.left = `${left}px`;
    linkInspector.style.top = `${top}px`;
  };

  const updateLinkInspector = () => {
    if (!editor || !linkInspector || isHtmlMode()) {
      return;
    }
    const range = getEditorSelectionRange();
    if (!range) {
      hideLinkInspector();
      return;
    }

    const selection = window.getSelection ? window.getSelection() : null;
    const isCollapsed = !selection || selection.isCollapsed;

    const link = findLinkInNode(range.startContainer) || findLinkInNode(range.endContainer);
    if (!link) {
      hideLinkInspector();
      return;
    }

    if (isCollapsed && !linkInspectorPinned) {
      hideLinkInspector();
      return;
    }

    activeEditorLink = link;
    const href = (link.getAttribute('href') || '').trim();
    if (linkInspectorUrl) {
      linkInspectorUrl.textContent = href || '(empty link)';
      linkInspectorUrl.href = href || '#';
      linkInspectorUrl.title = href || '';
    }
    linkInspector.hidden = false;
    requestAnimationFrame(() => positionLinkInspector(range));
  };

  const positionImageInspector = (img) => {
    if (!imageInspector || !img) {
      return;
    }
    const pane = editor.closest('.editor-pane');
    if (!pane) {
      return;
    }
    const paneRect = pane.getBoundingClientRect();
    const rect = img.getBoundingClientRect();
    const inspectorWidth = imageInspector.offsetWidth || 340;
    let left = rect.left - paneRect.left;
    let top = rect.bottom - paneRect.top + 8;
    left = Math.max(8, Math.min(left, paneRect.width - inspectorWidth - 8));
    top = Math.max(8, top);
    imageInspector.style.left = `${left}px`;
    imageInspector.style.top = `${top}px`;
  };

  const parseImageWidthPx = (img) => {
    if (!img) return 320;
    const styleWidth = (img.style.width || '').trim();
    if (styleWidth.endsWith('px')) {
      const parsed = parseInt(styleWidth, 10);
      if (!Number.isNaN(parsed)) return parsed;
    }
    const attrWidth = parseInt(img.getAttribute('width') || '', 10);
    if (!Number.isNaN(attrWidth)) return attrWidth;
    return Math.max(80, Math.round(img.getBoundingClientRect().width || 320));
  };

  const populateImageInspector = (img) => {
    if (!img || !imageInspector) return;
    const src = (img.getAttribute('src') || '').trim();
    const alt = img.getAttribute('alt') || '';
    const widthPx = parseImageWidthPx(img);
    if (imageInspectorSrc) {
      imageInspectorSrc.textContent = src || '(no src)';
      imageInspectorSrc.href = src || '#';
      imageInspectorSrc.title = src || '';
    }
    if (imageInspectorAlt) {
      imageInspectorAlt.value = alt;
    }
    if (imageInspectorWidth) {
      imageInspectorWidth.value = String(Math.max(80, Math.min(700, widthPx)));
    }
    if (imageInspectorWidthNumber) {
      imageInspectorWidthNumber.value = String(widthPx);
    }
  };

  const showImageInspector = (img) => {
    if (!img || !imageInspector || isHtmlMode()) return;
    activeEditorImage = img;
    populateImageInspector(img);
    imageInspector.hidden = false;
    requestAnimationFrame(() => positionImageInspector(img));
  };

  const applyImageWidth = (value) => {
    if (!activeEditorImage) return;
    if (value === 'auto') {
      activeEditorImage.style.width = '';
      activeEditorImage.removeAttribute('width');
    } else {
      activeEditorImage.style.width = value;
      if (value.endsWith('px')) {
        activeEditorImage.setAttribute('width', String(parseInt(value, 10)));
      } else {
        activeEditorImage.removeAttribute('width');
      }
    }
    activeEditorImage.style.maxWidth = '100%';
    activeEditorImage.style.height = 'auto';
    syncContent();
    showImageInspector(activeEditorImage);
  };

  document.querySelectorAll('.template-card[data-template-choice]').forEach((button) => {
    button.addEventListener('click', () => applyTemplate(button.dataset.templateChoice));
  });

  document.querySelectorAll('.editor-toolbar [data-command]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (isHtmlMode()) {
        return;
      }
      editor.focus();
      const command = button.dataset.command;
      if (command === 'createLink') {
        linkInspectorPinned = true;
        const currentLink = activeEditorLink || findLinkInNode(window.getSelection ? window.getSelection().anchorNode : null);
        const currentHref = currentLink ? (currentLink.getAttribute('href') || '') : '';
        const url = await uiDialog.prompt('Enter link URL', currentHref, { title: 'Insert Link', okText: 'Apply', placeholder: 'https://example.com' });
        if (url) {
          document.execCommand(command, false, url);
          syncContent();
          linkInspectorPinned = false;
          updateLinkInspector();
        } else {
          linkInspectorPinned = false;
          updateLinkInspector();
        }
        return;
      }
      if (command === 'insertImage') {
        const url = await uiDialog.prompt('Enter image URL', '', { title: 'Insert Image', okText: 'Insert', placeholder: 'https://...' });
        if (url) {
          document.execCommand(command, false, url);
          syncContent();
        }
        return;
      }
      document.execCommand(command, false, null);
      updateLinkInspector();
    });
  });

  if (formatBlock) {
    formatBlock.addEventListener('change', () => {
      if (isHtmlMode()) return;
      editor.focus();
      document.execCommand('formatBlock', false, formatBlock.value);
      syncContent();
    });
  }

  if (fontSizeSelect) {
    fontSizeSelect.addEventListener('change', () => {
      if (isHtmlMode()) return;
      editor.focus();
      document.execCommand('fontSize', false, fontSizeSelect.value);
      syncContent();
    });
  }

  if (foreColor) {
    foreColor.addEventListener('input', () => {
      if (isHtmlMode()) return;
      editor.focus();
      document.execCommand('foreColor', false, foreColor.value);
      syncContent();
    });
  }

  if (hiliteColor) {
    hiliteColor.addEventListener('input', () => {
      if (isHtmlMode()) return;
      editor.focus();
      document.execCommand('hiliteColor', false, hiliteColor.value);
      syncContent();
    });
  }

  if (insertPlaceholder) {
    insertPlaceholder.addEventListener('click', () => {
      if (isHtmlMode()) return;
      editor.focus();
      const placeholder = placeholderSelect.value;
      document.execCommand('insertText', false, placeholder);
      syncContent();
      linkInspectorPinned = false;
      updateLinkInspector();
    });
  }

  if (templateSelect) {
    templateSelect.addEventListener('change', () => applyTemplate(templateSelect.value));
  }

  editorModeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      setEditorMode(button.dataset.editorMode || 'visual');
    });
  });

  document.querySelectorAll('[data-modal-preview]').forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('[data-modal-preview]').forEach((button) => button.classList.remove('active'));
      tab.classList.add('active');
      const mode = tab.dataset.modalPreview;
      if (mode === 'text') {
        if (modalTextPreview) {
          modalTextPreview.hidden = false;
        }
        if (modalPreview) {
          modalPreview.style.display = 'none';
        }
      } else {
        if (modalTextPreview) {
          modalTextPreview.hidden = true;
        }
        if (modalPreview) {
          modalPreview.style.display = 'block';
        }
      }
    });
  });

  if (openPreview && previewModal) {
    openPreview.addEventListener('click', () => {
      if (modalPreview) {
        modalPreview.innerHTML = getCurrentHtml();
        modalPreview.style.display = 'block';
      }
      if (modalTextPreview) {
        modalTextPreview.textContent = htmlToText(getCurrentHtml()) || 'Text-only preview will appear here.';
        modalTextPreview.hidden = true;
      }
      document.querySelectorAll('[data-modal-preview]').forEach((button) => {
        button.classList.toggle('active', button.dataset.modalPreview === 'html');
      });
      if (previewModal.showModal) {
        previewModal.showModal();
      } else {
        previewModal.setAttribute('open', 'open');
      }
    });
  }

  if (closePreview && previewModal) {
    closePreview.addEventListener('click', () => {
      if (previewModal.close) {
        previewModal.close();
      } else {
        previewModal.removeAttribute('open');
      }
    });
  }

  [linkInspectorEdit, linkInspectorRemove].forEach((button) => {
    if (!button) {
      return;
    }
    button.addEventListener('mousedown', (event) => {
      event.preventDefault();
      linkInspectorInteracting = true;
    });
    button.addEventListener('mouseup', () => {
      setTimeout(() => { linkInspectorInteracting = false; }, 0);
    });
  });

  [imageInspectorAlt, imageInspectorWidth, imageInspectorWidthNumber, imageInspectorReplace, imageInspectorRemoveImage, ...imagePresetButtons].forEach((node) => {
    if (!node) return;
    node.addEventListener('mousedown', (event) => {
      event.preventDefault();
      imageInspectorInteracting = true;
    });
    node.addEventListener('mouseup', () => {
      setTimeout(() => { imageInspectorInteracting = false; }, 0);
    });
  });

  if (linkInspectorEdit) {
    linkInspectorEdit.addEventListener('click', async () => {
      if (!activeEditorLink) {
        return;
      }
      linkInspectorPinned = true;
      const currentHref = activeEditorLink.getAttribute('href') || '';
      const nextHref = await uiDialog.prompt('Edit link URL', currentHref, { title: 'Edit Link', okText: 'Save', placeholder: 'https://example.com' });
      if (nextHref === null) {
        linkInspectorPinned = false;
        updateLinkInspector();
        return;
      }
      const trimmed = nextHref.trim();
      if (trimmed === '') {
        activeEditorLink.removeAttribute('href');
      } else {
        activeEditorLink.setAttribute('href', trimmed);
        activeEditorLink.setAttribute('target', '_blank');
        activeEditorLink.setAttribute('rel', 'noopener noreferrer');
      }
      syncContent();
      updateLinkInspector();
    });
  }

  if (linkInspectorRemove) {
    linkInspectorRemove.addEventListener('click', () => {
      if (!activeEditorLink || !activeEditorLink.parentNode) {
        return;
      }
      const link = activeEditorLink;
      const parent = link.parentNode;
      while (link.firstChild) {
        parent.insertBefore(link.firstChild, link);
      }
      parent.removeChild(link);
      syncContent();
      hideLinkInspector();
      editor.focus();
    });
  }

  if (imageInspectorAlt) {
    imageInspectorAlt.addEventListener('input', () => {
      if (!activeEditorImage) return;
      activeEditorImage.setAttribute('alt', imageInspectorAlt.value);
      syncContent();
    });
  }

  if (imageInspectorWidth) {
    imageInspectorWidth.addEventListener('input', () => {
      const value = `${imageInspectorWidth.value}px`;
      if (imageInspectorWidthNumber) imageInspectorWidthNumber.value = imageInspectorWidth.value;
      applyImageWidth(value);
    });
  }

  if (imageInspectorWidthNumber) {
    imageInspectorWidthNumber.addEventListener('input', () => {
      const parsed = Math.max(40, Math.min(1200, parseInt(imageInspectorWidthNumber.value || '0', 10) || 0));
      if (!parsed) return;
      if (imageInspectorWidth) imageInspectorWidth.value = String(Math.max(80, Math.min(700, parsed)));
      applyImageWidth(`${parsed}px`);
    });
  }

  imagePresetButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const widthValue = button.dataset.imageWidth || 'auto';
      applyImageWidth(widthValue === 'auto' ? 'auto' : widthValue);
    });
  });

  if (imageInspectorReplace) {
    imageInspectorReplace.addEventListener('click', async () => {
      if (!activeEditorImage) return;
      const currentSrc = activeEditorImage.getAttribute('src') || '';
      const nextSrc = await uiDialog.prompt('Replace image URL', currentSrc, { title: 'Replace Image URL', okText: 'Save', placeholder: 'https://...' });
      if (nextSrc === null) return;
      const trimmed = nextSrc.trim();
      if (trimmed === '') return;
      activeEditorImage.setAttribute('src', trimmed);
      syncContent();
      showImageInspector(activeEditorImage);
    });
  }

  if (imageInspectorRemoveImage) {
    imageInspectorRemoveImage.addEventListener('click', () => {
      if (!activeEditorImage || !activeEditorImage.parentNode) return;
      activeEditorImage.remove();
      syncContent();
      hideImageInspector();
      editor.focus();
    });
  }

  const getAudienceMode = () => {
    const checked = document.querySelector('input[name="audience_mode"]:checked');
    return checked ? checked.value : 'list';
  };

  const updateAudienceModeUI = () => {
    const mode = getAudienceMode();
    document.querySelectorAll('.audience-mode-list').forEach((el) => {
      el.hidden = mode !== 'list';
    });
    document.querySelectorAll('.audience-mode-upload').forEach((el) => {
      el.hidden = mode !== 'upload';
    });
  };

  const updateSummary = () => {
    const title = titleInput ? titleInput.value.trim() : '';
    const subject = subjectInput ? subjectInput.value.trim() : '';
    const checkedSmtp = document.querySelector('input[name="smtp_config_id"]:checked');
    const smtpCard = checkedSmtp ? checkedSmtp.closest('.option-card') : null;
    const smtpTitle = smtpCard ? smtpCard.querySelector('.option-title') : null;
    const mode = getAudienceMode();
    const selectedListOption = contactListSelect ? contactListSelect.options[contactListSelect.selectedIndex] : null;
    const testSelected = campaignForm.querySelectorAll('input[name="test_contact_ids[]"]:checked').length;
    const scheduleValue = scheduleAtInput ? scheduleAtInput.value : '';

    if (summaryTitle) {
      summaryTitle.textContent = title || 'Not set';
    }
    if (summarySubject) {
      summarySubject.textContent = subject || 'Not set';
    }
    if (summarySmtp) {
      summarySmtp.textContent = smtpTitle ? smtpTitle.textContent.trim() : 'Not selected';
    }
    if (summaryAudience) {
      if (mode === 'list') {
        summaryAudience.textContent = selectedListOption && selectedListOption.value !== '' ? selectedListOption.textContent.trim() : 'Existing list not selected';
      } else {
        const fileName = uploadCsvInput && uploadCsvInput.files && uploadCsvInput.files[0] ? uploadCsvInput.files[0].name : 'No CSV selected';
        summaryAudience.textContent = 'Upload mode • ' + fileName;
      }
    }
    if (summaryTests) {
      summaryTests.textContent = `${testSelected} selected`;
    }
    if (summarySchedule) {
      summarySchedule.textContent = scheduleValue ? new Date(scheduleValue).toLocaleString() : 'Send immediately on publish';
    }

    if (subjectCounter) {
      subjectCounter.textContent = `${subject.length} chars`;
    }
    if (previewCounter) {
      const value = previewTextInput ? previewTextInput.value.trim() : '';
      previewCounter.textContent = `${value.length} chars`;
    }

    if (selectedListHint && mode === 'list' && selectedListOption && selectedListOption.value !== '') {
      const count = selectedListOption.dataset.count || '0';
      selectedListHint.textContent = `${count} contacts will be targeted from this list.`;
    } else if (selectedListHint) {
      selectedListHint.textContent = 'Choose the target list for this campaign.';
    }
  };

  const updateReadiness = () => {
    const checks = {
      title: !!(titleInput && titleInput.value.trim()),
      subject: !!(subjectInput && subjectInput.value.trim()),
      content: editor.innerText.trim().length > 0,
      smtp: !!document.querySelector('input[name="smtp_config_id"]:checked'),
      audience: false,
      tests: campaignForm.querySelectorAll('input[name="test_contact_ids[]"]:checked').length > 0,
    };

    if (getAudienceMode() === 'list') {
      checks.audience = !!(contactListSelect && contactListSelect.value);
    } else {
      checks.audience = !!(uploadCsvInput && uploadCsvInput.files && uploadCsvInput.files.length > 0);
    }

    let complete = 0;
    readinessItems.forEach((item) => {
      const key = item.dataset.check;
      const ok = !!checks[key];
      item.classList.toggle('done', ok);
      if (ok) {
        complete += 1;
      }
    });

    if (readinessBadge) {
      readinessBadge.textContent = `${complete}/${readinessItems.length}`;
      readinessBadge.classList.toggle('all-done', complete === readinessItems.length);
    }
  };

  const syncContent = () => {
    const html = getCurrentHtml();
    const text = htmlToText(html);
    htmlInput.value = html;
    textInput.value = text;
    if (modalPreview) {
      modalPreview.innerHTML = html;
    }
    if (modalTextPreview) {
      modalTextPreview.textContent = text || 'Text-only preview will appear here.';
    }
    updateSummary();
    updateReadiness();
  };

  const setSendTestFeedback = (type, message, details = []) => {
    if (!sendTestFeedback) {
      return;
    }
    const escapeHtml = (value) => String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
    sendTestFeedback.hidden = false;
    sendTestFeedback.className = `inline-feedback ${type}`;
    let html = `<div class="inline-feedback-title">${escapeHtml(message)}</div>`;
    if (details.length > 0) {
      html += '<ul class="inline-feedback-list">';
      details.forEach((line) => {
        html += `<li>${escapeHtml(line)}</li>`;
      });
      html += '</ul>';
    }
    sendTestFeedback.innerHTML = html;
  };

  const resetFormButtons = (form) => {
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
      button.disabled = false;
      if (button.tagName.toLowerCase() === 'button') {
        if (button.dataset.originalText) {
          button.innerHTML = button.dataset.originalText;
        }
        button.classList.remove('is-loading');
      }
    });
  };

  const updateCampaignUrlAndId = (campaignId) => {
    if (!campaignId) {
      return;
    }
    const campaignIdInput = campaignForm.querySelector('input[name="campaign_id"]');
    if (campaignIdInput) {
      campaignIdInput.value = String(campaignId);
    }
    const url = new URL(window.location.href);
    url.searchParams.set('page', 'create-campaign');
    url.searchParams.set('campaign_id', String(campaignId));
    history.replaceState({}, '', url.toString());
  };

  const setStatusPill = (status) => {
    if (!campaignStatusPill || !status) {
      return;
    }
    campaignStatusPill.textContent = status;
    campaignStatusPill.className = `pill-value ${String(status).toLowerCase().replace(/\\s+/g, '-')}`;
  };

  const submitCampaignActionAjax = async (actionValue, pendingMessage, genericErrorMessage) => {
    setSendTestFeedback('pending', pendingMessage);
    const formData = new FormData(campaignForm);
    formData.set('action', actionValue);

    const response = await fetch(window.location.pathname + window.location.search, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    let payload;
    try {
      payload = await response.json();
    } catch (error) {
      throw new Error(genericErrorMessage);
    }

    const type = payload.ok ? 'success' : 'error';
    const details = Array.isArray(payload.failed) ? payload.failed : [];
    setSendTestFeedback(type, payload.message || 'Request completed.', details);

    if (payload.campaign_id) {
      updateCampaignUrlAndId(payload.campaign_id);
    }

    if (payload.published_status) {
      setStatusPill(payload.published_status);
    }

    if (!payload.ok) {
      throw new Error(payload.message || genericErrorMessage);
    }

    return payload;
  };

  campaignForm.addEventListener('submit', async (event) => {
    syncContent();
    const submitter = event.submitter;
    if (!submitter || submitter.name !== 'action' || !['send_test', 'publish_campaign'].includes(submitter.value)) {
      return;
    }

    event.preventDefault();
    const actionValue = submitter.value;

    try {
      if (actionValue === 'send_test') {
        await submitCampaignActionAjax('send_test', 'Sending test email...', 'Test send request failed. Check server logs and SMTP settings.');
      } else {
        await submitCampaignActionAjax('publish_campaign', 'Publishing campaign...', 'Publish request failed. Check server logs.');
      }
    } catch (error) {
      if (actionValue === 'send_test') {
        setSendTestFeedback('error', error.message || 'Test send request failed. Check server logs and SMTP settings.');
      } else {
        setSendTestFeedback('error', error.message || 'Publish request failed. Check server logs.');
      }
    } finally {
      resetFormButtons(campaignForm);
    }
  });

  [titleInput, subjectInput, previewTextInput, fromNameInput, contactListSelect, uploadCsvInput, scheduleAtInput].forEach((node) => {
    if (!node) {
      return;
    }
    node.addEventListener('input', () => {
      updateSummary();
      updateReadiness();
    });
    node.addEventListener('change', () => {
      updateSummary();
      updateReadiness();
    });
  });

  audienceModeRadios.forEach((radio) => {
    radio.addEventListener('change', () => {
      updateAudienceModeUI();
      updateSummary();
      updateReadiness();
    });
  });

  campaignForm.querySelectorAll('input[name="smtp_config_id"], input[name="test_contact_ids[]"]').forEach((node) => {
    node.addEventListener('change', () => {
      updateSummary();
      updateReadiness();
    });
  });

  editor.addEventListener('input', () => {
    if (isHtmlMode()) return;
    syncContent();
    updateLinkInspector();
  });
  editor.addEventListener('mouseup', () => {
    if (isHtmlMode()) return;
    linkInspectorPinned = false;
    updateLinkInspector();
  });
  editor.addEventListener('keyup', () => {
    if (isHtmlMode()) return;
    linkInspectorPinned = false;
    updateLinkInspector();
  });
  editor.addEventListener('click', (event) => {
    if (isHtmlMode()) return;
    const target = event.target;
    if (target instanceof HTMLImageElement) {
      showImageInspector(target);
      return;
    }
    if (!(imageInspector && imageInspector.contains(target))) {
      hideImageInspector();
    }
  });
  editor.addEventListener('blur', () => {
    setTimeout(() => {
      if (linkInspectorInteracting) {
        return;
      }
      const active = document.activeElement;
      if (!linkInspector || !active || !linkInspector.contains(active)) {
        hideLinkInspector();
      }
    }, 0);
  });
  editor.addEventListener('focus', updateLinkInspector);
  document.addEventListener('selectionchange', () => {
    if (linkInspectorInteracting) {
      return;
    }
    if (imageInspectorInteracting) {
      return;
    }
    if (isHtmlMode()) {
      hideLinkInspector();
      hideImageInspector();
      return;
    }
    const selection = window.getSelection ? window.getSelection() : null;
    const node = selection && selection.anchorNode ? selection.anchorNode : null;
    if (node && (node === editor || editor.contains(node))) {
      updateLinkInspector();
      return;
    }
    if (linkInspector && !linkInspector.hidden) {
      hideLinkInspector();
    }
    if (imageInspector && !imageInspector.hidden) {
      hideImageInspector();
    }
  });
  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) {
      return;
    }
    if (editor.contains(target) || (linkInspector && linkInspector.contains(target)) || (imageInspector && imageInspector.contains(target))) {
      return;
    }
    hideLinkInspector();
    hideImageInspector();
  });
  document.addEventListener('focusin', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) {
      hideLinkInspector();
      return;
    }
    if (editor.contains(target) || (linkInspector && linkInspector.contains(target)) || (imageInspector && imageInspector.contains(target))) {
      return;
    }
    hideLinkInspector();
    hideImageInspector();
  });
  document.addEventListener('pointerdown', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) {
      return;
    }
    if (editor.contains(target) || (linkInspector && linkInspector.contains(target)) || (imageInspector && imageInspector.contains(target))) {
      return;
    }
    hideLinkInspector();
    hideImageInspector();
  });
  window.addEventListener('blur', () => { hideLinkInspector(); hideImageInspector(); });
  window.addEventListener('resize', () => { hideLinkInspector(); hideImageInspector(); });
  window.addEventListener('scroll', () => { hideLinkInspector(); hideImageInspector(); }, true);
  if (htmlCodeEditor) {
    htmlCodeEditor.addEventListener('input', () => {
      if (codeEditorInstance) return;
      syncContent();
    });
  }
  if (formatHtmlSource) {
    formatHtmlSource.addEventListener('click', () => {
      const current = getCodeEditorValue();
      const formatter = window.html_beautify;
      if (typeof formatter === 'function') {
        setCodeEditorValue(formatter(current, {
          indent_size: 2,
          wrap_line_length: 0,
          preserve_newlines: true,
          end_with_newline: false
        }));
      } else {
        setCodeEditorValue(current);
      }
      syncContent();
      if (codeEditorInstance) codeEditorInstance.refresh();
    });
  }
  updateAudienceModeUI();
  if (htmlInput && looksLikeFullDocumentHtml(htmlInput.value)) {
    currentEditorMode = 'html';
    ensureCodeEditor();
    setCodeEditorValue(htmlInput.value);
  }
  updateEditorModeUI();
  syncContent();
  hideLinkInspector();
  hideImageInspector();
</script>
