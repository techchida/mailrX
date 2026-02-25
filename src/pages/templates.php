<?php

declare(strict_types=1);

$templates = $data['templates'] ?? [];
$template = $data['template'] ?? null;
$settings = $data['settings'] ?? [];

$defaultHtml = '<h2>Hello {{first_name}},</h2><p>Thanks for being part of our community.</p><p>Use this template as a starting point for promotions, updates, and announcements.</p><p><a href="{{cta_url}}">Open the update</a></p><p>— Mailr Team</p>';
$initialHtml = trim((string) ($template['html_content'] ?? ''));
if ($initialHtml === '') {
    $initialHtml = $defaultHtml;
}

$initialText = trim((string) ($template['text_content'] ?? ''));
if ($initialText === '') {
    $initialText = trim(strip_tags($initialHtml));
}

$templateName = (string) ($template['name'] ?? '');
$templateCategory = (string) ($template['category'] ?? 'Marketing');
$templateDescription = (string) ($template['description'] ?? '');
$templateSourceText = (string) ($template['source_text'] ?? '');
$templateStatus = (string) ($template['status'] ?? 'Active');
$brandDefault = (string) ($settings['default_from_name'] ?? 'Mailr');
?>

<svg aria-hidden="true" class="ui-icon-sprite">
  <symbol id="tpl-icon-template" viewBox="0 0 24 24"><path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 3v2h14V7H5zm0 4v7h7v-7H5z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-spark" viewBox="0 0 24 24"><path d="M12 2l1.6 4.4L18 8l-4.4 1.6L12 14l-1.6-4.4L6 8l4.4-1.6L12 2zm6 10l.9 2.1L21 15l-2.1.9L18 18l-.9-2.1L15 15l2.1-.9L18 12zM6 14l1.1 2.9L10 18l-2.9 1.1L6 22l-1.1-2.9L2 18l2.9-1.1L6 14z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-eye" viewBox="0 0 24 24"><path d="M12 5c5.5 0 9.8 4.1 11 7-1.2 2.9-5.5 7-11 7S2.2 14.9 1 12c1.2-2.9 5.5-7 11-7zm0 2c-4.2 0-7.7 3-9 5 1.3 2 4.8 5 9 5s7.7-3 9-5c-1.3-2-4.8-5-9-5zm0 2.5a2.5 2.5 0 1 1-2.5 2.5A2.5 2.5 0 0 1 12 9.5z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-plus" viewBox="0 0 24 24"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-bold" viewBox="0 0 24 24"><path d="M8 4h6a4 4 0 0 1 0 8H8V4zm0 10h7a4 4 0 0 1 0 8H8v-8zm4-8H10v4h2a2 2 0 0 0 0-4zm1 10h-3v4h3a2 2 0 0 0 0-4z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-italic" viewBox="0 0 24 24"><path d="M10 4v2h3.2l-4.4 12H6v2h8v-2h-3.2l4.4-12H18V4h-8z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-underline" viewBox="0 0 24 24"><path d="M8 4v7a4 4 0 0 0 8 0V4h-2v7a2 2 0 0 1-4 0V4H8zm-2 14h12v2H6v-2z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-strike" viewBox="0 0 24 24"><path d="M6 11h12v2H6v-2zm6-7c-3 0-5 1.6-5 4 0 1.3.7 2.4 2 3.1h3.9c1.3.4 2.1.9 2.1 1.9 0 1.2-1.1 2-3 2-1.8 0-3.1-.7-4-1.5l-1.4 1.5C7.7 18 9.6 19 12 19c3.3 0 5.2-1.7 5.2-4.1 0-1.1-.5-2-1.4-2.7H8.9c-.9-.4-1.4-.9-1.4-1.7 0-1.1 1.1-1.8 2.6-1.8 1.6 0 2.8.5 3.8 1.3l1.3-1.6C14 4.8 12.4 4 10.1 4H12z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-undo" viewBox="0 0 24 24"><path d="M12 5a8 8 0 0 1 7.7 6h-2.1A6 6 0 0 0 12 7H7.8l2.6 2.6L9 11 4 6l5-5 1.4 1.4L7.8 5H12z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-redo" viewBox="0 0 24 24"><path d="M12 5a8 8 0 0 0-7.7 6h2.1A6 6 0 0 1 12 7h4.2l-2.6 2.6L15 11l5-5-5-5-1.4 1.4L16.2 5H12z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-list-bullets" viewBox="0 0 24 24"><path d="M9 6h11v2H9V6zm0 5h11v2H9v-2zm0 5h11v2H9v-2zM5 7.5A1.5 1.5 0 1 1 3.5 6 1.5 1.5 0 0 1 5 7.5zm0 5A1.5 1.5 0 1 1 3.5 11 1.5 1.5 0 0 1 5 12.5zm0 5A1.5 1.5 0 1 1 3.5 16 1.5 1.5 0 0 1 5 17.5z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-list-numbers" viewBox="0 0 24 24"><path d="M9 6h11v2H9V6zm0 5h11v2H9v-2zm0 5h11v2H9v-2zM3 6h3v1H5v1h1v1H3V8h1V7H3V6zm0 5h2.7v1H4v.5h1.7V14H3v-1.2c0-.7.4-1.1 1.1-1.1H5v-.2H3v-.5zm0 5h2.8c0 1.3-.8 2-2.2 2-.9 0-1.6-.3-2.1-.8l.7-.8c.4.4.8.6 1.4.6.5 0 .8-.2.9-.6H3v-.4h1.5c0-.4-.3-.6-.8-.6-.3 0-.7.1-1 .3l-.6-.9c.5-.4 1.1-.5 1.8-.5 1.2 0 2 .5 2.2 1.5H3V16z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-align-left" viewBox="0 0 24 24"><path d="M4 6h14v2H4V6zm0 4h10v2H4v-2zm0 4h14v2H4v-2zm0 4h10v2H4v-2z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-align-center" viewBox="0 0 24 24"><path d="M5 6h14v2H5V6zm3 4h8v2H8v-2zm-3 4h14v2H5v-2zm3 4h8v2H8v-2z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-align-right" viewBox="0 0 24 24"><path d="M6 6h14v2H6V6zm10 4h4v2h-4v-2zM6 14h14v2H6v-2zm10 4h4v2h-4v-2z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-align-justify" viewBox="0 0 24 24"><path d="M4 6h16v2H4V6zm0 4h16v2H4v-2zm0 4h16v2H4v-2zm0 4h16v2H4v-2z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-link" viewBox="0 0 24 24"><path d="M10.6 13.4l-1.4-1.4 5.2-5.2a3 3 0 1 1 4.2 4.2l-2.1 2.1-1.4-1.4 2.1-2.1a1 1 0 0 0-1.4-1.4l-5.2 5.2zM13.4 10.6l1.4 1.4-5.2 5.2a3 3 0 0 1-4.2-4.2l2.1-2.1 1.4 1.4-2.1 2.1a1 1 0 1 0 1.4 1.4l5.2-5.2z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-image" viewBox="0 0 24 24"><path d="M4 5h16v14H4V5zm2 2v10h12V7H6zm2 8l2.5-3 2 2.5 3-4L18 15H8zm2-5a1.5 1.5 0 1 0-1.5-1.5A1.5 1.5 0 0 0 10 10z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-erase" viewBox="0 0 24 24"><path d="M15.1 4.3l4.6 4.6-8.5 8.5H6.6L2 12.8l8.5-8.5a3.3 3.3 0 0 1 4.6 0zM9.4 16l7.5-7.5-3.2-3.2-7.5 7.5 2 2h1.2zM13 19h9v2h-9v-2z" fill="currentColor"/></symbol>
  <symbol id="tpl-icon-save" viewBox="0 0 24 24"><path d="M5 3h12l4 4v14H3V3h2zm2 2v5h8V5H7zm0 14h10v-7H7v7z" fill="currentColor"/></symbol>
