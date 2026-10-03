/* Woo Checkout Claro — Pedido recibido 1.0.0: cuenta regresiva «Te guardamos
 * tu pedido por 69 h 32 min». Lee el plazo del atributo data-expires (segundos
 * Unix) de los elementos que casan con el ajuste lmpr_selector_plazo; no
 * recalcula nada. Se actualiza una vez por minuto y se marca en color de aviso
 * cuando quedan menos de lmpr_horas_aviso horas. Al vencer devuelve el
 * contenido original del elemento. */
(function () {
	'use strict';
	var cfg = window.lmprCfg || {};
	if (!cfg.selector) { return; }
	var plazos;
	try { plazos = document.querySelectorAll(cfg.selector); } catch (e) { return; }
	var texto = typeof cfg.texto === 'string' ? cfg.texto : '';
	var menos = typeof cfg.menos === 'string' ? cfg.menos : '< 1 min';
	var umbral = (parseFloat(cfg.horas) || 0) * 3600000;
	Array.prototype.forEach.call(plazos, function (p) {
		var vence = parseInt(p.getAttribute('data-expires'), 10) * 1000;
		if (!vence || vence - Date.now() <= 0) { return; }
		var caja = document.createElement('span');
		caja.className = 'lmpr-plazo';
		caja.setAttribute('aria-live', 'polite');
		var fuerte = document.createElement('strong');
		caja.appendChild(document.createTextNode(texto));
		caja.appendChild(fuerte);
		p.appendChild(caja);
		p.classList.add('lmpr-plazo-on');
		function tick() {
			var d = vence - Date.now();
			if (d <= 0) {
				p.classList.remove('lmpr-plazo-on', 'lmpr-rojo');
				if (caja.parentNode) { caja.parentNode.removeChild(caja); }
				return;
			}
			var h = Math.floor(d / 3600000);
			var m = Math.floor((d % 3600000) / 60000);
			var t = h > 0 ? h + ' h ' + m + ' min' : (m > 0 ? m + ' min' : menos);
			if (fuerte.textContent !== t) { fuerte.textContent = t; }
			p.classList.toggle('lmpr-rojo', umbral > 0 && d < umbral);
			setTimeout(tick, (d % 60000) + 300);
		}
		tick();
	});
})();
