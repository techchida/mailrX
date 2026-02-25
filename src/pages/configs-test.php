<?php

declare(strict_types=1);

$smtpConfigs = $data['smtp_configs'] ?? [];
$testContacts = $data['test_contacts'] ?? [];
$settings = $data['settings'] ?? [];
?>

<section class="page-header">
  <div>
    <h1>Campaign Configs & Test Contacts</h1>
    <p>Manage default campaign settings, SMTP configurations, and test recipients.</p>
  </div>
</section>

<section class="card">
  <div class="card-header">
    <h2><span class="icon-label"><svg class="icon"><use href="#uii-settings"></use></svg>Default Campaign Settings</span></h2>
  </div>
  <form class="field-grid" method="post">
    <input type="hidden" name="action" value="save_settings" />
    <label class="field">
      <span>Default From Name</span>
      <input type="text" name="default_from_name" value="<?php echo htmlspecialchars((string) ($settings['default_from_name'] ?? '')); ?>" />
    </label>
    <label class="field">
      <span>Reply-To Address</span>
      <input type="email" name="reply_to" value="<?php echo htmlspecialchars((string) ($settings['reply_to'] ?? '')); ?>" />
    </label>
    <label class="field">
      <span>Timezone</span>
      <select name="timezone">
        <?php
        $timezones = ['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles'];
        $currentTz = $settings['timezone'] ?? 'America/New_York';
        foreach ($timezones as $tz) {
            $selected = $tz === $currentTz ? 'selected' : '';
            echo '<option ' . $selected . '>' . htmlspecialchars($tz) . '</option>';
        }
        ?>
      </select>
    </label>
    <label class="field">
      <span>Default Tracking</span>
      <select name="default_tracking">
        <?php
        $trackingOptions = ['Open + click tracking', 'Click only', 'Disable tracking'];
        $currentTracking = $settings['default_tracking'] ?? 'Open + click tracking';
        foreach ($trackingOptions as $option) {
            $selected = $option === $currentTracking ? 'selected' : '';
            echo '<option ' . $selected . '>' . htmlspecialchars($option) . '</option>';
        }
        ?>
      </select>
    </label>
    <div class="button-row">
      <button class="button" type="submit" data-loading-text="Saving...">Save Settings</button>
    </div>
  </form>
</section>

<section class="card">
  <div class="card-header">
    <h2><span class="icon-label"><svg class="icon"><use href="#uii-server"></use></svg>SMTP Configurations</span></h2>
  </div>
  <form class="field-grid" method="post">
    <input type="hidden" name="action" value="add_smtp_config" />
    <label class="field">
      <span>Name</span>
      <input type="text" name="name" placeholder="Primary SMTP" required />
    </label>
    <label class="field">
      <span>Host</span>
      <input type="text" name="host" placeholder="smtp.mailhost.com" required />
    </label>
    <label class="field">
      <span>Port</span>
      <input type="number" name="port" value="587" />
    </label>
    <label class="field">
      <span>Encryption</span>
      <select name="encryption">
        <option value="tls" selected>TLS (STARTTLS)</option>
        <option value="ssl">SSL</option>
        <option value="none">None</option>
      </select>
    </label>
    <label class="field">
      <span>Username</span>
      <input type="text" name="username" placeholder="hello@mailr.com" />
    </label>
    <label class="field">
      <span>Password</span>
      <input type="password" name="password" placeholder="SMTP password" />
      <span class="helper-inline"><?php echo ui_info_popover('Stored locally for this demo instance. Use environment secrets in production.'); ?></span>
    </label>
    <label class="field">
      <span>From Address</span>
      <input type="email" name="from_address" placeholder="hello@mailr.com" required />
    </label>
    <div class="button-row">
      <button class="ghost" type="submit" data-loading-text="Adding...">Add Config</button>
    </div>
  </form>
  <div class="table">
    <div class="table-row table-head">
      <div>Name</div>
      <div>Host / Encryption</div>
      <div>From Address</div>
      <div>Status</div>
      <div></div>
    </div>
    <?php foreach ($smtpConfigs as $config): ?>
      <div class="table-row">
        <div><?php echo htmlspecialchars((string) $config['name']); ?></div>
        <div>
          <?php echo htmlspecialchars((string) $config['host']); ?>:<?php echo (int) $config['port']; ?>
          <div class="table-sub"><?php echo htmlspecialchars((string) ($config['encryption'] ?? 'tls')); ?></div>
        </div>
        <div><?php echo htmlspecialchars((string) $config['from_address']); ?></div>
        <div><span class="status <?php echo status_badge_class((string) $config['status']); ?>"><?php echo htmlspecialchars((string) $config['status']); ?></span></div>
        <div class="button-row">
          <form method="post">
            <input type="hidden" name="action" value="toggle_smtp_status" />
            <input type="hidden" name="smtp_id" value="<?php echo (int) $config['id']; ?>" />
            <button class="ghost" type="submit" data-loading-text="Updating...">Toggle</button>
          </form>
          <form method="post">
            <input type="hidden" name="action" value="delete_smtp_config" />
            <input type="hidden" name="smtp_id" value="<?php echo (int) $config['id']; ?>" />
            <button class="ghost" type="submit" data-loading-text="Removing...">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="card">
  <div class="card-header">
    <h2><span class="icon-label"><svg class="icon"><use href="#uii-users"></use></svg>Test Contacts</span></h2>
  </div>
  <form class="field-grid" method="post">
    <input type="hidden" name="action" value="add_test_contact" />
    <label class="field">
      <span>Email</span>
      <input type="email" name="email" placeholder="qa-team@mailr.com" required />
    </label>
    <label class="field">
      <span>Name</span>
      <input type="text" name="name" placeholder="QA Team" />
    </label>
    <label class="field">
      <span>Notes</span>
      <input type="text" name="notes" placeholder="Review copy and links" />
    </label>
    <div class="button-row">
      <button class="ghost" type="submit" data-loading-text="Adding...">Add Test Contact</button>
    </div>
  </form>
  <div class="table">
    <div class="table-row table-head">
      <div>Email</div>
      <div>Name</div>
      <div>Notes</div>
      <div></div>
    </div>
    <?php foreach ($testContacts as $contact): ?>
      <div class="table-row">
        <div><?php echo htmlspecialchars((string) $contact['email']); ?></div>
        <div><?php echo htmlspecialchars((string) $contact['name']); ?></div>
        <div><?php echo htmlspecialchars((string) $contact['notes']); ?></div>
        <div>
          <form method="post">
            <input type="hidden" name="action" value="delete_test_contact" />
            <input type="hidden" name="test_contact_id" value="<?php echo (int) $contact['id']; ?>" />
            <button class="ghost" type="submit" data-loading-text="Removing...">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
