<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';

$activeNav = 'smtp';
$pageTitle = 'SMTP Settings | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-smtp-settings.css';
$csrfToken = AuthSecurity::generateCsrfToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
</head>
<body>
<div class="dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu">
        <ion-icon name="menu-outline"></ion-icon>
      </button>
      <h1 class="page-title">SMTP Settings</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="admin-panel smtp-panel">
        <p class="hint">Configure outbound email for automated notifications and the Email Center. Credentials are stored securely in <code>config/mail.local.php</code> or environment variables.</p>
        <p class="admin-mail-status" id="mailStatusLine">Checking configuration…</p>

        <form id="smtpForm" class="smtp-form">
          <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

          <div class="form-field">
            <label for="mailProvider">Provider</label>
            <select id="mailProvider">
              <option value="gmail">Gmail (Google SMTP)</option>
              <option value="sendgrid">SendGrid</option>
              <option value="brevo">Brevo (Sendinblue)</option>
              <option value="custom">Custom SMTP</option>
            </select>
          </div>

          <div class="form-field">
            <label><input type="checkbox" id="mailUseSmtp" checked> Use SMTP (recommended)</label>
          </div>

          <div class="admin-form-grid-2">
            <div class="form-field">
              <label for="mailHost">SMTP Host</label>
              <input type="text" id="mailHost" placeholder="smtp.gmail.com">
            </div>
            <div class="form-field">
              <label for="mailPort">SMTP Port</label>
              <input type="number" id="mailPort" value="587" min="1" max="65535">
            </div>
          </div>

          <div class="form-field">
            <label for="mailEncryption">SMTP Encryption</label>
            <select id="mailEncryption">
              <option value="tls">TLS (587)</option>
              <option value="ssl">SSL (465)</option>
              <option value="none">None</option>
            </select>
          </div>

          <div class="form-field">
            <label for="mailUsername">SMTP Username</label>
            <input type="text" id="mailUsername" autocomplete="username">
          </div>

          <div class="form-field">
            <label for="mailPassword">SMTP Password</label>
            <input type="password" id="mailPassword" placeholder="Leave blank to keep saved password" autocomplete="new-password">
            <small id="mailPasswordHint" class="admin-hint-block"></small>
          </div>

          <div class="admin-form-grid-2">
            <div class="form-field">
              <label for="mailFromEmail">From Email</label>
              <input type="email" id="mailFromEmail">
            </div>
            <div class="form-field">
              <label for="mailFromName">From Name</label>
              <input type="text" id="mailFromName" value="biverroyaltyhomesltd">
            </div>
          </div>

          <div class="form-field">
            <label for="mailReplyTo">Reply-To Email</label>
            <input type="email" id="mailReplyTo">
          </div>

          <div class="form-field">
            <label for="mailNotifyEmail">Admin notification email (contact enquiries)</label>
            <input type="email" id="mailNotifyEmail">
          </div>

          <div class="form-field">
            <label><input type="checkbox" id="mailNotifyOnContact" checked> Send admin alert on new contact enquiries</label>
          </div>

          <div class="smtp-actions">
            <button type="submit" class="admin-btn-primary"><ion-icon name="save-outline"></ion-icon> Save Settings</button>
            <button type="button" class="admin-btn-outline" id="testBtn"><ion-icon name="paper-plane-outline"></ion-icon> Send Test Email</button>
          </div>

          <div class="form-field">
            <label for="mailTestEmail">Test recipient (optional)</label>
            <input type="email" id="mailTestEmail" placeholder="Defaults to your admin email">
          </div>
        </form>

        <p class="admin-muted-note">Environment variables override saved settings: <code>SMTP_HOST</code>, <code>SMTP_PORT</code>, <code>SMTP_USERNAME</code>, <code>SMTP_PASSWORD</code>, <code>SMTP_ENCRYPTION</code>.</p>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const API = 'api/mail.php';
  const csrf = document.getElementById('csrfToken').value;

  async function api(method, body) {
    const opts = { method, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf, 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(API, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed');
    return data;
  }

  function toast(msg, err) {
    document.querySelector('.admin-toast')?.remove();
    const el = document.createElement('div');
    el.className = 'admin-toast' + (err ? ' error' : '');
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }

  function fillForm(mail) {
    document.getElementById('mailProvider').value = mail.provider || 'gmail';
    document.getElementById('mailUseSmtp').checked = !!mail.useSmtp;
    document.getElementById('mailHost').value = mail.host || '';
    document.getElementById('mailPort').value = mail.port || 587;
    document.getElementById('mailEncryption').value = mail.encryption || 'tls';
    document.getElementById('mailUsername').value = mail.username || '';
    document.getElementById('mailFromEmail').value = mail.fromEmail || '';
    document.getElementById('mailFromName').value = mail.fromName || 'biverroyaltyhomesltd';
    document.getElementById('mailReplyTo').value = mail.replyTo || '';
    document.getElementById('mailNotifyEmail').value = mail.notifyEmail || '';
    document.getElementById('mailNotifyOnContact').checked = !!mail.notifyOnContact;
    document.getElementById('mailPasswordHint').textContent = mail.passwordSet
      ? 'Password is saved. Leave blank to keep it.'
      : 'No password saved yet.';
    document.getElementById('mailStatusLine').textContent = mail.isReady
      ? 'SMTP is configured and ready.'
      : 'SMTP is incomplete — save host, username, and password.';
  }

  async function load() {
    const data = await api('GET');
    fillForm(data.mail || {});
  }

  document.getElementById('smtpForm').addEventListener('submit', async e => {
    e.preventDefault();
    try {
      await api('POST', {
        action: 'save',
        provider: document.getElementById('mailProvider').value,
        useSmtp: document.getElementById('mailUseSmtp').checked ? '1' : '0',
        host: document.getElementById('mailHost').value,
        port: document.getElementById('mailPort').value,
        encryption: document.getElementById('mailEncryption').value,
        username: document.getElementById('mailUsername').value,
        password: document.getElementById('mailPassword').value,
        fromEmail: document.getElementById('mailFromEmail').value,
        fromName: document.getElementById('mailFromName').value,
        replyTo: document.getElementById('mailReplyTo').value,
        notifyEmail: document.getElementById('mailNotifyEmail').value,
        notifyOnContact: document.getElementById('mailNotifyOnContact').checked ? '1' : '0',
      });
      toast('SMTP settings saved');
      load();
    } catch (err) {
      toast(err.message, true);
    }
  });

  document.getElementById('testBtn').addEventListener('click', async () => {
    try {
      const res = await api('POST', {
        action: 'test',
        email: document.getElementById('mailTestEmail').value.trim(),
      });
      toast(res.message);
    } catch (err) {
      toast(err.message, true);
    }
  });

  load();
})();
</script>
</body>
</html>
