# System Email Customizer

HumHub module for customizing all system-generated emails with rich text editing, branded headers and footers, call-to-action buttons, and per-email enable toggles.

**Version:** 1.0.0  
**Owner/Maintainer:** [D Cube Consulting](https://dcubeconsulting.co.uk)  
**Copyright:** Copyright (c) 2026 D Cube Consulting. All rights reserved.

## Overview

This module intercepts HumHub’s outgoing system mail and applies your custom templates when enabled. It includes:

- **Per-email templates** — subject, header, body, and footer for each supported system email
- **Rich text editing** — HumHub ProseMirror editor for email body content
- **Branded layout** — configurable header/footer background and font colours
- **Call-to-action buttons** — insert styled email buttons via shortcodes or the admin UI
- **Variable placeholders** — dynamic values such as `{app_name}`, `{registration_url}`, `{display_name}`
- **Live preview** — preview rendered HTML before sending
- **Per-template toggle** — enable or disable custom templates individually
- **Access control** — dedicated permission plus optional authorised group

## Supported email categories

- User emails (registration invite, password recovery, email/username change, 2FA, etc.)
- Admin emails (registration approval, decline, messages)
- Activity emails (mail summary)
- Notification emails (resolved from HumHub notification classes)

> **Note:** Per-space join-question emails are intentionally excluded; those are handled by the Space Join Questions module.

## Requirements

- HumHub **1.18.0** or higher
- PHP version compatible with your HumHub installation
- Working HumHub mail transport (SMTP or equivalent)

## Installation

1. Clone or copy this repository into your HumHub modules directory:

   ```bash
   git clone https://github.com/gauravsdcube/system-email-custom-templates.git protected/modules/system-email-customizer
   ```

   The folder name must be `system-email-customizer` (matches the module ID in `module.json`).

2. Set ownership for your web server user, for example:

   ```bash
   chown -R www-data:www-data protected/modules/system-email-customizer
   ```

3. Enable the module in **Administration → Modules → System Email Customizer**.

4. Run migrations:

   ```bash
   php protected/yii migrate/up --migrationPath=@system-email-customizer/migrations
   ```

5. Flush the cache:

   ```bash
   php protected/yii cache/flush-all
   ```

## Configuration

After installation, open **Administration → System Emails** (or **Administration → Settings → System Email Customizer**).

### Permissions

Grant access using either or both of:

1. **Manage system emails** permission — assign under **Administration → Groups → Permissions**
2. **Authorised group** — configure under module Settings so members of a named group can manage templates

Administrators always have full access.

### Editing a template

1. Open the email list and choose a template to edit.
2. Customise subject, header, body, and footer.
3. Use the variable sidebar to copy placeholders into your content.
4. Toggle **Use custom template** to activate the template for live emails.
5. Click **Preview** to see the rendered output.

## Button shortcodes

Insert a styled call-to-action button using this syntax:

```
{button:Label|URL}
```

Examples:

```
{button:Continue|{registration_url}}
{button:Reset password|{password_reset_url}}
{button:Sign in|https://example.com/login}
```

You can also use the **Insert button** panel in the template editor.

Buttons are rendered as table-based HTML compatible with common email clients and use your HumHub theme’s primary colours via `MailStyleHelper`.

## Variables

Each email type exposes relevant placeholders (shown in the editor sidebar). Common variables include:

| Variable | Description |
|----------|-------------|
| `{app_name}` | HumHub installation name |
| `{display_name}` | Recipient display name |
| `{registration_url}` | Registration link (invite emails) |
| `{password_reset_url}` | Password reset link |
| `{originator_name}` | User who triggered the email |
| `{space_name}` | Related space name (where applicable) |

## How it works

- `MailInterceptor` replaces the application mailer and intercepts `compose()` calls.
- `EmailDefinitionRegistry` maps HumHub mail views and notifications to template keys.
- `TemplateProcessor` replaces variables, converts rich text to email HTML, and renders button shortcodes.
- `Events::onBeforeRequest` bootstraps the module early so registration and other pre-request emails are intercepted.

## Production checklist

- [ ] Module enabled and migrations applied
- [ ] Mail transport tested (send a real invite or password recovery email)
- [ ] Custom templates previewed for each critical email type
- [ ] WAF/reverse-proxy rules allow POST to `/system-email-customizer/admin/` if using rich HTML content
- [ ] Permissions assigned to the correct admin group(s)
- [ ] Database and files backed up before upgrades

## Versioning

Semantic versioning: `MAJOR.MINOR.PATCH`

- **PATCH** — bug fixes and safe improvements
- **MINOR** — backward-compatible features
- **MAJOR** — breaking changes

### Changelog

#### 1.0.0

- Initial release
- Rich text email templates with header, body, and footer
- Button shortcode support with email-client-safe rendering
- Per-email enable toggles and live preview
- Group and permission-based access control

## License

Proprietary — Copyright (c) 2026 D Cube Consulting. All rights reserved.

## Support

For maintenance, customisation, or support:

- **Web:** [https://dcubeconsulting.co.uk](https://dcubeconsulting.co.uk)
- **Email:** info@dcubeconsulting.co.uk