</svg>

<section class="page-header page-header-create">
  <div>
    <div class="eyebrow"><svg class="icon"><use href="#tpl-icon-template"></use></svg>Template Studio</div>
    <h1>Email Templates</h1>
    <p>Create reusable email templates, edit them with the same WYSIWYG editor, and generate polished HTML from a plain-text brief.</p>
  </div>
  <div class="header-meta">
    <a class="ghost" href="/index.php?page=create-campaign"><svg class="icon"><use href="#tpl-icon-plus"></use></svg>New Campaign</a>
  </div>
</section>

<div class="templates-layout">
  <aside class="templates-sidebar">
    <section class="card templates-list-card sticky-panel">
      <div class="templates-list-head">
        <h2>Saved Templates</h2>
        <a class="ghost" href="/index.php?page=templates"><svg class="icon"><use href="#tpl-icon-plus"></use></svg>New</a>
      </div>
      <?php if (count($templates) === 0): ?>
        <p class="muted">No templates yet.</p>
      <?php else: ?>
        <div class="templates-list">
          <?php foreach ($templates as $row): ?>
            <?php $isActive = $template && (int) $template['id'] === (int) $row['id']; ?>
            <a class="template-list-item <?php echo $isActive ? 'active' : ''; ?>" href="/index.php?page=templates&template_id=<?php echo (int) $row['id']; ?>">
              <div class="template-list-item-top">
                <strong><?php echo htmlspecialchars((string) $row['name']); ?></strong>
                <span class="status <?php echo status_badge_class((string) ($row['status'] ?? 'Active')); ?>"><?php echo htmlspecialchars((string) ($row['status'] ?? 'Active')); ?></span>
              </div>
              <div class="template-list-item-meta">
                <span><?php echo htmlspecialchars((string) ($row['category'] ?? 'General')); ?></span>
                <span><?php echo htmlspecialchars((string) ($row['updated_at'] ?? '')); ?></span>
              </div>
              <?php if (!empty($row['description'])): ?>
                <p><?php echo htmlspecialchars((string) $row['description']); ?></p>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </aside>

  <section class="templates-main">
    <form class="form-grid template-editor-form" method="post">
      <input type="hidden" name="template_id" value="<?php echo (int) ($template['id'] ?? 0); ?>">

      <section class="card section-card">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">Template Details</div>
            <h2><?php echo $template ? 'Edit Template' : 'Create Template'; ?></h2>
            <p class="muted">Templates can be reused when composing campaigns. Include placeholders like `{{first_name}}` and `{{cta_url}}`.</p>
          </div>
          <div class="button-row">
            <?php if ($template): ?>
              <a class="ghost" href="/index.php?page=create-campaign">Use In Campaign</a>
            <?php endif; ?>
            <button class="button" type="submit" name="action" value="save_email_template" data-loading-text="Saving..."><svg class="icon"><use href="#tpl-icon-save"></use></svg>Save Template</button>
          </div>
        </div>

        <div class="field-grid">
          <label class="field field-with-meta">
            <span class="field-label-row">Template Name <?php echo ui_info_popover('Internal name shown in the template library.'); ?></span>
            <input id="tplName" type="text" name="name" value="<?php echo htmlspecialchars($templateName); ?>" placeholder="e.g. Product Launch Announcement" required>
          </label>
          <label class="field field-with-meta">
            <span class="field-label-row">Category <?php echo ui_info_popover('Used for organization and filtering in the template library.'); ?></span>
            <input id="tplCategory" type="text" name="category" value="<?php echo htmlspecialchars($templateCategory); ?>" placeholder="Marketing / Product / Events">
          </label>
          <label class="field field-with-meta">
            <span class="field-label-row">Description <?php echo ui_info_popover('Visible in the template list to help teammates choose the right template.'); ?></span>
            <input id="tplDescription" type="text" name="description" value="<?php echo htmlspecialchars($templateDescription); ?>" placeholder="Short summary of when to use this template">
          </label>
          <label class="field">
            <span>Status</span>
            <select name="status">
              <option value="Active" <?php echo $templateStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
              <option value="Paused" <?php echo $templateStatus === 'Paused' ? 'selected' : ''; ?>>Paused</option>
            </select>
          </label>
        </div>
      </section>

      <section class="card section-card">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">AI Drafting</div>
            <h2>Convert Text Brief To HTML Template</h2>
            <p class="muted">Paste a brief or plain text email. The generator will convert it into a polished HTML email template. Uses free local Ollama first (if running), then Hugging Face Inference (hosted free-tier token), then optional OpenAI, then a local beautifier fallback.</p>
          </div>
          <button type="button" class="ghost" id="generateWithAi" data-loading-text="Generating..."><svg class="icon"><use href="#tpl-icon-spark"></use></svg>Generate HTML</button>
        </div>

        <div class="templates-ai-grid">
          <label class="field field-span-2">
            <span>Source Text / Brief</span>
            <textarea id="tplSourceText" name="source_text" rows="7" placeholder="Write the email brief, goals, offer, CTA, audience, and key points..."><?php echo htmlspecialchars($templateSourceText); ?></textarea>
            <span class="helper-inline"><?php echo ui_info_popover('Tip: include bullets and a CTA URL if you have one.'); ?></span>
            <span class="helper-inline"><?php echo ui_info_popover('Free AI options: run Ollama locally or use a Hugging Face Router token via MAILR_HF_API_KEY.'); ?></span>
          </label>
          <label class="field">
            <span>AI Tone</span>
            <select id="tplTone">
              <option>Modern and polished</option>
              <option>Sales-focused and direct</option>
              <option>Warm and conversational</option>
              <option>Minimal and product-led</option>
            </select>
          </label>
          <label class="field">
            <span>Brand Name</span>
            <input id="tplBrand" type="text" value="<?php echo htmlspecialchars($brandDefault !== '' ? $brandDefault : 'Mailr'); ?>">
          </label>
          <label class="field">
            <span>Accent Color</span>
            <input id="tplAccent" type="color" value="#1f7a6d">
          </label>
        </div>
        <div id="aiTemplateFeedback" class="inline-feedback" hidden aria-live="polite"></div>
      </section>

      <section class="card section-card">
        <div class="section-card-head">
          <div>
            <div class="section-kicker">Editor</div>
            <h2>WYSIWYG Template Editor</h2>
            <p class="muted">Edit the generated HTML or compose from scratch. Preview opens in a modal.</p>
          </div>
          <div class="template-row">
            <div class="editor-mode-switch" role="tablist" aria-label="Editor mode">
              <button type="button" class="ghost active" data-tpl-editor-mode="visual" aria-selected="true">Design</button>
              <button type="button" class="ghost" data-tpl-editor-mode="html" aria-selected="false">HTML</button>
            </div>
            <button type="button" class="ghost" id="tplQuickStarter">Starter</button>
            <button type="button" class="ghost" id="tplQuickBlank">Blank</button>
            <button type="button" class="ghost" id="openTemplatePreview"><svg class="icon"><use href="#tpl-icon-eye"></use></svg>Live Preview</button>
          </div>
        </div>

        <div class="editor-toolbar" role="toolbar" aria-label="Template editor toolbar">
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="bold" aria-label="Bold" title="Bold"><svg class="icon"><use href="#tpl-icon-bold"></use></svg><span class="sr-only">Bold</span></button>
            <button type="button" class="ghost icon-btn" data-command="italic" aria-label="Italic" title="Italic"><svg class="icon"><use href="#tpl-icon-italic"></use></svg><span class="sr-only">Italic</span></button>
            <button type="button" class="ghost icon-btn" data-command="underline" aria-label="Underline" title="Underline"><svg class="icon"><use href="#tpl-icon-underline"></use></svg><span class="sr-only">Underline</span></button>
            <button type="button" class="ghost icon-btn" data-command="strikeThrough" aria-label="Strikethrough" title="Strikethrough"><svg class="icon"><use href="#tpl-icon-strike"></use></svg><span class="sr-only">Strikethrough</span></button>
            <button type="button" class="ghost icon-btn" data-command="undo" aria-label="Undo" title="Undo"><svg class="icon"><use href="#tpl-icon-undo"></use></svg><span class="sr-only">Undo</span></button>
            <button type="button" class="ghost icon-btn" data-command="redo" aria-label="Redo" title="Redo"><svg class="icon"><use href="#tpl-icon-redo"></use></svg><span class="sr-only">Redo</span></button>
          </div>
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="insertUnorderedList" aria-label="Bulleted list" title="Bulleted list"><svg class="icon"><use href="#tpl-icon-list-bullets"></use></svg><span class="sr-only">Bulleted list</span></button>
            <button type="button" class="ghost icon-btn" data-command="insertOrderedList" aria-label="Numbered list" title="Numbered list"><svg class="icon"><use href="#tpl-icon-list-numbers"></use></svg><span class="sr-only">Numbered list</span></button>
          </div>
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="justifyLeft" aria-label="Align left" title="Align left"><svg class="icon"><use href="#tpl-icon-align-left"></use></svg><span class="sr-only">Align left</span></button>
            <button type="button" class="ghost icon-btn" data-command="justifyCenter" aria-label="Align center" title="Align center"><svg class="icon"><use href="#tpl-icon-align-center"></use></svg><span class="sr-only">Align center</span></button>
            <button type="button" class="ghost icon-btn" data-command="justifyRight" aria-label="Align right" title="Align right"><svg class="icon"><use href="#tpl-icon-align-right"></use></svg><span class="sr-only">Align right</span></button>
            <button type="button" class="ghost icon-btn" data-command="justifyFull" aria-label="Justify" title="Justify"><svg class="icon"><use href="#tpl-icon-align-justify"></use></svg><span class="sr-only">Justify</span></button>
          </div>
          <div class="toolbar-group">
            <label class="field inline compact-field">
              <span>Style</span>
              <select id="tplFormatBlock" class="template-select small">
                <option value="P">Paragraph</option>
                <option value="H1">Heading 1</option>
                <option value="H2">Heading 2</option>
                <option value="H3">Heading 3</option>
                <option value="BLOCKQUOTE">Quote</option>
              </select>
            </label>
            <label class="field inline compact-field">
              <span>Size</span>
              <select id="tplFontSizeSelect" class="template-select small">
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
              <input type="color" id="tplForeColor" value="#1e1e1e">
            </label>
            <label class="field inline compact-field">
              <span>Highlight</span>
              <input type="color" id="tplHiliteColor" value="#f2c14e">
            </label>
          </div>
          <div class="toolbar-group">
            <button type="button" class="ghost icon-btn" data-command="createLink" aria-label="Insert link" title="Insert link"><svg class="icon"><use href="#tpl-icon-link"></use></svg><span class="sr-only">Insert link</span></button>
            <button type="button" class="ghost icon-btn" data-command="insertImage" aria-label="Insert image" title="Insert image"><svg class="icon"><use href="#tpl-icon-image"></use></svg><span class="sr-only">Insert image</span></button>
            <button type="button" class="ghost icon-btn" data-command="removeFormat" aria-label="Clear formatting" title="Clear formatting"><svg class="icon"><use href="#tpl-icon-erase"></use></svg><span class="sr-only">Clear formatting</span></button>
          </div>
          <div class="toolbar-divider"></div>
          <label class="field inline compact-field">
            <span>Insert</span>
            <select id="tplPlaceholderSelect" class="template-select">
              <option value="{{first_name}}">First name</option>
              <option value="{{company}}">Company</option>
              <option value="{{cta_url}}">CTA URL</option>
              <option value="{{email}}">Email</option>
            </select>
          </label>
          <button type="button" class="ghost icon-btn" id="tplInsertPlaceholder" aria-label="Insert placeholder" title="Insert placeholder"><svg class="icon"><use href="#tpl-icon-plus"></use></svg><span class="sr-only">Insert placeholder</span></button>
        </div>

        <div class="editor-shell">
          <div class="editor-pane" id="tplVisualEditorPane">
            <div id="tplEditor" class="editor" contenteditable="true"><?php echo $initialHtml; ?></div>
            <div id="tplLinkInspector" class="link-inspector" hidden>
              <div class="link-inspector-url-row">
                <span class="link-inspector-label">Link</span>
                <a id="tplLinkInspectorUrl" href="#" target="_blank" rel="noopener noreferrer" class="link-inspector-url">—</a>
              </div>
              <div class="link-inspector-actions">
                <button type="button" class="ghost" id="tplLinkInspectorEdit">Edit</button>
                <button type="button" class="ghost" id="tplLinkInspectorRemove">Remove</button>
              </div>
            </div>
            <div id="tplImageInspector" class="link-inspector image-inspector" hidden>
              <div class="link-inspector-url-row">
                <span class="link-inspector-label">Image</span>
                <a id="tplImageInspectorSrc" href="#" target="_blank" rel="noopener noreferrer" class="link-inspector-url">—</a>
              </div>
              <label class="field compact-field">
                <span>Alt Text</span>
                <input id="tplImageInspectorAlt" type="text" placeholder="Describe the image" />
              </label>
              <div class="image-size-controls">
                <label class="field inline compact-field">
                  <span>Width</span>
                  <input id="tplImageInspectorWidth" type="range" min="80" max="700" step="10" value="320" />
                </label>
                <label class="field inline compact-field">
                  <span>px</span>
                  <input id="tplImageInspectorWidthNumber" type="number" min="40" max="1200" step="10" value="320" />
                </label>
              </div>
              <div class="image-preset-row">
                <button type="button" class="ghost" data-tpl-image-width="25%">25%</button>
                <button type="button" class="ghost" data-tpl-image-width="50%">50%</button>
                <button type="button" class="ghost" data-tpl-image-width="100%">100%</button>
                <button type="button" class="ghost" data-tpl-image-width="auto">Auto</button>
              </div>
              <div class="link-inspector-actions">
                <button type="button" class="ghost" id="tplImageInspectorReplace">Replace URL</button>
                <button type="button" class="ghost" id="tplImageInspectorRemoveImage">Remove</button>
              </div>
            </div>
          </div>
          <div id="tplCodeEditorPane" class="code-editor-pane" hidden>
            <div class="code-editor-head">
              <span class="code-editor-label">HTML Source</span>
              <div class="code-editor-head-actions">
                <span class="muted">Direct HTML editing mode</span>
                <button type="button" class="ghost" id="tplFormatHtmlSource">Format HTML</button>
              </div>
            </div>
            <textarea id="tplHtmlCodeEditor" class="code-editor" spellcheck="false"></textarea>
          </div>
          <div class="editor-footer-note">
            <span class="muted">Preview opens in a modal. Saved HTML is stored for reuse in campaigns.</span>
          </div>
        </div>

        <textarea id="tplHtmlContent" name="html_content" hidden><?php echo htmlspecialchars($initialHtml); ?></textarea>
        <textarea id="tplTextContent" name="text_content" hidden><?php echo htmlspecialchars($initialText); ?></textarea>
      </section>

      <?php if ($template): ?>
        <section class="card section-card danger-zone">
          <div class="section-card-head">
            <div>
              <div class="section-kicker">Danger Zone</div>
              <h2>Delete Template</h2>
              <p class="muted">This removes the template from the library. Existing campaigns are not modified.</p>
            </div>
            <button class="ghost" id="deleteTemplateBtn" type="submit" name="action" value="delete_email_template" data-loading-text="Deleting...">Delete Template</button>
          </div>
        </section>
      <?php endif; ?>
    </form>
  </section>
