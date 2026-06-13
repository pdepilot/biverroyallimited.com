<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Royal Chamber | Admin Settings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
  <link rel="stylesheet" href="../assets/css/admin-setting.css">

  </head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<?php $activeNav = 'settings'; ?>
<div class="dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main">
    <div class="top-bar">
      <div class="admin-header-actions">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-grip-lines"></i></button>
        <h1 class="page-title"><i class="fas fa-sliders-h"></i> Royal Chamber Settings</h1>
      </div>
    </div>

    <div class="settings-grid">
      <!-- Profile Settings -->
      <div class="settings-card">
        <div class="card-header">
          <i class="fas fa-user-crown"></i>
          <h2>Admin Profile</h2>
        </div>
        <div class="profile-preview" id="profilePreview">
          <div class="avatar-large" id="avatarInitial">BR</div>
          <div>
            <strong id="profileName">Loading...</strong><br>
            <span class="email" id="profileEmail"></span>
          </div>
        </div>
        <form id="profileForm">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="fullName" placeholder="Your name" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="email" placeholder="admin@example.com" required>
          </div>
          <div class="form-group">
            <label>Phone (optional)</label>
            <input type="tel" id="phone" placeholder="Contact number">
          </div>
          <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Update Profile</button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="settings-card">
        <div class="card-header">
          <i class="fas fa-lock"></i>
          <h2>Royal Key</h2>
        </div>
        <form id="passwordForm">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" id="currentPassword" placeholder="••••••••" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" id="newPassword" placeholder="At least 8 characters" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" id="confirmPassword" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn-gold"><i class="fas fa-key"></i> Change Password</button>
        </form>
      </div>

      <!-- Email Configuration -->
      <div class="settings-card">
        <div class="card-header">
          <i class="fas fa-envelope"></i>
          <h2>Email Configuration</h2>
        </div>
        <p id="mailStatusText" class="admin-mail-status">Loading mail status...</p>
        <form id="mailSettingsForm">
          <div class="form-group">
            <label for="mailProvider">Email provider</label>
            <select id="mailProvider" name="provider">
              <option value="gmail">Gmail (Google SMTP)</option>
              <option value="sendgrid">SendGrid</option>
              <option value="brevo">Brevo (Sendinblue)</option>
              <option value="custom">Custom SMTP</option>
            </select>
          </div>
          <div class="form-group">
            <label><input type="checkbox" id="mailUseSmtp" checked> Use SMTP (recommended)</label>
          </div>
          <div class="form-group" id="customSmtpFields">
            <label for="mailHost">SMTP host</label>
            <input type="text" id="mailHost" placeholder="smtp.gmail.com">
          </div>
          <div class="admin-form-grid-2">
            <div class="form-group">
              <label for="mailPort">Port</label>
              <input type="number" id="mailPort" value="587" min="1" max="65535">
            </div>
            <div class="form-group">
              <label for="mailEncryption">Encryption</label>
              <select id="mailEncryption">
                <option value="tls">TLS (587)</option>
                <option value="ssl">SSL (465)</option>
                <option value="none">None</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="mailUsername">SMTP username</label>
            <input type="text" id="mailUsername" placeholder="Email or apikey (SendGrid)">
          </div>
          <div class="form-group">
            <label for="mailPassword">SMTP password / API key</label>
            <input type="password" id="mailPassword" placeholder="Leave blank to keep saved password" autocomplete="new-password">
            <small id="mailPasswordHint" class="admin-hint-block"></small>
          </div>
          <div class="form-group">
            <label for="mailFromEmail">From email</label>
            <input type="email" id="mailFromEmail">
          </div>
          <div class="form-group">
            <label for="mailFromName">From name</label>
            <input type="text" id="mailFromName">
          </div>
          <div class="form-group">
            <label for="mailReplyTo">Reply-To email</label>
            <input type="email" id="mailReplyTo">
          </div>
          <div class="form-group">
            <label for="mailNotifyEmail">New inquiry notification email</label>
            <input type="email" id="mailNotifyEmail" placeholder="Where contact form alerts are sent">
          </div>
          <div class="form-group">
            <label><input type="checkbox" id="mailNotifyOnContact" checked> Email me when someone submits the contact form</label>
          </div>
          <div class="admin-btn-row">
            <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Save Email Settings</button>
            <button type="button" class="btn-outline" id="mailTestBtn"><i class="fas fa-paper-plane"></i> Send Test Email</button>
          </div>
          <div class="form-group admin-form-group-spaced">
            <label for="mailTestEmail">Test recipient (optional)</label>
            <input type="email" id="mailTestEmail" placeholder="Defaults to your admin email">
          </div>
        </form>
        <p class="admin-muted-note">
          Gmail: use an App Password. SendGrid: username <code>apikey</code>, password = API key.
          Brevo: use your Brevo SMTP login email and SMTP key.
        </p>
      </div>

      <!-- Site Settings -->
      <div class="settings-card">
        <div class="card-header">
          <i class="fas fa-globe"></i>
          <h2>Site Configuration</h2>
        </div>
        <form id="siteSettingsForm">
          <div class="form-group">
            <label>Site Name</label>
            <input type="text" id="siteName" placeholder="Biver Royalty Homes">
          </div>
          <div class="form-group">
            <label>Contact Email (public)</label>
            <input type="email" id="contactEmail" placeholder="info@domain.com">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" id="contactPhone" placeholder="+234 XXX XXX XXXX">
          </div>
          <div class="form-group">
            <label>Address / Location</label>
            <textarea id="address" rows="2" placeholder="Royal headquarters address"></textarea>
          </div>
          <div class="form-group">
            <label>About Text (short)</label>
            <textarea id="aboutText" rows="3" placeholder="Luxury real estate description..."></textarea>
          </div>
          <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Save Site Settings</button>
        </form>
      </div>

      <!-- Danger Zone -->
      <div class="settings-card danger-zone">
        <div class="card-header">
          <i class="fas fa-exclamation-triangle"></i>
          <h2>Danger Zone</h2>
        </div>
        <p class="admin-danger-zone">These actions are irreversible. Proceed with royal caution.</p>
        <button id="clearCacheBtn" class="btn-outline admin-btn-spaced"><i class="fas fa-database"></i> Clear Dashboard Cache</button>
        <button id="exportDataBtn" class="btn-outline"><i class="fas fa-download"></i> Export All Data (JSON)</button>
        <div class="divider"></div>
        <button id="deleteAccountBtn" class="btn-danger"><i class="fas fa-skull"></i> Delete Admin Account</button>
      </div>
    </div>
  </div>
