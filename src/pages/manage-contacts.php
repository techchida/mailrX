<?php

declare(strict_types=1);

$contactLists = $data['contact_lists'] ?? [];
$dataPointsMap = $data['contact_list_data_points'] ?? [];
?>

<section class="page-header">
  <div>
    <h1>Manage Contact Lists</h1>
    <p>Create lists, inspect available contact fields, and append CSV uploads without duplicating existing emails.</p>
  </div>
  <div class="actions">
    <a class="ghost" href="/index.php?action=download_sample_contacts_csv"><svg class="icon"><use href="#uii-upload"></use></svg>Download Sample CSV</a>
  </div>
</section>

<section class="contacts-admin-grid">
  <section class="card contact-list-create-card">
    <div class="card-header">
      <h2><span class="icon-label"><svg class="icon"><use href="#uii-users"></use></svg>Create New List</span></h2>
      <span class="chip muted"><?php echo count($contactLists); ?> lists</span>
    </div>
    <form class="field-grid" method="post">
      <input type="hidden" name="action" value="add_contact_list" />
      <label class="field">
        <span>List Name</span>
        <input type="text" name="list_name" placeholder="e.g. February Leads" required />
      </label>
      <div class="button-row">
        <button class="button" type="submit" data-loading-text="Creating...">Create List</button>
      </div>
    </form>
  </section>

  <section class="card contact-list-import-card">
    <div class="card-header">
      <h2><span class="icon-label"><svg class="icon"><use href="#uii-upload"></use></svg>Import New Contacts</span></h2>
    </div>
    <form class="field-grid" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="import_contacts" />
      <input type="hidden" name="import_mode" value="insert_only" />
      <label class="field">
        <span>New List Name</span>
        <input type="text" name="list_name" placeholder="e.g. February Leads" required />
      </label>
      <label class="field">
        <span>Upload CSV <?php echo ui_info_popover('Required column: email. Common columns: first_name, last_name, tags. Extra columns become custom placeholders like {{plan}} or {{city}}.'); ?></span>
        <input type="file" name="upload_csv" accept=".csv,text/csv" required />
      </label>
      <label class="field">
        <span>Default Tags <?php echo ui_info_popover('Applied only when a row does not provide its own tags value.'); ?></span>
        <input type="text" name="tags" placeholder="e.g. webinar, beta" />
      </label>
      <div class="button-row">
        <button class="ghost" type="submit" data-loading-text="Importing...">Import Into New List</button>
      </div>
    </form>
  </section>
</section>

