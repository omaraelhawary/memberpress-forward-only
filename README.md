# MemberPress Forward-Only Access

WordPress plugin that restricts members from viewing content published **before** their membership start date. Content published on or after that date remains available according to your existing MemberPress rules.

**Requires:** [MemberPress](https://memberpress.com/) (active).

**License:** GPL-2.0-or-later

## Installation

1. Copy the `memberpress-forward-only` folder into `wp-content/plugins/`.
2. Activate **MemberPress Forward-Only Access** in the WordPress Plugins screen.
3. MemberPress must already be installed and active.

If MemberPress is missing, site administrators see an admin notice and the plugin does not load its features.

## How it works

- The plugin compares each protected post’s **publish date** (`post_date`) to the member’s **earliest qualifying active transaction** from MemberPress (same basis as “signup” for this feature).
- If the member’s start is **after** the post was published, access is blocked for that content (full-page and partial `[mepr-active]` flows, where MemberPress applies).
- Users with the `manage_options` capability are not blocked.

## Settings

In the WordPress admin: **MemberPress → Forward-Only Access**.

| Area | Description |
|------|-------------|
| **Rules** | Optional list of MemberPress **rule post IDs**. Leave empty to apply forward-only logic to **all** rules that protect the content. When set, only those rules participate. |
| **Message** | HTML shown when access is blocked. Use `%signup_date%` for the formatted membership start date (uses the site date format). |
| **Exclusions** | Optional post type names and category **slugs** (comma- or line-separated) to **never** apply forward-only checks to. |

If `MEPR_FORWARD_ONLY_RULE_IDS` or `MEPR_FORWARD_ONLY_MESSAGE` is defined in `wp-config.php`, that constant overrides the matching setting from the admin (the settings screen shows a notice when overrides are active).

### wp-config.php overrides

```php
// Optional: restrict enforcement to these rule IDs (array of integers).
define( 'MEPR_FORWARD_ONLY_RULE_IDS', array( 123, 456 ) );

// Optional: fixed HTML message (string). Replaces the Message field.
define( 'MEPR_FORWARD_ONLY_MESSAGE', '<div class="mepr-forward-only-notice"><p>…</p></div>' );
```

## Filters

Other code can add exclusions in addition to the settings screen:

- `mepr_forward_only_exclude_post_types` — array of post type names to exclude.
- `mepr_forward_only_exclude_categories` — array of category slugs to exclude (posts only).

The plugin merges saved settings into these filters at priority `5`.

## Shortcode: `[mepr_forward_link]`

Use on dashboards or landing pages to show inner content only when the linked “archive” page’s publish date is **on or before** the member’s signup (relative to optional membership scope).

```
[mepr_forward_link page_id="101"]
<a href="/april-2026">April 2026 Archive</a>
[/mepr_forward_link]
```

Attributes:

- `page_id` (required) — ID of the page whose publish date is compared.
- `membership_ids` (optional) — Comma-separated membership product IDs; limits the signup date calculation to those products.

Logged-out visitors get no output from the shortcode. Users with `manage_options` always see the inner content.

## PHP helpers

These functions mirror the internal API for use in themes or other plugins:

- `mepr_forward_only_get_signup_ts( int $user_id, ?array $product_ids = null ): ?int`
- `mepr_forward_only_rule_applies( int $rule_id ): bool`
- `mepr_forward_only_is_excluded( WP_Post $post ): bool`

## Development

Run unit tests (requires [Composer](https://getcomposer.org/)):

```bash
composer install
vendor/bin/phpunit -c phpunit.xml.dist
```

## Uninstall

Deactivating leaves options in the database. Uninstalling the plugin removes the `mepr_forward_only_settings` option.