</div>

<script>
  const API = 'api/settings.php';
  const MAIL_API = 'api/mail.php';

  const providerDefaults = {
    gmail:    { host: 'smtp.gmail.com', port: 587, encryption: 'tls', username: 'biverroyaltyhomes01@gmail.com' },
    sendgrid: { host: 'smtp.sendgrid.net', port: 587, encryption: 'tls', username: 'apikey' },
    brevo:    { host: 'smtp-relay.brevo.com', port: 587, encryption: 'tls', username: '' },
    custom:   { host: '', port: 587, encryption: 'tls', username: '' }
  };

  async function loadMailSettings() {
    try {
      const res = await fetch(MAIL_API);
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to load mail settings');
      const mail = data.mail || {};

      document.getElementById('mailProvider').value = mail.provider || 'gmail';
      document.getElementById('mailUseSmtp').checked = !!mail.useSmtp;
      document.getElementById('mailHost').value = mail.host || '';
      document.getElementById('mailPort').value = mail.port || 587;
      document.getElementById('mailEncryption').value = mail.encryption || 'tls';
      document.getElementById('mailUsername').value = mail.username || '';
      document.getElementById('mailFromEmail').value = mail.fromEmail || '';
      document.getElementById('mailFromName').value = mail.fromName || '';
      document.getElementById('mailReplyTo').value = mail.replyTo || '';
      document.getElementById('mailNotifyEmail').value = mail.notifyEmail || '';
      document.getElementById('mailNotifyOnContact').checked = !!mail.notifyOnContact;
      document.getElementById('mailPasswordHint').textContent = mail.passwordSet
        ? 'A password is saved. Leave blank to keep it.'
        : 'No password saved yet — enter your SMTP password or API key.';

      const status = [];
      status.push(mail.composerInstalled ? 'PHPMailer installed' : 'Run composer install in project root');
      status.push(mail.isReady ? 'SMTP ready' : 'SMTP not fully configured');
      status.push(mail.notifyOnContact ? 'Contact alerts on' : 'Contact alerts off');
      document.getElementById('mailStatusText').textContent = status.join(' · ');
      toggleCustomSmtpFields();
    } catch (err) {
      document.getElementById('mailStatusText').textContent = 'Could not load mail settings: ' + err.message;
    }
  }

  function toggleCustomSmtpFields() {
    const provider = document.getElementById('mailProvider').value;
    const custom = provider === 'custom';
    document.getElementById('customSmtpFields').style.display = custom ? 'block' : 'none';
    if (!custom && providerDefaults[provider]) {
      const preset = providerDefaults[provider];
      if (!document.getElementById('mailHost').dataset.userEdited) {
        document.getElementById('mailHost').value = preset.host;
        document.getElementById('mailPort').value = preset.port;
        document.getElementById('mailEncryption').value = preset.encryption;
      }
      if (preset.username && document.getElementById('mailUsername').value === '') {
        document.getElementById('mailUsername').value = preset.username;
      }
    }
  }

  document.getElementById('mailProvider')?.addEventListener('change', () => {
    document.getElementById('mailHost').dataset.userEdited = '';
    const preset = providerDefaults[document.getElementById('mailProvider').value];
    if (preset) {
      document.getElementById('mailHost').value = preset.host;
      document.getElementById('mailPort').value = preset.port;
      document.getElementById('mailEncryption').value = preset.encryption;
      if (preset.username) document.getElementById('mailUsername').value = preset.username;
    }
    toggleCustomSmtpFields();
  });

  document.getElementById('mailHost')?.addEventListener('input', (e) => {
    e.target.dataset.userEdited = '1';
  });

  document.getElementById('mailSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const res = await fetch(MAIL_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
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
          notifyOnContact: document.getElementById('mailNotifyOnContact').checked ? '1' : '0'
        })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Save failed');
      document.getElementById('mailPassword').value = '';
      showToast(data.message || 'Email settings saved');
      loadMailSettings();
    } catch (err) {
      showToast(err.message, true);
    }
  });

  document.getElementById('mailTestBtn')?.addEventListener('click', async () => {
    try {
      const res = await fetch(MAIL_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'test',
          email: document.getElementById('mailTestEmail').value || document.getElementById('email').value
        })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Test failed');
      showToast(data.message || 'Test email sent');
    } catch (err) {
      showToast(err.message, true);
    }
  });

  async function apiPost(action, payload) {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, ...payload })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
  }

  function showToast(msg, isError = false) {
    let toast = document.querySelector('.toast-msg');
    if (toast) toast.remove();
    toast = document.createElement('div');
    toast.className = 'toast-msg';
    toast.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i> ${msg}`;
    toast.style.borderLeftColor = isError ? '#e74c3c' : '#D4AF37';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  async function loadSettings() {
    try {
      const res = await fetch(API);
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to load settings');

      const profile = data.profile || {};
      const site = data.site || {};

      document.getElementById('fullName').value = profile.name || '';
      document.getElementById('email').value = profile.email || '';
      document.getElementById('phone').value = profile.phone || '';

      const initials = profile.name
        ? profile.name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)
        : 'AD';
      document.getElementById('avatarInitial').innerText = initials;
      document.getElementById('profileName').innerText = profile.name || 'Admin';
      document.getElementById('profileEmail').innerText = profile.email || '';

      document.getElementById('siteName').value = site.siteName || '';
      document.getElementById('contactEmail').value = site.contactEmail || '';
      document.getElementById('contactPhone').value = site.contactPhone || '';
      document.getElementById('address').value = site.address || '';
      document.getElementById('aboutText').value = site.aboutText || '';
    } catch (err) {
      showToast('Could not load settings: ' + err.message, true);
    }
  }

  document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await apiPost('update_profile', {
        name: document.getElementById('fullName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value
      });
      showToast('Profile updated successfully');
      loadSettings();
    } catch (err) {
      showToast(err.message, true);
    }
  });

  document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const newPass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    if (newPass !== confirm) {
      showToast('New passwords do not match', true);
      return;
    }
    try {
      await apiPost('change_password', {
        currentPassword: document.getElementById('currentPassword').value,
        newPassword: newPass
      });
      showToast('Password changed successfully');
      document.getElementById('passwordForm').reset();
    } catch (err) {
      showToast(err.message, true);
    }
  });

  document.getElementById('siteSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await apiPost('save_site', {
        siteName: document.getElementById('siteName').value,
        contactEmail: document.getElementById('contactEmail').value,
        contactPhone: document.getElementById('contactPhone').value,
        address: document.getElementById('address').value,
        aboutText: document.getElementById('aboutText').value
      });
      showToast('Site settings saved');
    } catch (err) {
      showToast(err.message, true);
    }
  });

  document.getElementById('clearCacheBtn')?.addEventListener('click', () => {
    localStorage.removeItem('dashboardCache');
    sessionStorage.removeItem('biver_promo_dismissed_v1');
    showToast('Local cache cleared — refresh homepage to see promo banner again if dismissed');
  });

  document.getElementById('exportDataBtn')?.addEventListener('click', async () => {
    try {
      const res = await fetch(API + '?action=export');
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Export failed');
      const blob = new Blob([JSON.stringify(data.export, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `biver_royalty_export_${Date.now()}.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      showToast('Data exported successfully');
    } catch (err) {
      showToast(err.message, true);
    }
  });

  document.getElementById('deleteAccountBtn')?.addEventListener('click', async () => {
    if (!confirm('This will deactivate your admin account. Continue?')) return;
    const phrase = prompt('Type "DELETE ADMIN" to confirm:');
    if (phrase !== 'DELETE ADMIN') {
      showToast('Account deactivation cancelled');
      return;
    }
    try {
      const data = await apiPost('deactivate_account', { confirm: phrase });
      showToast(data.message || 'Account deactivated');
      setTimeout(() => { window.location.href = data.redirect || 'admin-login.php'; }, 1500);
    } catch (err) {
      showToast(err.message, true);
    }
  });


  loadSettings();
  loadMailSettings();
</script>
</body>
</html>