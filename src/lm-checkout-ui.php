<?php
/**
 * Plugin Name: Woo Checkout Claro — Checkout UI
 * Description: Checkout de bloques más corto: traduce textos en inglés del JS de los bloques, ordena el formulario y, en el móvil, deja el botón nativo pegado abajo con «Pagar $ total». Solo presentación.
 * Version: 1.0.0
 * Author: Andrés Valencia Tobón
 * Author URI: https://github.com/quijotevitruvio
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-checkout-claro
 * Requires Plugins: woocommerce
 *
 * LinkedIn: https://www.linkedin.com/in/andr%C3%A9s-valencia-tob%C3%B3n/
 * Donate link: https://paypal.me/bibliotecologo
 *
 * Desarrollado para librosmedellin.com.
 *
 * Mu-plugin: copiar este archivo y la carpeta lm-checkout-ui/ juntos a wp-content/mu-plugins/.
 * Interruptor: wp option update lm_checkout_ui off (deshacer: wp option delete lm_checkout_ui).
 * Prueba sin guardar nada: añadir ?lm_cui=off o ?lm_cui=on a la URL del checkout.
 * Ajustes (opción, o constante con el mismo nombre en mayúsculas en wp-config.php): ver README.
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'LM_CUI_VERSION' ) ) {
	return; // Ya cargado (otra copia).
}
define( 'LM_CUI_VERSION', '1.0.0' );
define( 'LM_CUI_DIR', __DIR__ . '/lm-checkout-ui/' );

/**
 * Lee un ajuste: constante (nombre en mayúsculas) > opción > valor por defecto.
 * El resultado pasa por el filtro lm_cui_ajuste_<nombre>.
 */
function lm_cui_ajuste( string $nombre, $defecto = '' ) {
	$constante = strtoupper( $nombre );
	$valor     = defined( $constante ) ? constant( $constante ) : get_option( $nombre, $defecto );
	return apply_filters( 'lm_cui_ajuste_' . $nombre, $valor );
}

/** ¿Interruptor encendido? Opción + parámetro de prueba ?lm_cui=on|off (solo cosmético). */
function lm_cui_encendido(): bool {
	if ( isset( $_GET['lm_cui'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- solo cambia la presentación.
		$q = sanitize_key( wp_unslash( $_GET['lm_cui'] ) );
		if ( 'off' === $q ) {
			return false;
		}
		if ( 'on' === $q ) {
			return true;
		}
	}
	$v = strtolower( (string) get_option( 'lm_checkout_ui', 'on' ) );
	return ! in_array( $v, array( 'off', '0', 'no', 'false' ), true );
}

/** ¿Página de finalizar compra (sin pedido recibido ni pagar pedido)? */
function lm_cui_es_checkout(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return false;
	}
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return false;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
		return false;
	}
	return true;
}

function lm_cui_activo(): bool {
	return lm_cui_es_checkout() && lm_cui_encendido();
}

/** Traducciones solo en checkout y carrito. */
function lm_cui_traduce_aqui(): bool {
	if ( ! lm_cui_encendido() || is_admin() ) {
		return false;
	}
	if ( lm_cui_es_checkout() ) {
		return true;
	}
	return function_exists( 'is_cart' ) && is_cart();
}

