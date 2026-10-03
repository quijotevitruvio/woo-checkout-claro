# Seguridad / Security

Revisión de la versión 1.0.0 (2026-10-03). Superficie: solo presentación en el front (filtros de bloques, `body_class`, CSS/JS en línea). No hay AJAX, REST, formularios, subida de archivos, SQL propio ni `unserialize`.

| # | Punto | Archivo:línea | Gravedad | Estado / recomendación |
|---|---|---|---|---|
| 1 | Acceso al pedido en «pedido recibido»: solo con la clave del pedido (`?key=`) comparada con `hash_equals` | `src/lm-pedido-recibido.php` (`lmpr_pedido`) | — | Correcto: no hay IDOR. |
| 2 | El filtro `lmpr_cierre_html` imprime el HTML que le devuelva otro código sin escapar (para permitir formularios o botones) | `src/lm-pedido-recibido.php` (`lmpr_cierre_al_final`) | Informativa | Por diseño; documentado: quien engancha el filtro debe escapar su HTML. |
| 3 | Ajustes que acaban en CSS en línea (`lm_cui_color`, `lmpr_color_aviso`, `lm_cui_flotantes`) | `src/lm-checkout-ui.php` (`lm_cui_css`, `lm_cui_selectores_flotantes`), `src/lm-pedido-recibido.php` (`lmpr_assets`) | Baja | Arreglado: colores con `sanitize_hex_color`; selectores filtrados a caracteres de selector (sin `{ } ; < @ \ /`). Además, solo un administrador o `wp-config.php` puede cambiarlos. |
| 4 | Textos de ajustes en la página (títulos, etiqueta, plazo) | ambos archivos | Baja | Arreglado: `wp_strip_all_tags` + `esc_html`; al JS van por `wp_json_encode`. |
| 5 | `?lm_cui=on\|off` cambia la vista sin nonce | `src/lm-checkout-ui.php` (`lm_cui_encendido`) | Informativa | Solo cosmético; no guarda nada. Con caché de página, la variante puede cachearse por URL. |
| 6 | CSS y JS en línea | `src/lm-checkout-ui.php` | Informativa | Con una CSP estricta hay que permitir estilos y scripts en línea (o un nonce). |

Producción: sin hallazgos nuevos en estas piezas.
