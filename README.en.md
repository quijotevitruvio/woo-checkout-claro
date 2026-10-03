# Woo Checkout Claro

English · [Español](README.md)

Two mu-plugins that make two **block-based** WooCommerce screens clearer: the checkout (shorter, Spanish texts, a fixed "Pagar $ …" (Pay) button on mobile) and the "order received" page (tidy, no repeated parts, masked ID number).

**Who it is for:** stores using the block checkout that want a shorter purchase and a clean confirmation page, without touching payments or fields.

## What is included

All code lives in `src/`.

| Piece | What it does |
|---|---|
| `src/lm-checkout-ui.php` + `src/lm-checkout-ui/` | Polished checkout (CSS and texts only). Translates English strings that the block JS draws ("Ship", "Add coupons", "Create an account with …"). At ≤ 782 px the button bar sticks to the bottom and the button says **"Pagar"** with the total (via `registerCheckoutFilters`). |
| `src/lm-pedido-recibido.php` + `src/lm-pedido-recibido/` | Clearer "order received": one "Shipping and billing" box when both addresses match; masked ID number (••••1234); card-style summary on mobile; custom title for pending bank transfers; countdown in hours and minutes; and a closing slot (`lmpr_cierre_html` filter) printed once, at the end, only if the order is paid. |

Presentation only: it does not touch payments, statuses, deadlines, emails, fields or saved data. The button is still WooCommerce's native one (same validation, terms and submit).

## Requirements

- WordPress 6.2+, PHP 7.4+.
- WooCommerce with the **block checkout** and a block-based "order received" page.
- Access to the `wp-content/mu-plugins/` folder.
- Nothing else. Parts that depend on something else (ID field, transfer gateway, countdown) stay off until configured.

## Installation

1. Copy the contents of `src/` to `wp-content/mu-plugins/`: **each `.php` with its folder**
   (`lm-checkout-ui.php` + `lm-checkout-ui/`, `lm-pedido-recibido.php` + `lm-pedido-recibido/`).
2. Done: mu-plugins load by themselves (there is no activate button).
3. If you use a page cache, purge it.

They are independent: you can copy just one.

## Setup, step by step

There is no settings screen: use WordPress options (with WP-CLI) or constants in `wp-config.php` with the same name in uppercase (the constant wins over the option). Every setting also goes through the `lm_cui_ajuste_<name>` or `lmpr_ajuste_<name>` filter.

1. Open the checkout on a phone (or at ≤ 782 px wide) with products in the cart.
2. To compare, add `?lm_cui=off` or `?lm_cui=on` to the checkout URL (nothing is saved).
3. If your checkout has an additional ID-number field, set its id: `wp option update lmpr_campo_documento "my-store/id-number"`.
4. If you take bank transfers, set the gateway: `wp option update lmpr_pasarelas_pendientes bacs`.

## Settings

### Checkout (`lm-checkout-ui.php`)

| Setting | Option / constant | Default | What it does |
|---|---|---|---|
| Switch | `lm_checkout_ui` | `on` | `off`/`0`/`no`/`false` turns off CSS, the "Pagar" button and translations. |
| URL test | `?lm_cui=on\|off` | — | Changes only that view, nothing saved. |
| Accent color | `lm_cui_color` / `LM_CUI_COLOR` | empty (`#1f2937`) | Hex color for the selected option (shipping, payment, radios). |
| Button label | `lm_cui_etiqueta` / `LM_CUI_ETIQUETA` | empty ("Pagar", translatable) | Text shown before the total. |
| Floating buttons | `lm_cui_flotantes` / `LM_CUI_FLOTANTES` | empty | Comma-separated CSS selectors (e.g. `#my-chat, .my-bubble`) of other plugins' floating buttons that must move above the sticky mobile bar. |
| Translations | `lm_cui_traducciones` filter | `lm-checkout-ui/traducciones.json` | `msgid => translation` catalog merged into WooCommerce's (`wp.i18n` format). |

### Order received (`lm-pedido-recibido.php`)

