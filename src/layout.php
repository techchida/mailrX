<?php

declare(strict_types=1);

function render_header(string $title, string $activePage): void
{
    $isAuthed = function_exists('auth_is_authenticated') ? auth_is_authenticated() : false;
    $csrf = function_exists('csrf_token') ? csrf_token() : '';
    $pages = [
        'dashboard' => ['label' => 'Campaigns', 'icon' => 'uii-dashboard'],
        'create-campaign' => ['label' => 'Create Campaign', 'icon' => 'uii-compose'],
        'templates' => ['label' => 'Templates', 'icon' => 'uii-template'],
        'configs-test' => ['label' => 'Configs & Test Contacts', 'icon' => 'uii-settings'],
        'manage-contacts' => ['label' => 'Contact Lists', 'icon' => 'uii-users'],
    ];

    echo "<!doctype html>\n";
    echo "<html lang=\"en\">\n";
    echo "<head>\n";
    echo "  <meta charset=\"utf-8\">\n";
    echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo "  <meta name=\"mailr-csrf\" content=\"" . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "  <title>Mailr · {$title}</title>\n";
    echo "  <link rel=\"stylesheet\" href=\"" . htmlspecialchars(asset_url('style.css'), ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "  <svg aria-hidden=\"true\" class=\"ui-icon-sprite\">\n";
    echo "    <symbol id=\"uii-dashboard\" viewBox=\"0 0 24 24\"><path d=\"M4 13h7v7H4v-7zm9-9h7v16h-7V4zM4 4h7v7H4V4z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-compose\" viewBox=\"0 0 24 24\"><path d=\"M4 20l4.5-1 9-9-3.5-3.5-9 9L4 20zm10-13.5l3.5 3.5 1.5-1.5a1.5 1.5 0 0 0 0-2.1l-1.4-1.4a1.5 1.5 0 0 0-2.1 0L14 6.5z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-template\" viewBox=\"0 0 24 24\"><path d=\"M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 3v2h14V7H5zm0 4v7h7v-7H5z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-settings\" viewBox=\"0 0 24 24\"><path d=\"M12 8.5A3.5 3.5 0 1 0 15.5 12 3.5 3.5 0 0 0 12 8.5zm9 4.5l-2 .7a7.8 7.8 0 0 1-.6 1.4l1 1.9-2.1 2.1-1.9-1a7.8 7.8 0 0 1-1.4.6l-.7 2h-3l-.7-2a7.8 7.8 0 0 1-1.4-.6l-1.9 1-2.1-2.1 1-1.9a7.8 7.8 0 0 1-.6-1.4l-2-.7v-3l2-.7a7.8 7.8 0 0 1 .6-1.4l-1-1.9L6.2 2.8l1.9 1a7.8 7.8 0 0 1 1.4-.6l.7-2h3l.7 2a7.8 7.8 0 0 1 1.4.6l1.9-1 2.1 2.1-1 1.9a7.8 7.8 0 0 1 .6 1.4l2 .7v3z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-users\" viewBox=\"0 0 24 24\"><path d=\"M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm-8 1a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm8 1c-2.2 0-4 1.3-4 3v2h8v-2c0-1.7-1.8-3-4-3zM8 14c-2.8 0-5 1.6-5 3.5V19h7v-1c0-1.2.5-2.2 1.4-3C10.6 14.4 9.3 14 8 14z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-info\" viewBox=\"0 0 24 24\"><path d=\"M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-upload\" viewBox=\"0 0 24 24\"><path d=\"M12 3l5 5h-3v6h-4V8H7l5-5zm-7 14h14v4H5v-4z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-server\" viewBox=\"0 0 24 24\"><path d=\"M4 5h16v5H4V5zm0 9h16v5H4v-5zm3-6a1 1 0 1 0 0 .01V8zm0 9a1 1 0 1 0 0 .01V17z\" fill=\"currentColor\"/></symbol>\n";
    echo "    <symbol id=\"uii-alert\" viewBox=\"0 0 24 24\"><path d=\"M12 3L2 20h20L12 3zm1 13h-2v-2h2v2zm0-4h-2V9h2v3z\" fill=\"currentColor\"/></symbol>\n";
    echo "  </svg>\n";
    echo "  <dialog id=\"mailrUiDialog\" class=\"app-ui-dialog\">\n";
    echo "    <form method=\"dialog\" class=\"app-ui-dialog-panel\" id=\"mailrUiDialogForm\">\n";
    echo "      <div class=\"app-ui-dialog-head\"><span class=\"app-ui-dialog-icon\"><svg class=\"icon\" aria-hidden=\"true\"><use href=\"#uii-alert\"></use></svg></span><div><h3 id=\"mailrUiDialogTitle\">Notice</h3><p id=\"mailrUiDialogMessage\"></p></div></div>\n";
    echo "      <label id=\"mailrUiDialogInputWrap\" class=\"field\" hidden><span>Value</span><input id=\"mailrUiDialogInput\" type=\"text\" autocomplete=\"off\"></label>\n";
    echo "      <div class=\"app-ui-dialog-actions\"><button class=\"ghost\" id=\"mailrUiDialogCancel\" value=\"cancel\">Cancel</button><button class=\"button\" id=\"mailrUiDialogOk\" value=\"ok\">OK</button></div>\n";
    echo "    </form>\n";
    echo "  </dialog>\n";
    echo "  <script>\n";
    echo "    (() => {\n";
    echo "      const dialog = document.getElementById('mailrUiDialog');\n";
    echo "      if (!dialog) return;\n";
    echo "      const titleEl = document.getElementById('mailrUiDialogTitle');\n";
    echo "      const msgEl = document.getElementById('mailrUiDialogMessage');\n";
    echo "      const inputWrap = document.getElementById('mailrUiDialogInputWrap');\n";
    echo "      const inputEl = document.getElementById('mailrUiDialogInput');\n";
    echo "      const okBtn = document.getElementById('mailrUiDialogOk');\n";
    echo "      const cancelBtn = document.getElementById('mailrUiDialogCancel');\n";
    echo "      let activeResolver = null;\n";
    echo "      const closeWith = (payload) => {\n";
    echo "        if (typeof activeResolver === 'function') { const resolve = activeResolver; activeResolver = null; resolve(payload); }\n";
    echo "        if (dialog.open && dialog.close) dialog.close();\n";
    echo "      };\n";
    echo "      const openDialog = (opts) => new Promise((resolve) => {\n";
    echo "        activeResolver = resolve;\n";
    echo "        titleEl.textContent = opts.title || 'Notice';\n";
    echo "        msgEl.textContent = opts.message || '';\n";
    echo "        okBtn.textContent = opts.okText || 'OK';\n";
    echo "        cancelBtn.textContent = opts.cancelText || 'Cancel';\n";
    echo "        cancelBtn.hidden = !opts.showCancel;\n";
    echo "        inputWrap.hidden = !opts.showInput;\n";
    echo "        if (opts.showInput) {\n";
    echo "          inputEl.value = opts.defaultValue || '';\n";
    echo "          inputEl.placeholder = opts.placeholder || '';\n";
    echo "          setTimeout(() => inputEl.focus(), 0);\n";
    echo "        } else {\n";
    echo "          setTimeout(() => okBtn.focus(), 0);\n";
    echo "        }\n";
    echo "        if (dialog.showModal) dialog.showModal(); else dialog.setAttribute('open', 'open');\n";
    echo "      });\n";
    echo "      okBtn.addEventListener('click', (e) => {\n";
    echo "        e.preventDefault();\n";
    echo "        closeWith({ ok: true, value: inputWrap.hidden ? null : inputEl.value });\n";
    echo "      });\n";
    echo "      cancelBtn.addEventListener('click', (e) => {\n";
    echo "        e.preventDefault();\n";
    echo "        closeWith({ ok: false, value: null });\n";
    echo "      });\n";
    echo "      dialog.addEventListener('cancel', (e) => { e.preventDefault(); closeWith({ ok: false, value: null }); });\n";
    echo "      inputEl.addEventListener('keydown', (e) => {\n";
    echo "        if (e.key === 'Enter') { e.preventDefault(); okBtn.click(); }\n";
    echo "      });\n";
    echo "      window.mailrDialog = {\n";
    echo "        alert(message, options = {}) { return openDialog({ title: options.title || 'Notice', message: String(message ?? ''), okText: options.okText || 'OK', showCancel: false, showInput: false }).then(() => undefined); },\n";
    echo "        confirm(message, options = {}) { return openDialog({ title: options.title || 'Confirm', message: String(message ?? ''), okText: options.okText || 'Continue', cancelText: options.cancelText || 'Cancel', showCancel: true, showInput: false }).then((r) => !!(r && r.ok)); },\n";
    echo "        prompt(message, defaultValue = '', options = {}) { return openDialog({ title: options.title || 'Input', message: String(message ?? ''), okText: options.okText || 'Save', cancelText: options.cancelText || 'Cancel', showCancel: true, showInput: true, defaultValue: String(defaultValue ?? ''), placeholder: options.placeholder || '' }).then((r) => (r && r.ok ? String(r.value ?? '') : null)); }\n";
    echo "      };\n";
    echo "    })();\n";
    echo "  </script>\n";
    echo "  <header class=\"topbar\">\n";
    echo "    <div class=\"topbar-row\">\n";
    echo "      <div class=\"brand\">Mailr</div>\n";
    if ($isAuthed) {
        echo "      <button class=\"nav-toggle\" type=\"button\" aria-expanded=\"false\" aria-controls=\"site-nav\">Menu</button>\n";
    }
    echo "    </div>\n";
    echo "    <nav class=\"nav\" id=\"site-nav\">\n";
    if ($isAuthed) {
        foreach ($pages as $key => $meta) {
            $label = $meta['label'];
            $icon = $meta['icon'];
            $isActive = $key === $activePage ? 'active' : '';
            echo "      <a class=\"nav-link {$isActive}\" href=\"/index.php?page={$key}\"><svg class=\"icon\" aria-hidden=\"true\"><use href=\"#{$icon}\"></use></svg><span>{$label}</span></a>\n";
        }
        echo "      <form method=\"post\" class=\"nav-logout-form\"><button class=\"ghost\" type=\"submit\" name=\"action\" value=\"logout\" data-loading-text=\"Signing out...\">Sign Out</button></form>\n";
    }
    echo "    </nav>\n";
    echo "  </header>\n";
    echo "  <main class=\"container\">\n";
    $flash = flash_get();
    if ($flash) {
        $type = htmlspecialchars((string) $flash['type']);
        $message = htmlspecialchars((string) $flash['message']);
        echo "    <div class=\"flash {$type}\">{$message}</div>\n";
    }
}

function asset_url(string $file): string
{
    $file = ltrim($file, '/');
    $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docRoot !== '' && is_file($docRoot . '/assets/' . $file)) {
        return '/assets/' . $file;
    }
    return '/public/assets/' . $file;
}

