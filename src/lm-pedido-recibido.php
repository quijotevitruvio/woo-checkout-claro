<?php
/**
 * Plugin Name: Woo Checkout Claro — Pedido recibido
 * Description: Página de «pedido recibido» (bloques) más clara: una sola caja si envío y facturación coinciden, documento enmascarado, resumen en ficha en el móvil, título propio para transferencias pendientes, cuenta regresiva y un punto de cierre (filtro) que solo se pinta si el pedido está pagado. Solo presentación.
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
 * Mu-plugin: copiar este archivo y la carpeta lm-pedido-recibido/ juntos a wp-content/mu-plugins/.
 * Interruptor: wp option update lm_pedido_recibido_off 1 (deshacer: wp option delete lm_pedido_recibido_off).
 * No toca cobro, estados, plazos, correos ni datos guardados.
 * Ajustes (opción, o constante con el mismo nombre en mayúsculas en wp-config.php): ver README.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'lmpr_on' ) ) {
	return; // Ya cargado (otra copia).
}

const LMPR_VERSION = '1.0.0';
const LMPR_ASSETS  = 'lm-pedido-recibido/';
const LMPR_CSS     = 'lmpr.css';
const LMPR_JS      = 'lmpr.js';

/**
 * Lee un ajuste: constante (nombre en mayúsculas) > opción > valor por defecto.
 * El resultado pasa por el filtro lmpr_ajuste_<nombre>.
 */
function lmpr_ajuste( $nombre, $defecto = '' ) {
	$constante = strtoupper( $nombre );
	$valor     = defined( $constante ) ? constant( $constante ) : get_option( $nombre, $defecto );
	return apply_filters( 'lmpr_ajuste_' . $nombre, $valor );
}

/** Ajuste de texto; si está vacío, devuelve el texto por defecto. */
function lmpr_texto( $nombre, $defecto ) {
	$v = trim( wp_strip_all_tags( (string) lmpr_ajuste( $nombre, '' ) ) );
	return '' !== $v ? $v : $defecto;
}

/** Ajuste con lista separada por comas → array de cadenas no vacías. */
function lmpr_lista( $nombre ) {
	$v = lmpr_ajuste( $nombre, '' );
	if ( is_array( $v ) ) {
		$v = implode( ',', $v );
	}
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) $v ) ) ) );
}

function lmpr_on() {
	return ! get_option( 'lm_pedido_recibido_off' );
}

function lmpr_en_recibido() {
	return function_exists( 'is_order_received_page' ) && is_order_received_page();
}

/** El pedido de esta página, solo si la clave del pedido (?key=) cuadra. */
function lmpr_pedido() {
	$id = absint( get_query_var( 'order-received' ) );
	$p  = $id ? wc_get_order( $id ) : false;
	if ( ! $p instanceof WC_Order ) {
		return false;
	}
	$clave = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $clave || ! hash_equals( $p->get_order_key(), $clave ) ) {
		return false;
	}
	return $p;
}

function lmpr_activo() {
	return lmpr_on() && lmpr_en_recibido();
}

/** Transferencia todavía sin pagar (pasarelas del ajuste lmpr_pasarelas_pendientes). */
function lmpr_transferencia_pendiente( $p ) {
	$pasarelas = lmpr_lista( 'lmpr_pasarelas_pendientes' );
	return $pasarelas && $p instanceof WC_Order
		&& in_array( $p->get_payment_method(), $pasarelas, true )
		&& $p->has_status( array( 'on-hold', 'pending' ) );
}

/* ─────────────────────────────────────────────────────────────────────────
 * 1) Cierre: una caja final (propina, encuesta, café…) solo si está pagado
 * ───────────────────────────────────────────────────────────────────────── */

add_action( 'wp', 'lmpr_reenganchar', 20 );
function lmpr_reenganchar() {
	if ( ! lmpr_activo() ) {
		return;
	}
	/**
	 * Punto para quitar, SOLO en esta petición, lo que otros plugins pintan en
	 * «pedido recibido» (p. ej. remove_action( 'woocommerce_thankyou', ... )).
	 */
	do_action( 'lmpr_reenganchar' );
	add_action( 'woocommerce_thankyou', 'lmpr_cierre_al_final', 999 );
}