<section class="card contact-lists-section">
  <div class="card-header">
    <h2><span class="icon-label"><svg class="icon"><use href="#uii-template"></use></svg>Contact Lists</span></h2>
    <div class="recipients-search-input contact-lists-search">
      <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10 4a6 6 0 1 0 3.9 10.6l4.7 4.7 1.4-1.4-4.7-4.7A6 6 0 0 0 10 4zm0 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8z" fill="currentColor"/>
      </svg>
      <input id="contactListSearch" type="search" placeholder="Search lists by name or data point..." autocomplete="off" />
    </div>
  </div>

  <?php if (count($contactLists) === 0): ?>
    <div class="dashboard-empty">
      <div class="dashboard-empty-icon"><svg class="icon"><use href="#uii-users"></use></svg></div>
      <h3>No contact lists yet</h3>
      <p>Create a list or import a CSV to get started.</p>
    </div>
  <?php else: ?>
    <div class="contact-list-grid" id="contactListGrid">
      <?php foreach ($contactLists as $list): ?>
        <?php
        $listId = (int) ($list['id'] ?? 0);
        $stats = $dataPointsMap[$listId] ?? [];
        $totalContacts = (int) ($list['count'] ?? 0);
        $customKeys = array_slice((array) ($stats['custom_keys'] ?? []), 0, 8);
        $searchTextParts = [
          (string) ($list['name'] ?? ''),
          'email first_name last_name tags',
          implode(' ', (array) ($stats['custom_keys'] ?? [])),
        ];
        $searchBlob = strtolower(trim(implode(' ', $searchTextParts)));
        ?>
        <article class="contact-list-card" data-list-search="<?php echo htmlspecialchars($searchBlob); ?>">
          <div class="contact-list-card-head">
            <div>
              <h3><?php echo htmlspecialchars((string) $list['name']); ?></h3>
              <div class="contact-list-card-sub">list-<?php echo $listId; ?> · Updated <?php echo htmlspecialchars((string) ($list['updated_at'] ?? '')); ?></div>
            </div>
            <span class="status sent"><?php echo $totalContacts; ?> contacts</span>
          </div>

          <div class="contact-list-stats">
            <div class="contact-list-stat"><span>Email</span><strong><?php echo (int) ($stats['email_count'] ?? $totalContacts); ?></strong></div>
            <div class="contact-list-stat"><span>First Name</span><strong><?php echo (int) ($stats['first_name_count'] ?? 0); ?></strong></div>
            <div class="contact-list-stat"><span>Last Name</span><strong><?php echo (int) ($stats['last_name_count'] ?? 0); ?></strong></div>
            <div class="contact-list-stat"><span>Tags</span><strong><?php echo (int) ($stats['tags_count'] ?? 0); ?></strong></div>
            <div class="contact-list-stat"><span>Custom Fields</span><strong><?php echo count((array) ($stats['custom_keys'] ?? [])); ?></strong></div>
            <div class="contact-list-stat"><span>Rows w/ Custom</span><strong><?php echo (int) ($stats['custom_fields_count'] ?? 0); ?></strong></div>
          </div>

          <div class="contact-list-fields">
            <span class="chip">email</span>
            <?php if ((int) ($stats['first_name_count'] ?? 0) > 0): ?><span class="chip muted">first_name</span><?php endif; ?>
            <?php if ((int) ($stats['last_name_count'] ?? 0) > 0): ?><span class="chip muted">last_name</span><?php endif; ?>
            <?php if ((int) ($stats['tags_count'] ?? 0) > 0): ?><span class="chip muted">tags</span><?php endif; ?>
            <?php foreach ($customKeys as $key): ?>
              <span class="chip muted"><?php echo htmlspecialchars((string) $key); ?></span>
            <?php endforeach; ?>
            <?php if (count((array) ($stats['custom_keys'] ?? [])) > count($customKeys)): ?>
              <span class="chip muted">+<?php echo count((array) ($stats['custom_keys'] ?? [])) - count($customKeys); ?> more</span>
            <?php endif; ?>
          </div>

          <div class="contact-list-card-actions">
            <a class="ghost" href="/index.php?action=export_list&list_id=<?php echo $listId; ?>">Export CSV</a>
            <details class="contact-list-append">
              <summary class="ghost"><svg class="icon"><use href="#uii-upload"></use></svg>Update List</summary>
              <form method="post" enctype="multipart/form-data" class="contact-list-append-form">
                <input type="hidden" name="action" value="append_contacts_to_list" />
                <input type="hidden" name="list_id" value="<?php echo $listId; ?>" />
                <label class="field">
                  <span>CSV Upload</span>
                  <input type="file" name="upload_csv" accept=".csv,text/csv" required />
                </label>
                <label class="field">
                  <span>Default Tags</span>
                  <input type="text" name="tags" placeholder="optional default tags" />
                </label>
                <label class="field">
                  <span>Merge Mode <?php echo ui_info_popover('Insert New Only keeps existing contacts unchanged. Insert + Update Existing merges non-empty incoming values into matching emails, including custom placeholder fields.'); ?></span>
                  <select name="import_mode">
                    <option value="insert_only">Insert New Only</option>
                    <option value="upsert_existing">Insert + Update Existing</option>
                  </select>
                </label>
                <div class="contact-list-append-note">
                  <svg class="icon"><use href="#uii-info"></use></svg>
                  <span>Use update mode when your CSV adds new columns like plan, city, coupon_code, or other personalization fields for existing emails.</span>
                </div>
                <div class="button-row">
                  <button class="button" type="submit" data-loading-text="Updating...">Upload & Merge</button>
                </div>
              </form>
            </details>
            <form method="post" class="inline-delete-list-form">
              <input type="hidden" name="action" value="delete_contact_list" />
              <input type="hidden" name="list_id" value="<?php echo $listId; ?>" />
              <button class="ghost danger" type="submit" data-loading-text="Deleting...">Delete</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="muted contact-list-search-empty" id="contactListSearchEmpty" hidden>No lists matched your search.</p>
  <?php endif; ?>
</section>

<script>
(() => {
  const searchInput = document.getElementById('contactListSearch');
  const cards = Array.from(document.querySelectorAll('.contact-list-card'));
  const emptyState = document.getElementById('contactListSearchEmpty');
  const uiDialog = window.mailrDialog || { confirm: async (m) => window.confirm(m) };

  if (searchInput && cards.length) {
    const applySearch = () => {
      const q = (searchInput.value || '').trim().toLowerCase();
      let visible = 0;
      cards.forEach((card) => {
        const hay = (card.dataset.listSearch || '');
        const match = q === '' || hay.includes(q);
        card.hidden = !match;
        if (match) visible += 1;
      });
      if (emptyState) emptyState.hidden = visible !== 0;
    };
    searchInput.addEventListener('input', applySearch);
    applySearch();
  }

  document.querySelectorAll('.inline-delete-list-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const ok = await uiDialog.confirm('Delete this contact list and all contacts in it?', {
        title: 'Delete Contact List',
        okText: 'Delete',
        cancelText: 'Keep'
      });
      if (!ok) return;
      const submitter = form.querySelector('button[type="submit"], input[type="submit"]');
      if (typeof form.requestSubmit === 'function' && submitter) {
        form.requestSubmit(submitter);
        return;
      }
      if (window.mailrFormState && typeof window.mailrFormState.applySubmittingState === 'function') {
        window.mailrFormState.applySubmittingState(form, submitter);
      }
      form.submit();
    });
  });
})();
</script>
