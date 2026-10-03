/* Woo Checkout Claro — Checkout UI 1.0.0. Lo imprime lm-checkout-ui.php SOLO
 * en finalizar compra y SOLO si el interruptor lm_checkout_ui no está en 'off'.
 * Va pegado DESPUÉS de wc-blocks-checkout y ANTES del checkout de bloques, así
 * el filtro ya existe cuando el botón se pinta por primera vez.
 *
 * No crea botones ni hace clic por nadie: el botón «Pagar $ …» es el MISMO
 * botón nativo de WooCommerce (validación, términos y envío intactos).
 *  - El texto («Pagar» o el ajuste lm_cui_etiqueta) lo pone el filtro
 *    placeOrderButtonLabel.
 *  - El precio lo pinta WooCommerce (estilo «con precio» del bloque, activado
 *    en PHP) con el total de su propio almacén: es el mismo número que el
 *    «Total» del resumen y se actualiza solo con cupón, envío o recogida.
 *  - Si algo falla, el filtro devuelve el texto de siempre y el CSS esconde el
 *    precio (solo se muestra con body.lm-cui-pagar).
 */
(function () {
	'use strict';
	var body = document.body;
	if (!body || !body.classList.contains('lm-cui')) { return; }
	body.classList.add('lm-cui-js');
	var cfg = window.lmCuiCfg || {};
	var etiqueta = (typeof cfg.etiqueta === 'string' && cfg.etiqueta) ? cfg.etiqueta : 'Pagar';

	/* 1) Etiqueta + total ------------------------------------------------ */
	function totalValido() {
		try {
			var t = window.wp && window.wp.data && window.wp.data.select('wc/store/cart').getCartTotals();
			if (!t || t.total_price === undefined || t.total_price === null || t.total_price === '') { return false; }
			var n = parseInt(t.total_price, 10);
			return isFinite(n) && n > 0;
		} catch (e) { return false; }
	}
	function bloqueConPrecio() {
		return !!document.querySelector('.wp-block-woocommerce-checkout-actions-block.is-style-with-price');
	}
	var filtros = {
		placeOrderButtonLabel: function (valor) {
			try {
				if (typeof valor === 'string' && body.classList.contains('lm-cui') && bloqueConPrecio() && totalValido()) {
					body.classList.add('lm-cui-pagar');
					return etiqueta;
				}
			} catch (e) { /* cae al texto de siempre */ }
			body.classList.remove('lm-cui-pagar');
			return valor;
		}
	};
	try {
		var bc = window.wc && window.wc.blocksCheckout;
		if (bc && typeof bc.registerCheckoutFilters === 'function') {
			bc.registerCheckoutFilters('lm-checkout-ui', filtros);
			// WooCommerce guarda en caché lo que devuelve el filtro y en el
			// primer pintado el carrito aún sale en 0. Cuando el total pasa a
			// válido (o deja de serlo) se vuelve a registrar: eso vacía su
			// caché y el siguiente repintado del bloque ya lo toma.
			var valido = totalValido();
			if (window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function') {
				window.wp.data.subscribe(function () {
					var v = totalValido();
					if (v !== valido) {
						valido = v;
						try { bc.registerCheckoutFilters('lm-checkout-ui', filtros); } catch (e) { body.classList.remove('lm-cui-pagar'); }
					}
				}, 'wc/store/cart');
			}
		}
	} catch (e) { body.classList.remove('lm-cui-pagar'); }

	/* 2) Alto real de la barra (para subir los botones flotantes y para el
	 *    scroll-padding que evita que tape el campo enfocado) -------------- */
	var ultimoAlto = 0, ultimoMargen = '';
	function medirBarra() {
		var raiz = document.documentElement;
		var a = document.querySelector('.wp-block-woocommerce-checkout .wc-block-checkout__actions');
		if (!a) { return; }
		var h = Math.ceil(a.getBoundingClientRect().height);
		if (h > 0 && h < 400 && h !== ultimoAlto) {
			ultimoAlto = h;
			raiz.style.setProperty('--lm-cui-barra', h + 'px');
		}
		// Margen real a cada lado del formulario: la barra llega de borde a
		// borde sin adivinar el relleno del tema.
		var f = a.parentElement && a.parentElement.getBoundingClientRect();
		if (f && f.width > 0) {
			var gi = Math.max(0, Math.round(f.left)), gd = Math.max(0, Math.round(raiz.clientWidth - f.right));
			var m = gi + '/' + gd;
			if (m !== ultimoMargen && gi < 80 && gd < 80) {
				ultimoMargen = m;
				raiz.style.setProperty('--lm-cui-gi', gi + 'px');
				raiz.style.setProperty('--lm-cui-gd', gd + 'px');
			}
		}
	}
	setInterval(function () { if (!document.hidden) { medirBarra(); } }, 1200);
	window.addEventListener('resize', medirBarra);
	window.addEventListener('load', medirBarra);

	/* 3) Con el teclado abierto la barra vuelve a su sitio (no flota encima
	 *    del campo ni del teclado). Sin visualViewport: basta el foco. ------- */
	var vv = window.visualViewport;
	function esCampo(el) {
		if (!el || !el.tagName || !el.closest || !el.closest('.wp-block-woocommerce-checkout')) { return false; }
		if (el.tagName === 'TEXTAREA') { return true; }
		if (el.tagName !== 'INPUT') { return false; }
		return !/^(checkbox|radio|button|submit|hidden|range|color|file)$/i.test(el.type || '');
	}
	function revisarTeclado() {
		var enfocado = esCampo(document.activeElement);
		var teclado = vv ? (vv.height < window.innerHeight * 0.85) : true;
		body.classList.toggle('lm-cui-escribiendo', enfocado && teclado);
	}
	document.addEventListener('focusin', function () { setTimeout(revisarTeclado, 0); });
	document.addEventListener('focusout', function () { setTimeout(revisarTeclado, 60); });
	if (vv) { vv.addEventListener('resize', revisarTeclado); }
})();
