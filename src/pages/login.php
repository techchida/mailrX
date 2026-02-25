<?php

declare(strict_types=1);

$next = (string) ($data['next'] ?? '/index.php?page=dashboard');
?>

<section class="auth-shell">
  <div class="auth-card card">
    <div class="auth-head">
      <div class="eyebrow">Mailr</div>
      <h1>Sign In</h1>
      <p>Authenticate to manage campaigns, templates, contacts, and delivery settings.</p>
    </div>

    <form method="post" class="auth-form">
      <input type="hidden" name="action" value="login" />
      <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>" />
      <label class="field">
        <span>Username</span>
        <input type="text" name="username" autocomplete="username" required />
      </label>
      <label class="field">
        <span>Password</span>
        <input type="password" name="password" autocomplete="current-password" required />
      </label>
      <div class="button-row">
        <button class="button" type="submit" data-loading-text="Signing in...">Sign In</button>
      </div>
    </form>

  </div>
</section>