</div>

<dialog id="templatePreviewModal" class="preview-modal">
  <div class="modal-head">
    <h3>Template Preview</h3>
    <button type="button" class="ghost" id="closeTemplatePreview">Close</button>
  </div>
  <div class="modal-body">
    <div class="preview-header modal-preview-header">
      <div>
        <strong>Preview</strong>
        <span class="muted">Rendered HTML and text fallback</span>
      </div>
      <div class="preview-tabs">
        <button type="button" class="ghost active" data-template-preview-mode="html">HTML</button>
        <button type="button" class="ghost" data-template-preview-mode="text">Text</button>
      </div>
    </div>
    <div id="templateModalPreview" class="preview-body"></div>
    <pre id="templateModalTextPreview" class="preview-text" hidden></pre>
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
(() => {
  const form = document.querySelector('.template-editor-form');
  if (!form) return;
  const deleteTemplateBtn = document.getElementById('deleteTemplateBtn');

  const editor = document.getElementById('tplEditor');
  const visualEditorPane = document.getElementById('tplVisualEditorPane');
  const codeEditorPane = document.getElementById('tplCodeEditorPane');
  const htmlCodeEditor = document.getElementById('tplHtmlCodeEditor');
  const formatHtmlSource = document.getElementById('tplFormatHtmlSource');
  const htmlInput = document.getElementById('tplHtmlContent');
  const textInput = document.getElementById('tplTextContent');
  const sourceText = document.getElementById('tplSourceText');
  const aiButton = document.getElementById('generateWithAi');
  const aiFeedback = document.getElementById('aiTemplateFeedback');
  const toneInput = document.getElementById('tplTone');
  const brandInput = document.getElementById('tplBrand');
  const accentInput = document.getElementById('tplAccent');
  const nameInput = document.getElementById('tplName');
  const categoryInput = document.getElementById('tplCategory');

  const formatBlock = document.getElementById('tplFormatBlock');
  const fontSizeSelect = document.getElementById('tplFontSizeSelect');
  const foreColor = document.getElementById('tplForeColor');
  const hiliteColor = document.getElementById('tplHiliteColor');
  const placeholderSelect = document.getElementById('tplPlaceholderSelect');
  const insertPlaceholder = document.getElementById('tplInsertPlaceholder');
  const starterBtn = document.getElementById('tplQuickStarter');
  const blankBtn = document.getElementById('tplQuickBlank');

  const openPreview = document.getElementById('openTemplatePreview');
  const previewModal = document.getElementById('templatePreviewModal');
  const closePreview = document.getElementById('closeTemplatePreview');
  const modalPreview = document.getElementById('templateModalPreview');
  const modalTextPreview = document.getElementById('templateModalTextPreview');

  const linkInspector = document.getElementById('tplLinkInspector');
  const linkInspectorUrl = document.getElementById('tplLinkInspectorUrl');
  const linkInspectorEdit = document.getElementById('tplLinkInspectorEdit');
  const linkInspectorRemove = document.getElementById('tplLinkInspectorRemove');
  const imageInspector = document.getElementById('tplImageInspector');
  const imageInspectorSrc = document.getElementById('tplImageInspectorSrc');
  const imageInspectorAlt = document.getElementById('tplImageInspectorAlt');
  const imageInspectorWidth = document.getElementById('tplImageInspectorWidth');
  const imageInspectorWidthNumber = document.getElementById('tplImageInspectorWidthNumber');
  const imageInspectorReplace = document.getElementById('tplImageInspectorReplace');
  const imageInspectorRemoveImage = document.getElementById('tplImageInspectorRemoveImage');
  const imagePresetButtons = document.querySelectorAll('[data-tpl-image-width]');
  const editorModeButtons = document.querySelectorAll('[data-tpl-editor-mode]');
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

  const quickTemplates = {
    starter: `<h2>Hello {{first_name}},</h2><p>We have a quick update for you.</p><ul><li>Feature highlight</li><li>Customer win</li><li>Next step</li></ul><p><a href="{{cta_url}}">Open the update</a></p><p>-- Mailr Team</p>`,
    blank: `<p>Start writing your template here...</p>`
  };

  const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const setAiFeedback = (type, message) => {
    if (!aiFeedback) return;
    aiFeedback.hidden = false;
    aiFeedback.className = `inline-feedback ${type}`;
    aiFeedback.innerHTML = `<div class="inline-feedback-title">${escapeHtml(message)}</div>`;
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
    if (codeEditorInstance) codeEditorInstance.setValue(String(value ?? ''));
    else if (htmlCodeEditor) htmlCodeEditor.value = String(value ?? '');
  };
  const ensureCodeEditor = () => {
    if (codeEditorInstance || !htmlCodeEditor || !window.CodeMirror) return;
    codeEditorInstance = window.CodeMirror.fromTextArea(htmlCodeEditor, {
      mode: 'htmlmixed',
      lineNumbers: true,
      lineWrapping: true,
      tabSize: 2,
      indentUnit: 2,
    });
    codeEditorInstance.on('change', () => {
      if (isHtmlMode()) syncContent();
    });
  };
  const getCurrentHtml = () => isHtmlMode() ? getCodeEditorValue().trim() : ((editor?.innerHTML || '').trim());

  const setCurrentHtml = (html) => {
    const next = String(html ?? '');
    if (editor) editor.innerHTML = next;
    setCodeEditorValue(next);
  };

  const updateEditorModeUI = () => {
    editorModeButtons.forEach((button) => {
      const active = button.dataset.tplEditorMode === currentEditorMode;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    if (visualEditorPane) visualEditorPane.hidden = isHtmlMode();
    if (codeEditorPane) codeEditorPane.hidden = !isHtmlMode();
    document.querySelectorAll('.editor-toolbar [data-command], .editor-toolbar select, .editor-toolbar input[type="color"], #tplInsertPlaceholder').forEach((control) => {
      control.disabled = isHtmlMode();
    });
    hideLinkInspector();
    hideImageInspector();
  };

  const setEditorMode = async (mode) => {
    if (!['visual', 'html'].includes(mode) || mode === currentEditorMode) return;
    if (mode === 'visual') {
      const sourceHtml = getCodeEditorValue();
      if (looksLikeFullDocumentHtml(sourceHtml) && !allowVisualModeForFullHtml) {
        const approved = await uiDialog.confirm(
          'This template contains a full HTML document (head/style/body). Visual mode may alter styling. Continue anyway?',
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
    if (mode === 'html' && htmlCodeEditor && editor) {
      setCodeEditorValue(editor.innerHTML.trim());
      ensureCodeEditor();
    }
    if (mode === 'visual' && htmlCodeEditor && editor) editor.innerHTML = getCodeEditorValue();
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
    }
    else if (editor) editor.focus();
  };

  const syncContent = () => {
    const html = getCurrentHtml();
    const text = htmlToText(html);
    if (htmlInput) htmlInput.value = html;
    if (textInput) textInput.value = text;
    if (modalPreview) modalPreview.innerHTML = html;
    if (modalTextPreview) modalTextPreview.textContent = text || 'Text-only preview will appear here.';
  };

  const findLinkInNode = (node) => {
    let current = node;
    while (current && current !== editor) {
      if (current.nodeType === Node.ELEMENT_NODE && current.tagName === 'A') return current;
      current = current.parentNode;
    }
    return null;
  };

  const getEditorSelectionRange = () => {
    const selection = window.getSelection ? window.getSelection() : null;
    if (!selection || selection.rangeCount === 0) return null;
    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;
    if (!container || !(container === editor || editor.contains(container))) return null;
    return range;
  };

  const hideLinkInspector = () => {
    activeEditorLink = null;
    linkInspectorPinned = false;
    if (linkInspector) linkInspector.hidden = true;
  };

  const hideImageInspector = () => {
    activeEditorImage = null;
    if (imageInspector) imageInspector.hidden = true;
  };

  const positionLinkInspector = (range) => {
    if (!linkInspector || !range || !editor) return;
    const pane = editor.closest('.editor-pane');
    if (!pane) return;
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
    if (!editor || !linkInspector || isHtmlMode()) return;
    const range = getEditorSelectionRange();
    if (!range) return hideLinkInspector();
    const selection = window.getSelection ? window.getSelection() : null;
    const isCollapsed = !selection || selection.isCollapsed;
    const link = findLinkInNode(range.startContainer) || findLinkInNode(range.endContainer);
    if (!link) return hideLinkInspector();
    if (isCollapsed && !linkInspectorPinned) return hideLinkInspector();
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
    if (!imageInspector || !img || !editor) return;
    const pane = editor.closest('.editor-pane');
    if (!pane) return;
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
    if (imageInspectorAlt) imageInspectorAlt.value = alt;
    if (imageInspectorWidth) imageInspectorWidth.value = String(Math.max(80, Math.min(700, widthPx)));
    if (imageInspectorWidthNumber) imageInspectorWidthNumber.value = String(widthPx);
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
      if (value.endsWith('px')) activeEditorImage.setAttribute('width', String(parseInt(value, 10)));
      else activeEditorImage.removeAttribute('width');
    }
    activeEditorImage.style.maxWidth = '100%';
    activeEditorImage.style.height = 'auto';
    syncContent();
    showImageInspector(activeEditorImage);
  };

  document.querySelectorAll('.editor-toolbar [data-command]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (isHtmlMode()) return;
      if (!editor) return;
      editor.focus();
      const command = button.dataset.command;
      if (command === 'createLink') {
        linkInspectorPinned = true;
        const currentHref = activeEditorLink ? (activeEditorLink.getAttribute('href') || '') : '';
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
      syncContent();
      updateLinkInspector();
    });
  });

  if (formatBlock) formatBlock.addEventListener('change', () => { if (isHtmlMode()) return; editor.focus(); document.execCommand('formatBlock', false, formatBlock.value); syncContent(); });
  if (fontSizeSelect) fontSizeSelect.addEventListener('change', () => { if (isHtmlMode()) return; editor.focus(); document.execCommand('fontSize', false, fontSizeSelect.value); syncContent(); });
  if (foreColor) foreColor.addEventListener('input', () => { if (isHtmlMode()) return; editor.focus(); document.execCommand('foreColor', false, foreColor.value); syncContent(); });
  if (hiliteColor) hiliteColor.addEventListener('input', () => { if (isHtmlMode()) return; editor.focus(); document.execCommand('hiliteColor', false, hiliteColor.value); syncContent(); });

  if (insertPlaceholder) {
    insertPlaceholder.addEventListener('click', () => {
      if (isHtmlMode()) return;
      editor.focus();
      document.execCommand('insertText', false, placeholderSelect.value);
      syncContent();
      updateLinkInspector();
    });
  }

  if (starterBtn) starterBtn.addEventListener('click', () => { editor.innerHTML = quickTemplates.starter; syncContent(); });
  if (blankBtn) blankBtn.addEventListener('click', () => { editor.innerHTML = quickTemplates.blank; syncContent(); });
  editorModeButtons.forEach((button) => {
    button.addEventListener('click', () => setEditorMode(button.dataset.tplEditorMode || 'visual'));
  });

  document.querySelectorAll('[data-template-preview-mode]').forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('[data-template-preview-mode]').forEach((b) => b.classList.remove('active'));
      tab.classList.add('active');
      const mode = tab.dataset.templatePreviewMode;
      if (mode === 'text') {
        if (modalTextPreview) modalTextPreview.hidden = false;
        if (modalPreview) modalPreview.style.display = 'none';
      } else {
        if (modalTextPreview) modalTextPreview.hidden = true;
        if (modalPreview) modalPreview.style.display = 'block';
      }
    });
  });

  if (openPreview && previewModal) {
    openPreview.addEventListener('click', () => {
      syncContent();
      if (modalPreview) modalPreview.style.display = 'block';
      if (modalTextPreview) modalTextPreview.hidden = true;
      document.querySelectorAll('[data-template-preview-mode]').forEach((button) => {
        button.classList.toggle('active', button.dataset.templatePreviewMode === 'html');
      });
      if (previewModal.showModal) previewModal.showModal();
      else previewModal.setAttribute('open', 'open');
    });
  }

  if (closePreview && previewModal) {
    closePreview.addEventListener('click', () => {
      if (previewModal.close) previewModal.close();
      else previewModal.removeAttribute('open');
    });
  }

  [linkInspectorEdit, linkInspectorRemove].forEach((button) => {
    if (!button) return;
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
      if (!activeEditorLink) return;
      linkInspectorPinned = true;
      const currentHref = activeEditorLink.getAttribute('href') || '';
      const nextHref = await uiDialog.prompt('Edit link URL', currentHref, { title: 'Edit Link', okText: 'Save', placeholder: 'https://example.com' });
      if (nextHref === null) {
        linkInspectorPinned = false;
        updateLinkInspector();
        return;
      }
      const trimmed = nextHref.trim();
      if (trimmed === '') activeEditorLink.removeAttribute('href');
      else {
        activeEditorLink.setAttribute('href', trimmed);
        activeEditorLink.setAttribute('target', '_blank');
        activeEditorLink.setAttribute('rel', 'noopener noreferrer');
      }
      syncContent();
      linkInspectorPinned = false;
      updateLinkInspector();
    });
  }

  if (linkInspectorRemove) {
    linkInspectorRemove.addEventListener('click', () => {
      if (!activeEditorLink || !activeEditorLink.parentNode) return;
      const link = activeEditorLink;
      const parent = link.parentNode;
      while (link.firstChild) parent.insertBefore(link.firstChild, link);
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
      const widthValue = button.dataset.tplImageWidth || 'auto';
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
      if (!activeEditorImage) return;
      activeEditorImage.remove();
      syncContent();
      hideImageInspector();
      if (editor) editor.focus();
    });
  }

  if (aiButton) {
    aiButton.addEventListener('click', async () => {
      const brief = sourceText ? sourceText.value.trim() : '';
      if (!brief) {
        setAiFeedback('error', 'Add source text or a brief before generating.');
        return;
      }

      const originalHtml = aiButton.innerHTML;
      aiButton.disabled = true;
      aiButton.classList.add('is-loading');
      aiButton.innerHTML = aiButton.dataset.loadingText || 'Generating...';
      setAiFeedback('pending', 'Generating HTML template...');

      try {
        const body = new FormData();
        body.set('action', 'generate_template_ai');
        body.set('source_text', brief);
        body.set('name', nameInput ? nameInput.value : '');
        body.set('category', categoryInput ? categoryInput.value : '');
        body.set('tone', toneInput ? toneInput.value : 'Modern and polished');
        body.set('brand', brandInput ? brandInput.value : 'Mailr');
        body.set('accent', accentInput ? accentInput.value : '#1f7a6d');
        if (window.mailrCsrfToken) {
          body.set('_csrf', window.mailrCsrfToken);
        }

        const response = await fetch(window.location.pathname + window.location.search, {
          method: 'POST',
          body,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const payload = await response.json();
        if (!payload.ok || !payload.html) {
          throw new Error(payload.message || 'Generation failed.');
        }

        setCurrentHtml(String(payload.html).trim());
        if (payload.text && textInput) {
          textInput.value = String(payload.text);
        }
        syncContent();
        setAiFeedback('success', `${payload.message || 'Template generated.'}${payload.provider ? ` (${payload.provider})` : ''}`);
      } catch (error) {
        setAiFeedback('error', error.message || 'Generation failed.');
      } finally {
        aiButton.disabled = false;
        aiButton.classList.remove('is-loading');
        aiButton.innerHTML = originalHtml;
      }
    });
  }

  if (editor) {
    editor.addEventListener('input', () => { if (isHtmlMode()) return; syncContent(); updateLinkInspector(); });
    editor.addEventListener('mouseup', () => { if (isHtmlMode()) return; linkInspectorPinned = false; updateLinkInspector(); });
    editor.addEventListener('keyup', () => { if (isHtmlMode()) return; linkInspectorPinned = false; updateLinkInspector(); });
    editor.addEventListener('click', (event) => {
      if (isHtmlMode()) return;
      const target = event.target;
      if (target instanceof HTMLImageElement) {
        showImageInspector(target);
        return;
      }
      if (!(imageInspector && imageInspector.contains(target))) hideImageInspector();
    });
    editor.addEventListener('blur', () => {
      setTimeout(() => {
        if (linkInspectorInteracting || imageInspectorInteracting) return;
        const active = document.activeElement;
        if (!linkInspector || !active || !linkInspector.contains(active)) hideLinkInspector();
        if (!imageInspector || !active || !imageInspector.contains(active)) hideImageInspector();
      }, 0);
    });
    editor.addEventListener('focus', updateLinkInspector);
  }

  document.addEventListener('selectionchange', () => {
    if (linkInspectorInteracting || imageInspectorInteracting) return;
    if (isHtmlMode()) {
      hideLinkInspector();
      hideImageInspector();
      return;
    }
    const selection = window.getSelection ? window.getSelection() : null;
    const node = selection && selection.anchorNode ? selection.anchorNode : null;
    if (node && editor && (node === editor || editor.contains(node))) {
      updateLinkInspector();
      return;
    }
    if (linkInspector && !linkInspector.hidden) hideLinkInspector();
    if (imageInspector && !imageInspector.hidden) hideImageInspector();
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) return;
    if ((editor && editor.contains(target)) || (linkInspector && linkInspector.contains(target)) || (imageInspector && imageInspector.contains(target))) return;
    hideLinkInspector();
    hideImageInspector();
  });
  document.addEventListener('focusin', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) {
      hideLinkInspector();
      hideImageInspector();
      return;
    }
    if ((editor && editor.contains(target)) || (linkInspector && linkInspector.contains(target)) || (imageInspector && imageInspector.contains(target))) return;
    hideLinkInspector();
    hideImageInspector();
  });
  document.addEventListener('pointerdown', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) return;
    if ((editor && editor.contains(target)) || (linkInspector && linkInspector.contains(target)) || (imageInspector && imageInspector.contains(target))) return;
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
      }
      syncContent();
      if (codeEditorInstance) codeEditorInstance.refresh();
    });
  }

  if (deleteTemplateBtn) {
    deleteTemplateBtn.addEventListener('click', async (event) => {
      event.preventDefault();
      const ok = await uiDialog.confirm('Delete this template? This cannot be undone.', {
        title: 'Delete Template',
        okText: 'Delete',
        cancelText: 'Keep'
      });
      if (ok) {
        form.requestSubmit(deleteTemplateBtn);
      }
    });
  }

  if (htmlInput && looksLikeFullDocumentHtml(htmlInput.value)) {
    currentEditorMode = 'html';
    ensureCodeEditor();
    setCodeEditorValue(htmlInput.value);
  }
  updateEditorModeUI();
  syncContent();
  hideLinkInspector();
  hideImageInspector();
})();
</script>