function lmpr_cierre_al_final( $pedido_id ) {
	static $hecho = false;
	// Algún plugin puede disparar thankyou dentro del bloque de totales: ahí no
	// se pinta (no sería el final de la página).
	if ( $hecho || doing_filter( 'render_block_woocommerce/order-confirmation-totals-wrapper' ) ) {
		return;
	}
	$p = wc_get_order( $pedido_id );
	if ( ! $p instanceof WC_Order || ! $p->is_paid() ) {
		return; // Pendiente, en espera, fallido…: no se pinta.
	}
	/**
	 * HTML de cierre (una caja de propina, encuesta, etc.). Por defecto vacío.
	 * Lo devuelve el código del propio sitio, que es responsable de escaparlo.
	 *
	 * @param string   $html Vacío.
	 * @param WC_Order $p    Pedido pagado.
	 */
	$html = (string) apply_filters( 'lmpr_cierre_html', '', $p );
	if ( '' === $html ) {
		return;
	}
	$hecho = true;
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML del filtro, responsabilidad de quien lo engancha.
}

/* ─────────────────────────────────────────────────────────────────────────
 * 2) Campos ocultos en «Información adicional» (solo en esta página)
 * ───────────────────────────────────────────────────────────────────────── */

add_filter( 'woocommerce_filter_fields_for_order_confirmation', 'lmpr_campos_ocultos', 20, 4 );
function lmpr_campos_ocultos( $mostrar, $field, $fields = array(), $context = array() ) {
	if ( ! $mostrar || ! is_array( $field ) ) {
		return $mostrar;
	}
	$ocultos = lmpr_lista( 'lmpr_campos_ocultos' );
	if ( ! $ocultos || ! in_array( (string) ( $field['id'] ?? '' ), $ocultos, true ) ) {
		return $mostrar;
	}
	if ( ( $context['caller'] ?? '' ) !== 'AdditionalFields::render_content' || ! lmpr_activo() ) {
		return $mostrar; // Correos y demás: igual que siempre.
	}
	return false;
}

/* ─────────────────────────────────────────────────────────────────────────
 * 3) Documento enmascarado (••••1234) en esta página
 * ───────────────────────────────────────────────────────────────────────── */

function lmpr_enmascarar( $v ) {
	$v = trim( (string) $v );
	$n = function_exists( 'mb_strlen' ) ? mb_strlen( $v ) : strlen( $v );
	if ( $n <= 4 ) {
		return '••••';
	}
	return '••••' . ( function_exists( 'mb_substr' ) ? mb_substr( $v, -4 ) : substr( $v, -4 ) );
}

/** Valor del campo adicional de documento (ajuste lmpr_campo_documento, p. ej. «mi-tienda/documento»). */
function lmpr_documento( $p ) {
	$campo = trim( (string) lmpr_ajuste( 'lmpr_campo_documento', '' ) );
	if ( '' === $campo ) {
		return '';
	}
	$v = '';
	if ( class_exists( '\Automattic\WooCommerce\Blocks\Package' ) ) {
		try {
			$c = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
			$v = (string) $c->get_field_from_object( $campo, $p, 'other' );
		} catch ( Throwable $e ) {
			$v = '';
		}
	}
	if ( '' === $v ) {
		$v = (string) $p->get_meta( '_wc_other/' . $campo );
	}
	return trim( $v );
}

add_filter( 'render_block_woocommerce/order-confirmation-additional-fields', 'lmpr_doc_en_bloque', 20, 1 );
function lmpr_doc_en_bloque( $html ) {
	if ( ! lmpr_activo() || strpos( (string) $html, '<dd>' ) === false ) {
		return $html;
	}
	$p = lmpr_pedido();
	if ( ! $p ) {
		return $html;
	}
	$doc = lmpr_documento( $p );
	if ( '' === $doc ) {
		return $html;
	}
	$crudo    = esc_html( $doc );
	$etiqueta = trim( (string) lmpr_ajuste( 'lmpr_etiqueta_documento', '' ) );
	return preg_replace_callback(
		'#(<dt>[^<]*</dt>)<dd>([^<]*)</dd>#u',
		function ( $m ) use ( $crudo, $doc, $etiqueta ) {
			if ( $m[2] !== $crudo || ( '' !== $etiqueta && stripos( $m[1], $etiqueta ) === false ) ) {
				return $m[0];
			}
			$final = function_exists( 'mb_substr' ) ? mb_substr( $doc, -4 ) : substr( $doc, -4 );
			/* translators: %s: últimos cuatro caracteres del documento. */
			$aria = sprintf( __( 'Termina en %s', 'woo-checkout-claro' ), $final );
			return $m[1] . '<dd><span class="lmpr-doc" aria-label="' . esc_attr( $aria ) . '">' . esc_html( lmpr_enmascarar( $doc ) ) . '</span></dd>';
		},
		$html
	);
}

