# Biver Royalty Homes — AI Chat Assistant

Production-ready real estate chatbot with intent recognition, FAQ/knowledge base, property search (₦ budgets), in-chat human support, lead CRM, and admin live chat.

## Support platform (conversations, leads, CRM)

After installing the chatbot schema, run:

```
http://localhost/BIVER_ROYAL_ESTATE/sql/install_support_platform.php
```

This creates `support_conversations`, `support_messages`, and `chat_leads`, and adds `properties.listing_status` (available/sold/rented/reserved).

**Admin:** `admin/admin-live-chat.php` — stats, live chat (5s polling), lead stages, agent assignment.

**Human support form:** posts to `admin/api/chatbot-contact.php` (also available as `support_request` on `chatbot-api.php`). No WhatsApp redirects — all support stays in the chat widget.

### Nigerian FAQ/KB expansion

```
http://localhost/BIVER_ROYAL_ESTATE/sql/upgrade_chatbot_nigeria_content.php
```

Adds 25+ FAQs and 20+ knowledge articles (C of O, Governor's Consent, diaspora, PH/Lagos/Abuja, tenancy, fraud prevention, ₦ budget bands, etc.). Safe to re-run — skips duplicates.

## Folder Structure

```
chatbot/
├── chatbot.php              # Widget partial (include on public pages)
├── chatbot-api.php          # REST API (visitor + admin endpoints)
├── chatbot.js               # Frontend chat logic
├── chatbot.css              # Glassmorphism UI styles
├── chatbot-admin.php        # Admin dashboard
├── chatbot-config.php       # Configuration & helpers
├── chatbot-database.sql     # MySQL schema + seed data
├── INSTALL.md               # This file
└── includes/
    ├── ChatbotSecurity.php  # CSRF, rate limiting, sanitization
    ├── ChatbotRepository.php# Database layer
    └── ChatbotEngine.php    # Intent + FAQ + KB engine
```

## Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Apache (XAMPP) with mod_rewrite optional
- Existing `biverroyal_estate` database (see `config/database.php`)

## Installation

### 1. Import the database schema

Using phpMyAdmin or MySQL CLI:

```bash
mysql -u root biverroyal_estate < chatbot/chatbot-database.sql
```

Or in phpMyAdmin: select database `biverroyal_estate` → Import → choose `chatbot-database.sql`.

### 2. Verify database credentials

Edit `config/database.php` if your MySQL user/password differs from XAMPP defaults.

### 3. Enable the widget on your site

Add before `</body>` in `index.php` (and other public pages):

```php
<?php require __DIR__ . '/chatbot/chatbot.php'; ?>
```

### 4. Access the admin dashboard

1. Log in at `/admin/admin-login.php`
2. Open `/chatbot/chatbot-admin.php`

## Features

| Feature | Description |
|---------|-------------|
| Intent engine | Weighted keyword scoring (not hardcoded if-else) |
| FAQ / Knowledge base | Database-driven fallback chain |
| Property integration | Live listings from `properties` table |
| Human escalation | Support tickets + live agent chat |
| Security | PDO prepared statements, CSRF, XSS sanitization, rate limiting |
| Admin | Stats, conversations, assign agents, export, tickets |

## API Endpoints (Visitor)

| Action | Method | Description |
|--------|--------|-------------|
| `csrf` | GET | Get CSRF token |
| `init` | POST | Start/resume session |
| `send` | POST | Send message |
| `poll` | GET/POST | Poll for new messages |
| `escalate` | POST | Connect to human agent |
| `inspection` | POST | Book inspection |
| `mark_read` | POST | Mark messages read |
| `search` | POST | Search conversation |

## API Endpoints (Admin — requires login)

| Action | Description |
|--------|-------------|
| `admin_stats` | Dashboard metrics |
| `admin_conversations` | List conversations |
| `admin_messages` | Get session messages |
| `admin_reply` | Agent reply |
| `admin_assign` | Assign agent |
| `admin_close` | Close chat |
| `admin_export` | Export conversation JSON |
| `admin_tickets` | List support tickets |

## Customization

- **Intents/responses**: Edit rows in `chatbot_intents` and `chatbot_responses`
- **FAQs**: Edit `chatbot_faqs` table
- **Knowledge base**: Edit `chatbot_knowledgebase` table
- **Site contact info**: Uses `config/site-settings.php` automatically
- **UI timing**: Adjust `welcomeDelay1/2/3` in `chatbot-config.php`

## Troubleshooting

**"Database unavailable"** — Run `chatbot-database.sql` against your database.

**CSRF errors** — Clear cookies and refresh. Ensure sessions work (PHP `session.save_path` writable).

**Chat not appearing** — Confirm `chatbot.php` is included and `chatbot.css` / `chatbot.js` load without 404.

**No property results** — Ensure approved properties exist in the `properties` table.

## Security Notes

- Admin endpoints require authenticated admin session + CSRF token
- Visitor messages are rate-limited (30/minute per IP)
- All user input is sanitized before storage
- Use HTTPS in production and set `session.cookie_secure`
