=== Woo Checkout Claro ===
Contributors: quijotevitruvio
Donate link: https://paypal.me/bibliotecologo
Tags: woocommerce, checkout, blocks, mobile, order received
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A shorter WooCommerce block checkout with a fixed "Pagar $ ..." button on mobile, and a clean "order received" page.

== Description ==

Two must-use plugins for the WooCommerce block checkout. Presentation only: they do not touch payments, statuses, fields or saved data.

* **Checkout UI:** shorter checkout (CSS and texts), Spanish translations for strings the block JS draws, and on screens up to 782 px a sticky bar with the native place-order button relabeled "Pagar" with the total (uses `registerCheckoutFilters`). If anything fails it falls back to the default label.
* **Order received:** one "Shipping and billing" box when both addresses match, masked ID number, card-style summary on mobile, countdown in hours and minutes, a "reserved" title for pending bank transfers, and a closing slot (`lmpr_cierre_html` filter) printed only for paid orders.

Store-specific parts are settings (WordPress options or wp-config constants) with neutral or empty defaults. See README.md for the full table.

Default texts are in Spanish. Developed for librosmedellin.com.

== Installation ==

1. Copy the contents of `src/` to `/wp-content/mu-plugins/`: `lm-checkout-ui.php` with `lm-checkout-ui/`, and `lm-pedido-recibido.php` with `lm-pedido-recibido/`.
2. Purge your page cache.
3. Test on a phone-width screen. Use `?lm_cui=off` or `?lm_cui=on` on the checkout URL to compare.

Switches: `wp option update lm_checkout_ui off` and `wp option update lm_pedido_recibido_off 1`.

== Frequently Asked Questions ==

= Is it a normal plugin? =
No. It is a must-use plugin: there is no activate button. Find it under Plugins > Must-Use.

= Does it replace the place-order button? =
No. It is the same native button with a different label and the total.

= How do I turn it off? =
Set the option to off, or delete the files from `mu-plugins/`.

= Does it need other plugins? =
No. ID masking, pending-transfer titles and the countdown stay off until you set their options.

== Changelog ==

= 1.0.0 =
* First public release: lm-checkout-ui 1.0.0 and lm-pedido-recibido 1.0.0, store-specific values moved to settings, generic hooks instead of private integrations.