function render_footer(): void
{
    $year = date('Y');
    echo "  </main>\n";
    echo "  <footer class=\"footer\">\n";
    echo "    <div>Mailr Campaigns · {$year}</div>\n";
    echo "  </footer>\n";
    echo "  <script>\n";
    echo "    const navToggle = document.querySelector('.nav-toggle');\n";
    echo "    const siteNav = document.getElementById('site-nav');\n";
    echo "    if (navToggle && siteNav) {\n";
    echo "      navToggle.addEventListener('click', () => {\n";
    echo "        const open = siteNav.classList.toggle('is-open');\n";
    echo "        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');\n";
    echo "      });\n";
    echo "      siteNav.querySelectorAll('a').forEach((link) => {\n";
    echo "        link.addEventListener('click', () => {\n";
    echo "          if (window.innerWidth <= 600) {\n";
    echo "            siteNav.classList.remove('is-open');\n";
    echo "            navToggle.setAttribute('aria-expanded', 'false');\n";
    echo "          }\n";
    echo "        });\n";
    echo "      });\n";
    echo "    }\n";
    echo "    document.addEventListener('click', (event) => {\n";
    echo "      document.querySelectorAll('details.info-pop[open]').forEach((el) => {\n";
    echo "        if (!el.contains(event.target)) {\n";
    echo "          el.removeAttribute('open');\n";
    echo "        }\n";
    echo "      });\n";
    echo "    });\n";
    echo "    const mailrCsrfMeta = document.querySelector('meta[name=\"mailr-csrf\"]');\n";
    echo "    const mailrCsrfToken = mailrCsrfMeta ? (mailrCsrfMeta.getAttribute('content') || '') : '';\n";
    echo "    window.mailrCsrfToken = mailrCsrfToken;\n";
    echo "    window.mailrFormState = window.mailrFormState || {};\n";
    echo "    window.mailrFormState.ensureCsrf = (form) => {\n";
    echo "      if (!form || !mailrCsrfToken) return;\n";
    echo "      let csrfInput = form.querySelector('input[name=\"_csrf\"]');\n";
    echo "      if (!csrfInput) {\n";
    echo "        csrfInput = document.createElement('input');\n";
    echo "        csrfInput.type = 'hidden';\n";
    echo "        csrfInput.name = '_csrf';\n";
    echo "        form.appendChild(csrfInput);\n";
    echo "      }\n";
    echo "      csrfInput.value = mailrCsrfToken;\n";
    echo "    };\n";
    echo "    window.mailrFormState.applySubmittingState = (form, explicitSubmitter = null) => {\n";
    echo "      if (!form) return;\n";
    echo "      window.mailrFormState.ensureCsrf(form);\n";
    echo "      const submitter = explicitSubmitter || form.querySelector('button[type=\"submit\"], input[type=\"submit\"]');\n";
    echo "      if (submitter && submitter.name) {\n";
    echo "        form.querySelectorAll('input[data-submitter-mirror=\"1\"]').forEach((node) => node.remove());\n";
    echo "        const mirror = document.createElement('input');\n";
    echo "        mirror.type = 'hidden';\n";
    echo "        mirror.name = submitter.name;\n";
    echo "        mirror.value = submitter.value || '';\n";
    echo "        mirror.setAttribute('data-submitter-mirror', '1');\n";
    echo "        form.appendChild(mirror);\n";
    echo "      }\n";
    echo "      const buttons = form.querySelectorAll('button[type=\"submit\"], input[type=\"submit\"]');\n";
    echo "      buttons.forEach((button) => {\n";
    echo "        button.disabled = true;\n";
    echo "        if (button.tagName.toLowerCase() === 'button') {\n";
    echo "          if (!button.dataset.originalText) {\n";
    echo "            button.dataset.originalText = button.innerHTML;\n";
    echo "          }\n";
    echo "          if (button === submitter) {\n";
    echo "            const label = button.dataset.loadingText || 'Working...';\n";
    echo "            button.innerHTML = label;\n";
    echo "            button.classList.add('is-loading');\n";
    echo "          }\n";
    echo "        }\n";
    echo "      });\n";
    echo "    };\n";
    echo "    document.querySelectorAll('form').forEach((form) => {\n";
    echo "      window.mailrFormState.ensureCsrf(form);\n";
    echo "      form.addEventListener('submit', (event) => {\n";
    echo "        if (event.defaultPrevented) {\n";
    echo "          return;\n";
    echo "        }\n";
    echo "        const submitter = event.submitter || form.querySelector('button[type=\"submit\"], input[type=\"submit\"]');\n";
    echo "        window.mailrFormState.applySubmittingState(form, submitter);\n";
    echo "      });\n";
    echo "    });\n";
    echo "  </script>\n";
    echo "</body>\n";
    echo "</html>\n";
}

function ui_info_popover(string $text, string $title = 'Info'): string
{
    $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return '<details class="info-pop"><summary aria-label="' . $safeTitle . '"><svg class="icon" aria-hidden="true"><use href="#uii-info"></use></svg></summary><div class="info-pop-panel">' . $safeText . '</div></details>';
}
