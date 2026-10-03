# Changelog / Historial de cambios

## 1.0.0 — 2026-10-03

**Español.** Primera versión pública. El código vive en `src/`:
- `lm-checkout-ui` 1.0.0: checkout pulido, traducciones de los bloques, botón fijo «Pagar $ …» en el móvil.
- `lm-pedido-recibido` 1.0.0: página de pedido recibido clara (direcciones fusionadas, documento enmascarado, resumen en ficha, título de transferencia pendiente, cuenta regresiva).
- Todo lo propio de una tienda pasó a ajustes (opción o constante) con valores neutros o vacíos: color de acento, etiqueta del botón, botones flotantes, campo y etiqueta del documento, campos ocultos, pasarelas de transferencia, textos, selector y umbral de la cuenta regresiva, color de aviso.
- Las integraciones con plugins privados se cambiaron por ganchos genéricos: filtro `lmpr_cierre_html` (caja final solo si el pedido está pagado) y acción `lmpr_reenganchar`.
- El CSS ya no apunta a clases de otros plugins ni a imágenes del tema; los recursos ya no llevan la versión en el nombre (`checkout-ui.css/js`, `lmpr.css/js`, versión por `filemtime`).

**English.** First public release. Code lives in `src/`:
- `lm-checkout-ui` 1.0.0: polished checkout, block translations, fixed "Pagar $ …" button on mobile.
- `lm-pedido-recibido` 1.0.0: clear order-received page (merged addresses, masked ID, card summary, pending-transfer title, countdown).
- Everything store-specific became a setting (option or constant) with neutral or empty defaults.
- Private-plugin integrations were replaced by generic hooks: `lmpr_cierre_html` filter (closing box only for paid orders) and `lmpr_reenganchar` action.
- CSS no longer targets other plugins' classes or theme images; assets no longer carry the version in their file names (version via `filemtime`).