/* ─────────────────────────────────────────────────────────────────────────
 * 4) Envío = facturación → una sola caja «Envío y facturación»
 * ───────────────────────────────────────────────────────────────────────── */

function lmpr_norm( $s ) {
	$s = wp_strip_all_tags( str_replace( array( '<br/>', '<br />', '<br>' ), "\n", (string) $s ) );
	$s = html_entity_decode( $s, ENT_QUOTES, 'UTF-8' );
	$s = remove_accents( $s );
	$s = function_exists( 'mb_strtolower' ) ? mb_strtolower( $s ) : strtolower( $s );
	$s = str_replace( array( '–', '—', '‒', '−' ), '-', $s );
	$s = preg_replace( '/\s+/u', ' ', $s );
	$s = preg_replace( '/\s*([#\-,.])\s*/u', '$1', $s );
	return trim( $s );
}

/** ¿Mismo teléfono? Solo dígitos; con o sin prefijo de país (uno termina en el otro, mínimo 7 dígitos). */
function lmpr_mismo_tel( $a, $b ) {
	$a = preg_replace( '/\D+/', '', (string) $a );
	$b = preg_replace( '/\D+/', '', (string) $b );
	if ( $a === $b ) {
		return true;
	}
	$corto = strlen( $a ) < strlen( $b ) ? $a : $b;
	$largo = $corto === $a ? $b : $a;
	return strlen( $corto ) >= 7 && substr( $largo, -strlen( $corto ) ) === $corto;
}

/** ¿Lo que se ve en las dos cajas es lo mismo? (dirección formateada + teléfono). */
function lmpr_misma_direccion( $p ) {
	if ( ! $p instanceof WC_Order || ! $p->has_billing_address() || ! $p->has_shipping_address() ) {
		return false;
	}
	$b = lmpr_norm( $p->get_formatted_billing_address() );
	$s = lmpr_norm( $p->get_formatted_shipping_address() );
	if ( '' === $b || $b !== $s ) {
		return false;
	}
	return lmpr_mismo_tel( $p->get_billing_phone(), $p->get_shipping_phone() );
}

function lmpr_una_dir() {
	static $r = null;
	if ( null === $r ) {
		$r = lmpr_activo() && lmpr_misma_direccion( lmpr_pedido() );
	}
	return $r;
}

add_filter( 'render_block_woocommerce/order-confirmation-shipping-wrapper', 'lmpr_dir_envio', 20, 1 );
function lmpr_dir_envio( $html ) {
	if ( trim( (string) $html ) === '' || ! lmpr_una_dir() ) {
		return $html;
	}
	// El título que pinta WooCommerce (traducido) o su versión en español.
	$titulos = array_unique( array( __( 'Shipping address', 'woocommerce' ), 'Dirección de envío' ) );
	$alt     = implode( '|', array_map( 'preg_quote', $titulos, array_fill( 0, count( $titulos ), '#' ) ) );
	$nuevo   = lmpr_texto( 'lmpr_titulo_direccion_unica', __( 'Envío y facturación', 'woo-checkout-claro' ) );
	return preg_replace_callback(
		'#(<h2\b[^>]*>)\s*(?:' . $alt . ')\s*(</h2>)#u',
		function ( $m ) use ( $nuevo ) {
			return $m[1] . esc_html( $nuevo ) . $m[2];
		},
		$html,
		1
	);
}

add_filter( 'render_block_woocommerce/order-confirmation-billing-wrapper', 'lmpr_dir_factura', 20, 1 );
function lmpr_dir_factura( $html ) {
	if ( ! lmpr_una_dir() ) {
		return $html;
	}
	return '';
}

add_filter( 'render_block_core/columns', 'lmpr_dir_columnas', 20, 1 );
function lmpr_dir_columnas( $html ) {
	if ( strpos( (string) $html, 'wc-block-order-confirmation-address-wrapper' ) === false || ! lmpr_una_dir() ) {
		return $html;
	}
	// Quita la columna que quedó vacía para que la caja ocupe el ancho.
	$html = preg_replace( '#<div class="wp-block-column">\s*</div>#', '', $html );
	return preg_replace( '#class="wp-block-columns #', 'class="wp-block-columns lmpr-una-dir ', $html, 1 );
}

/* ─────────────────────────────────────────────────────────────────────────
 * 5) Encabezado de transferencia pendiente
 * ───────────────────────────────────────────────────────────────────────── */