| Setting | Option / constant | Default | What it does |
|---|---|---|---|
| Switch | `lm_pedido_recibido_off` | unset | `1` turns everything off. Deleting the option turns it back on. |
| ID field | `lmpr_campo_documento` / `LMPR_CAMPO_DOCUMENTO` | empty (no masking) | Id of the additional checkout field (e.g. `my-store/id-number`). Its value is shown as `••••1234`. |
| ID label | `lmpr_etiqueta_documento` / `LMPR_ETIQUETA_DOCUMENTO` | empty | If set, only the row whose label contains this text is masked. Empty: the row whose value matches. |
| Hidden fields | `lmpr_campos_ocultos` / `LMPR_CAMPOS_OCULTOS` | empty | Comma-separated additional field ids (e.g. a newsletter checkbox) hidden from "Additional information" on this page only. Emails do not change. |
| Transfer gateways | `lmpr_pasarelas_pendientes` / `LMPR_PASARELAS_PENDIENTES` | empty (off) | Comma-separated gateway ids (e.g. `bacs`). For on-hold or pending orders, title and subtitle change. |
| Pending title | `lmpr_titulo_pendiente` | "Tu pedido está apartado" | Title for those transfers. |
| Pending subtitle | `lmpr_texto_pendiente` | "Págalo desde tu banco y te lo enviamos." | Text under the title. |
| Single-address title | `lmpr_titulo_direccion_unica` | "Envío y facturación" | Box title when shipping and billing match. |
| Deadline selector | `lmpr_selector_plazo` / `LMPR_SELECTOR_PLAZO` | empty (no countdown) | CSS selector of elements carrying `data-expires` (Unix seconds) printed by your payment plugin. With [woo-pagos-colombia](https://github.com/quijotevitruvio): `.lm-breb__plazo[data-expires]`. |
| Deadline text | `lmpr_texto_plazo` | "Te guardamos tu pedido por" | Text before "69 h 32 min". |
| Warning hours | `lmpr_horas_aviso` | `6` | Below this many hours the figure uses the warning color (`0` disables). |
| Warning color | `lmpr_color_aviso` / `LMPR_COLOR_AVISO` | empty (`#b91c1c`) | Hex color for the urgent countdown. |

Other colors are CSS variables (`--lmpr-tinta`, `--lmpr-mut`, `--lmpr-linea`, `--lmpr-fondo`…) your theme can override.

### Developer hooks

| Hook | Type | Purpose |
|---|---|---|
| `lmpr_cierre_html` | filter `( string $html, WC_Order $order )` | HTML for a closing box (tip jar, survey…). Printed once, at the end, **only if the order is paid**. Whoever returns it must escape it. |
| `lmpr_reenganchar` | action | Fires only on "order received" (plugin on), to remove for that request what other plugins print there (`remove_action( 'woocommerce_thankyou', … )`). |
| `lm_cui_traducciones` | filter `( array $catalog )` | Add or change translations for the block JS. |

WP-CLI examples: `wp option update lm_checkout_ui off` · `wp option update lm_cui_color "#0f766e"` · `wp option update lm_pedido_recibido_off 1` · `wp option delete lm_pedido_recibido_off`.

## Undo / uninstall

Set the switch to off, or delete the files and folders from `mu-plugins/`. Nothing is stored in the database except the options above, which you can delete (`wp option delete <name>`).

## FAQ

- **Does it replace my pay button?** It is the same button; only its label becomes "Pagar" with the total. If something fails, it goes back to "Place order".
- **Does it touch my gateways?** No. Styles and texts only.
- **Why is it not in the Plugins list?** Mu-plugins are listed apart: Plugins → "Must-Use".
- **How do I change a color?** With `lm_cui_color` and `lmpr_color_aviso`, or by overriding the CSS variables from your theme.
- **Can I use only one of the two?** Yes, they are independent (copy the one you want with its folder).

## Known limitations

- Default texts are Spanish (they go through `__()` with the `woo-checkout-claro` text domain, and the main ones have a setting).
- Dark mode for the summary only applies with `html[data-theme="dark"]` or `.is-dark-theme` (not `prefers-color-scheme`, so light themes are not darkened).
- The checkout CSS is printed inline (`<style>` in `wp_head`) and the JS is attached to `wc-blocks-checkout`: allow them if your site uses a strict CSP.
- Rules depend on WooCommerce block class names; a new WooCommerce version may change them.

## License

GPL-2.0-or-later. See `LICENSE`.

## Author and support

**Andrés Valencia Tobón** · [GitHub](https://github.com/quijotevitruvio) · [LinkedIn](https://www.linkedin.com/in/andr%C3%A9s-valencia-tob%C3%B3n/)
Developed for librosmedellin.com.
Support the project: https://paypal.me/bibliotecologo
