<?php
/**
 * Secure admin login page — session check, CSRF token, lockout display.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/AuthSecurity.php';

AuthSecurity::initSession();

if (AuthSecurity::isAuthenticated()) {
    header('Location: admin-dashboard.php');
    exit;
}

$ip = AuthSecurity::getClientIp();
AuthSecurity::purgeExpiredLockouts();

$lockout = AuthSecurity::getActiveLockout($ip);

$errorMessage   = AuthSecurity::getFlash('error', '');
$showWarning    = (bool) AuthSecurity::getFlash('warning', false);
$lockoutMessage = AuthSecurity::getFlash('lockout', '');
$isManualReview = (bool) AuthSecurity::getFlash('lockout_manual', false);
$loggedOut      = isset($_GET['logged_out']);

if ($lockout !== null && $lockoutMessage === '') {
    if ((int) $lockout['requires_manual_review'] === 1) {
        $lockoutMessage = 'Your IP address requires manual administrator review before login is permitted.';
        $isManualReview = true;
    } else {
        $remaining = AuthSecurity::formatRemainingTime($lockout['expires_at']);
        $lockoutMessage = 'Access temporarily restricted. Time remaining: ' . $remaining . '.';
    }
}

$csrfToken = AuthSecurity::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Royal Gate — Biver Royalty Homes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="../assets/css/admin-login.css">
</head>
<body>
    <canvas id="particleCanvas"></canvas>

    <div class="login-container" id="loginContainer">
        <div class="seal-wrapper">
            <div class="seal-ring"></div>
            <div class="seal-core">B</div>
        </div>

        <div class="login-card">
            <h2>Biver <span>Royalty</span> Homes</h2>
            <p class="subtitle">Royal Command Center</p>

            <?php if ($loggedOut): ?>
                <div class="alert alert-success">You have been logged out securely.</div>
            <?php endif; ?>

            <?php if ($lockoutMessage !== ''): ?>
                <div class="alert alert-lockout" role="alert">
                    <strong>Access Restricted</strong><br>
                    <?= htmlspecialchars($lockoutMessage, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($isManualReview): ?>
                        <br><small>Contact your system administrator to restore access.</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($showWarning): ?>
                <div class="alert security-warning" id="securityWarning" role="alert">
                    Warning: You have 1 login attempt remaining. One more failed attempt will result in a temporary 72-hour restriction.
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== '' && !$showWarning): ?>
                <div class="alert alert-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php elseif ($errorMessage !== '' && $showWarning): ?>
                <div class="alert alert-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php $formDisabled = ($lockout !== null); ?>
            <form id="loginForm" method="POST" action="authenticate.php" autocomplete="off" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder=" "
                           required <?= $formDisabled ? 'disabled' : '' ?>
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <label for="email">Email Address</label>
                </div>
                <div class="input-group">
                    <input type="password" id="password" name="password" placeholder=" "
                           required <?= $formDisabled ? 'disabled' : '' ?>>
                    <label for="password">Password</label>
                </div>
                <button type="submit" class="login-btn" id="loginBtn" <?= $formDisabled ? 'disabled' : '' ?>>
                    <?= $formDisabled ? 'Access Restricted' : 'Unlock Vault' ?>
                </button>
            </form>
        </div>
        <p class="footer-note">SECURE GATEWAY · PHP SESSION AUTHENTICATION</p>
    </div>

    <script>
        const canvas = document.getElementById('particleCanvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        class Particle {
            constructor() { this.reset(); }
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 1;
                this.speedY = Math.random() * 0.3 + 0.1;
                this.opacity = Math.random() * 0.5 + 0.2;
            }
            update() {
                this.y -= this.speedY;
                if (this.y < -10) {
                    this.y = canvas.height + 10;
                    this.x = Math.random() * canvas.width;
                }
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(212, 175, 55, ${this.opacity})`;
                ctx.fill();
            }
        }

        function initParticles(count = 40) {
            particles = [];
            for (let i = 0; i < count; i++) particles.push(new Particle());
        }
        initParticles();

        function animateParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => { p.update(); p.draw(); });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();

        <?php if ($showWarning): ?>
        (function playSecurityAlert() {
            try {
                const ctxAudio = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctxAudio.createOscillator();
                const gain = ctxAudio.createGain();
                osc.connect(gain);
                gain.connect(ctxAudio.destination);
                osc.frequency.value = 880;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.15, ctxAudio.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctxAudio.currentTime + 0.5);
                osc.start(ctxAudio.currentTime);
                osc.stop(ctxAudio.currentTime + 0.5);
                setTimeout(() => {
                    const osc2 = ctxAudio.createOscillator();
                    const gain2 = ctxAudio.createGain();
                    osc2.connect(gain2);
                    gain2.connect(ctxAudio.destination);
                    osc2.frequency.value = 660;
                    osc2.type = 'sine';
                    gain2.gain.setValueAtTime(0.12, ctxAudio.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.01, ctxAudio.currentTime + 0.4);
                    osc2.start(ctxAudio.currentTime);
                    osc2.stop(ctxAudio.currentTime + 0.4);
                }, 600);
            } catch (e) { /* Audio not supported or blocked */ }
        })();
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
        document.getElementById('loginContainer').classList.add('shake');
        <?php endif; ?>

        document.getElementById('loginForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Authenticating...';
            }
        });
    </script>
</body>
</html>