add_filter( 'woocommerce_thankyou_order_received_title', 'lmpr_titulo', 20, 2 );
function lmpr_titulo( $titulo, $p = null ) {
	if ( lmpr_activo() && lmpr_transferencia_pendiente( $p ) ) {
		return esc_html( lmpr_texto( 'lmpr_titulo_pendiente', __( 'Tu pedido está apartado', 'woo-checkout-claro' ) ) );
	}
	return $titulo;
}

add_filter( 'woocommerce_thankyou_order_received_text', 'lmpr_subtitulo', 20, 2 );
function lmpr_subtitulo( $texto, $p = null ) {
	if ( lmpr_activo() && lmpr_transferencia_pendiente( $p ) ) {
		return esc_html( lmpr_texto( 'lmpr_texto_pendiente', __( 'Págalo desde tu banco y te lo enviamos.', 'woo-checkout-claro' ) ) );
	}
	return $texto;
}

/* ─────────────────────────────────────────────────────────────────────────
 * 6) Resumen en ficha en el móvil
 * ───────────────────────────────────────────────────────────────────────── */

/**
 * Marca cada fila por su etiqueta (el correo va a lo ancho en el móvil) y
 * aparta los dos puntos para ocultarlos en la ficha. Mismo texto.
 */
add_filter( 'render_block_woocommerce/order-confirmation-summary', 'lmpr_resumen', 20, 1 );
function lmpr_resumen( $html ) {
	if ( ! lmpr_activo() || strpos( (string) $html, 'summary-list-item__key' ) === false ) {
		return $html;
	}
	$html = preg_replace_callback(
		'#<li class="wc-block-order-confirmation-summary-list-item">(\s*<span class="wc-block-order-confirmation-summary-list-item__key">)([^<]*?)(:?)(</span>)#u',
		function ( $m ) {
			$k     = remove_accents( function_exists( 'mb_strtolower' ) ? mb_strtolower( $m[2] ) : strtolower( $m[2] ) );
			$clase = ( strpos( $k, 'email' ) !== false || strpos( $k, 'correo' ) !== false ) ? ' lmpr-sum--email' : '';
			$dp    = '' !== $m[3] ? '<span class="lmpr-dp">:</span>' : '';
			return '<li class="wc-block-order-confirmation-summary-list-item' . $clase . '">' . $m[1] . $m[2] . $dp . $m[4];
		},
		$html
	);
	return preg_replace( '#class="wc-block-order-confirmation-summary-list"#', 'class="wc-block-order-confirmation-summary-list lmpr-sum"', $html, 1 );
}

/* ─────────────────────────────────────────────────────────────────────────
 * 7) Estilos y cuenta regresiva
 * ───────────────────────────────────────────────────────────────────────── */

add_action( 'wp_enqueue_scripts', 'lmpr_assets', 30 );
function lmpr_assets() {
	if ( ! lmpr_activo() ) {
		return;
	}
	$dir = __DIR__ . '/' . LMPR_ASSETS;
	$ver = static function ( $archivo ) use ( $dir ) {
		$t = @filemtime( $dir . $archivo ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return $t ? LMPR_VERSION . '.' . $t : LMPR_VERSION;
	};
	wp_enqueue_style( 'lm-pedido-recibido', plugins_url( LMPR_ASSETS . LMPR_CSS, __FILE__ ), array(), $ver( LMPR_CSS ) );

	$aviso = sanitize_hex_color( (string) lmpr_ajuste( 'lmpr_color_aviso', '' ) );
	if ( $aviso ) {
		wp_add_inline_style( 'lm-pedido-recibido', ':root{--lmpr-aviso:' . $aviso . ';}' );
	}

	// La cuenta regresiva solo se carga si hay un selector configurado.
	$selector = trim( (string) lmpr_ajuste( 'lmpr_selector_plazo', '' ) );
	if ( '' === $selector ) {
		return;
	}
	$cfg = array(
		'selector' => $selector,
		'texto'    => lmpr_texto( 'lmpr_texto_plazo', __( 'Te guardamos tu pedido por', 'woo-checkout-claro' ) ) . ' ',
		'menos'    => __( 'menos de 1 min', 'woo-checkout-claro' ),
		'horas'    => max( 0, (float) lmpr_ajuste( 'lmpr_horas_aviso', 6 ) ),
	);
	wp_enqueue_script( 'lm-pedido-recibido', plugins_url( LMPR_ASSETS . LMPR_JS, __FILE__ ), array(), $ver( LMPR_JS ), true );
	wp_add_inline_script( 'lm-pedido-recibido', 'window.lmprCfg=' . wp_json_encode( $cfg ) . ';', 'before' );
}
