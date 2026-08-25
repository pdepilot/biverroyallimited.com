<?php
/**
 * Admin reset-password form — opened from email reset link.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/AuthSecurity.php';

AuthSecurity::initSession();

if (!AuthSecurity::isAuthenticated()) {
    AuthSecurity::attemptRememberLogin();
}

if (AuthSecurity::isAuthenticated()) {
    header('Location: admin-dashboard.php');
    exit;
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$valid = $token !== '' ? AuthSecurity::validatePasswordResetToken($token) : null;

$errorMessage   = AuthSecurity::getFlash('error', '');
$successMessage = AuthSecurity::getFlash('success', '');
$csrfToken      = AuthSecurity::generateCsrfToken();
$tokenInvalid   = ($valid === null && $successMessage === '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reset Password — Biver Royalty Homes</title>
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
            <h2>Reset <span>Password</span></h2>
            <p class="subtitle">Choose a new password for your admin account</p>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <p class="auth-links">
                    <a href="admin-login.php">Continue to login</a>
                </p>
            <?php elseif ($tokenInvalid): ?>
                <div class="alert alert-error" role="alert">
                    This reset link is invalid or has expired. Please request a new one.
                </div>
                <p class="auth-links">
                    <a href="admin-forgot-password.php">Request a new reset link</a>
                    <span class="auth-sep">·</span>
                    <a href="admin-login.php">Back to login</a>
                </p>
            <?php else: ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <p class="auth-hint">Resetting password for <strong><?= htmlspecialchars((string) $valid['email'], ENT_QUOTES, 'UTF-8') ?></strong></p>

                <form id="resetForm" method="POST" action="process-password-reset.php" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder=" " required minlength="8">
                        <label for="password">New Password</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password_confirm" name="password_confirm" placeholder=" " required minlength="8">
                        <label for="password_confirm">Confirm Password</label>
                    </div>

                    <button type="submit" class="login-btn" id="resetBtn">Update Password</button>
                </form>

                <p class="auth-links">
                    <a href="admin-login.php">Back to login</a>
                </p>
            <?php endif; ?>
        </div>
        <p class="footer-note">SECURE GATEWAY · PASSWORD RECOVERY</p>
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

        document.getElementById('resetForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('resetBtn');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    </script>
</body>
</html>