/** Lee un archivo propio de la carpeta; vacío si no está. */
function lm_cui_archivo( string $nombre ): string {
	$ruta = LM_CUI_DIR . $nombre;
	if ( ! is_readable( $ruta ) ) {
		return '';
	}
	return (string) file_get_contents( $ruta ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Catálogo msgid => array de traducciones (formato de wp.i18n).
 * Se amplía o cambia con el filtro lm_cui_traducciones.
 */
function lm_cui_traducciones(): array {
	static $t = null;
	if ( null !== $t ) {
		return $t;
	}
	$t   = array();
	$raw = json_decode( lm_cui_archivo( 'traducciones.json' ), true );
	$raw = apply_filters( 'lm_cui_traducciones', is_array( $raw ) ? $raw : array() );
	if ( is_array( $raw ) ) {
		foreach ( $raw as $k => $v ) {
			if ( is_string( $k ) && '' !== $k ) {
				$t[ $k ] = is_array( $v ) ? array_values( array_map( 'strval', $v ) ) : array( (string) $v );
			}
		}
	}
	return $t;
}

/* ── 1. Traducciones del JS de WooCommerce ─────────────────────────────── */
add_filter( 'load_script_translations', 'lm_cui_json_traducciones', 20, 4 );
function lm_cui_json_traducciones( $json, $file, $handle, $domain ) {
	if ( 'woocommerce' !== $domain || ! is_string( $json ) || '' === $json || ! lm_cui_traduce_aqui() ) {
		return $json;
	}
	$extra = lm_cui_traducciones();
	$data  = json_decode( $json, true );
	if ( ! $extra || ! is_array( $data ) || empty( $data['locale_data'] ) || ! is_array( $data['locale_data'] ) ) {
		return $json;
	}
	$clave = isset( $data['locale_data'][ $domain ] ) ? $domain : ( isset( $data['locale_data']['messages'] ) ? 'messages' : '' );
	if ( '' === $clave || ! is_array( $data['locale_data'][ $clave ] ) ) {
		return $json;
	}
	foreach ( $extra as $k => $v ) {
		$data['locale_data'][ $clave ][ $k ] = $v;
	}
	$nuevo = wp_json_encode( $data );
	return $nuevo ? $nuevo : $json;
}

add_action( 'wp_enqueue_scripts', 'lm_cui_scripts', 100 );
function lm_cui_scripts() {
	if ( ! lm_cui_traduce_aqui() ) {
		return;
	}
	$extra = lm_cui_traducciones();
	if ( $extra ) {
		// Respaldo para chunks sin archivo de traducción: se fusiona con lo de
		// WooCommerce (setLocaleData mezcla, no reemplaza).
		wp_add_inline_script(
			'wp-i18n',
			'try{wp.i18n.setLocaleData(' . wp_json_encode( $extra ) . ',"woocommerce");}catch(e){}',
			'after'
		);
	}
	if ( ! lm_cui_activo() ) {
		return;
	}
	$js = lm_cui_archivo( 'checkout-ui.js' );
	if ( '' !== $js ) {
		$etiqueta = trim( wp_strip_all_tags( (string) lm_cui_ajuste( 'lm_cui_etiqueta', '' ) ) );
		if ( '' === $etiqueta ) {
			$etiqueta = __( 'Pagar', 'woo-checkout-claro' );
		}
		$cfg = array( 'etiqueta' => $etiqueta );
		// Detrás de wc-blocks-checkout y antes del checkout: el filtro existe
		// desde el primer pintado.
		wp_add_inline_script( 'wc-blocks-checkout', 'window.lmCuiCfg=' . wp_json_encode( $cfg ) . ";\n" . $js, 'after' );
	}
}

/* ── 2. CSS y clase en <body> ──────────────────────────────────────────── */
add_filter( 'body_class', 'lm_cui_body_class' );
function lm_cui_body_class( $clases ) {
	if ( lm_cui_activo() ) {
		$clases[] = 'lm-cui';
	}
	return $clases;
}

/** Selectores CSS (de otros plugins) que flotan abajo y deben subir sobre la barra fija del móvil. */
function lm_cui_selectores_flotantes(): string {
	$raw = (string) lm_cui_ajuste( 'lm_cui_flotantes', '' );
	// Solo caracteres de selector: nada de llaves, punto y coma, «<», «@», «\» ni «/».
	$raw   = (string) preg_replace( '/[^A-Za-z0-9_\-#.,:\s\[\]="\'()>+~*]/', '', $raw );
	$lista = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
	$out   = array();
	foreach ( $lista as $sel ) {
		$out[] = 'body.lm-cui ' . $sel;
	}
	return implode( ',', $out );
}

add_action( 'wp_head', 'lm_cui_css', 99 );
function lm_cui_css() {
	if ( ! lm_cui_activo() ) {
		return;
	}
	$css = lm_cui_archivo( 'checkout-ui.css' );
	if ( '' === $css ) {
		return;
	}
	$extra  = '';
	$acento = sanitize_hex_color( (string) lm_cui_ajuste( 'lm_cui_color', '' ) );
	if ( $acento ) {
		$extra .= 'body.lm-cui{--lmcui-acento:' . $acento . ';}';
	}
	$flot = lm_cui_selectores_flotantes();
	if ( '' !== $flot ) {
		$extra .= '@media (max-width:782px){' . $flot . '{margin-bottom:calc(var(--lm-cui-barra,70px) + 8px)!important;}}';
	}
	echo '<style id="lm-checkout-ui-css">' . $css . $extra . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- archivo propio y valores saneados arriba.
}

/* ── 3. Estilo nativo «con precio» en el bloque del botón ─────────────── */
add_filter( 'render_block_woocommerce/checkout-actions-block', 'lm_cui_boton_con_precio', 99 );
function lm_cui_boton_con_precio( $html ) {
	if ( ! is_string( $html ) || ! lm_cui_activo() ) {
		return $html;
	}
	if ( false !== strpos( $html, 'is-style-without-price' ) ) {
		return preg_replace( '/\bis-style-without-price\b/', 'is-style-with-price', $html, 1 );
	}
	if ( false === strpos( $html, 'is-style-with-price' ) ) {
		return preg_replace( '/class="wp-block-woocommerce-checkout-actions-block/', 'class="wp-block-woocommerce-checkout-actions-block is-style-with-price', $html, 1 );
	}
	return $html;
}
