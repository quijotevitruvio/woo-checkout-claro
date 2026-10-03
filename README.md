# Woo Checkout Claro

[English](README.en.md) · Español

Dos mu-plugins que dejan más claras dos pantallas de WooCommerce con **bloques**: el checkout (más corto, textos en español, botón fijo «Pagar $ …» en el móvil) y la página de «pedido recibido» (ordenada, sin repeticiones y con el documento enmascarado).

**Para quién:** tiendas que usan el checkout de bloques y quieren una compra más corta y una confirmación limpia, sin tocar pagos ni campos.

## Qué incluye

Todo el código está en `src/`.

| Pieza | Qué hace |
|---|---|
| `src/lm-checkout-ui.php` + `src/lm-checkout-ui/` | Checkout pulido (solo CSS y textos). Traduce textos en inglés que pinta el JS de los bloques («Ship», «Add coupons», «Create an account with …»). En ≤ 782 px deja la barra del botón pegada abajo, y el botón dice **«Pagar»** con el total (con `registerCheckoutFilters`). |
| `src/lm-pedido-recibido.php` + `src/lm-pedido-recibido/` | «Pedido recibido» más claro: una sola caja «Envío y facturación» si las direcciones son iguales; documento enmascarado (••••1234); resumen en ficha en el móvil; título propio en transferencias pendientes; cuenta regresiva en horas y minutos; y un punto de cierre (filtro `lmpr_cierre_html`) que solo se pinta, al final, si el pedido está pagado. |

Es solo presentación: no toca pagos, estados, plazos, correos, campos ni datos guardados. El botón sigue siendo el nativo de WooCommerce (validación, términos y envío iguales).

## Requisitos

- WordPress 6.2 o superior, PHP 7.4 o superior.
- WooCommerce con **checkout de bloques** y página de «pedido recibido» de bloques.
- Acceso a la carpeta `wp-content/mu-plugins/`.
- Nada más. Las partes que dependen de otra cosa (campo de documento, pasarela de transferencia, cuenta regresiva) vienen apagadas hasta que se configuran.

## Instalación

1. Copia el contenido de `src/` a `wp-content/mu-plugins/`: **cada `.php` con su carpeta**
   (`lm-checkout-ui.php` + `lm-checkout-ui/`, `lm-pedido-recibido.php` + `lm-pedido-recibido/`).
2. Listo: los mu-plugins se cargan solos (no hay botón de activar).
3. Si usas caché de página, vacíala.

Son independientes: puedes copiar solo uno.

## Configuración paso a paso

No hay pantalla de ajustes: se usan opciones de WordPress (con WP-CLI) o constantes en `wp-config.php` con el mismo nombre en mayúsculas (la constante manda sobre la opción). Cada ajuste pasa además por el filtro `lm_cui_ajuste_<nombre>` o `lmpr_ajuste_<nombre>`.

1. Abre el checkout en un teléfono (o ancho ≤ 782 px) con productos en el carrito.
2. Para comparar, añade `?lm_cui=off` o `?lm_cui=on` a la URL del checkout (no cambia nada guardado).
3. Si tu checkout tiene un campo adicional de documento, indica su id: `wp option update lmpr_campo_documento "mi-tienda/documento"`.
4. Si usas transferencia bancaria, indica la pasarela: `wp option update lmpr_pasarelas_pendientes bacs`.

## Ajustes

### Checkout (`lm-checkout-ui.php`)

| Ajuste | Opción / constante | Por defecto | Qué hace |
|---|---|---|---|
| Interruptor | `lm_checkout_ui` | `on` | `off`/`0`/`no`/`false` apaga CSS, botón «Pagar» y traducciones. |
| Prueba por URL | `?lm_cui=on\|off` | — | Cambia solo esa vista, sin guardar nada. |
| Color de acento | `lm_cui_color` / `LM_CUI_COLOR` | vacío (`#1f2937`) | Color hexadecimal de la opción elegida (envío, pago, radios). |
| Etiqueta del botón | `lm_cui_etiqueta` / `LM_CUI_ETIQUETA` | vacío («Pagar», traducible) | Texto que va antes del total. |
| Botones flotantes | `lm_cui_flotantes` / `LM_CUI_FLOTANTES` | vacío | Selectores CSS separados por comas (p. ej. `#mi-chat, .mi-burbuja`) de botones flotantes de otros plugins que deben subir sobre la barra fija del móvil. |
| Traducciones | filtro `lm_cui_traducciones` | `lm-checkout-ui/traducciones.json` | Catálogo `msgid => traducción` que se mezcla con el de WooCommerce (formato de `wp.i18n`). |

### Pedido recibido (`lm-pedido-recibido.php`)

| Ajuste | Opción / constante | Por defecto | Qué hace |
|---|---|---|---|
| Interruptor | `lm_pedido_recibido_off` | sin definir | `1` apaga todo. Borrar la opción lo vuelve a encender. |
| Campo de documento | `lmpr_campo_documento` / `LMPR_CAMPO_DOCUMENTO` | vacío (no enmascara) | Id del campo adicional del checkout (p. ej. `mi-tienda/documento`). Se lee del pedido y su valor se muestra como `••••1234`. |
| Etiqueta del documento | `lmpr_etiqueta_documento` / `LMPR_ETIQUETA_DOCUMENTO` | vacío | Si se da, solo se enmascara la fila cuya etiqueta contiene ese texto (p. ej. `Documento`). Vacío: la fila cuyo valor coincide. |
| Campos ocultos | `lmpr_campos_ocultos` / `LMPR_CAMPOS_OCULTOS` | vacío | Ids de campos adicionales, separados por comas (p. ej. una casilla de boletín), que no se muestran en «Información adicional» de esta página. Los correos no cambian. |
| Pasarelas de transferencia | `lmpr_pasarelas_pendientes` / `LMPR_PASARELAS_PENDIENTES` | vacío (apagado) | Ids de pasarela separados por comas (p. ej. `bacs`). Con el pedido en espera o pendiente cambia título y subtítulo. |
| Título pendiente | `lmpr_titulo_pendiente` | «Tu pedido está apartado» | Título para esas transferencias. |
| Subtítulo pendiente | `lmpr_texto_pendiente` | «Págalo desde tu banco y te lo enviamos.» | Texto bajo el título. |
| Título de dirección única | `lmpr_titulo_direccion_unica` | «Envío y facturación» | Título de la caja cuando envío y facturación coinciden. |
| Selector del plazo | `lmpr_selector_plazo` / `LMPR_SELECTOR_PLAZO` | vacío (sin cuenta regresiva) | Selector CSS de los elementos con `data-expires` (segundos Unix) que pinta tu plugin de pago. Con [woo-pagos-colombia](https://github.com/quijotevitruvio): `.lm-breb__plazo[data-expires]`. |
| Texto del plazo | `lmpr_texto_plazo` | «Te guardamos tu pedido por» | Texto antes de «69 h 32 min». |
| Horas de aviso | `lmpr_horas_aviso` | `6` | Con menos horas que esto, la cifra va en color de aviso (`0` lo apaga). |
| Color de aviso | `lmpr_color_aviso` / `LMPR_COLOR_AVISO` | vacío (`#b91c1c`) | Color hexadecimal de la cuenta regresiva urgente. |

Los demás colores son variables CSS (`--lmpr-tinta`, `--lmpr-mut`, `--lmpr-linea`, `--lmpr-fondo`…) que el tema puede sobrescribir.

### Ganchos para desarrolladores

| Gancho | Tipo | Para qué |
|---|---|---|
| `lmpr_cierre_html` | filtro `( string $html, WC_Order $pedido )` | HTML de una caja de cierre (propina, encuesta…). Se pinta una sola vez, al final, y **solo si el pedido está pagado**. Quien lo devuelve debe escaparlo. |
| `lmpr_reenganchar` | acción | Se dispara solo en «pedido recibido» (con el plugin activo), para quitar en esa petición lo que otros plugins pintan ahí (`remove_action( 'woocommerce_thankyou', … )`). |
| `lm_cui_traducciones` | filtro `( array $catalogo )` | Añadir o cambiar traducciones del JS de los bloques. |

Ejemplos con WP-CLI: `wp option update lm_checkout_ui off` · `wp option update lm_cui_color "#0f766e"` · `wp option update lm_pedido_recibido_off 1` · `wp option delete lm_pedido_recibido_off`.

## Deshacer / desinstalar

Pon el interruptor en apagado, o borra los archivos y carpetas de `mu-plugins/`. No se guarda nada en la base de datos aparte de las opciones de la tabla de arriba, que puedes borrar (`wp option delete <nombre>`).

## Preguntas frecuentes

- **¿Cambia mi botón de pagar?** Es el mismo botón; solo cambia su etiqueta a «Pagar» con el total. Si algo falla, vuelve a «Realizar el pedido».
- **¿Toca mis pasarelas?** No. Solo estilos y textos.
- **¿Por qué no aparece en Plugins?** Los mu-plugins van aparte: Plugins → «Must-Use».
- **¿Cómo cambio un color?** Con `lm_cui_color` y `lmpr_color_aviso`, o sobrescribiendo las variables CSS desde el tema.
- **¿Puedo usar solo uno de los dos?** Sí, son independientes (copia el que quieras con su carpeta).

## Limitaciones conocidas

- Los textos por defecto están en español (pasan por `__()` con el dominio `woo-checkout-claro`, y los principales tienen ajuste).
- El modo oscuro del resumen solo se activa con `html[data-theme="dark"]` o `.is-dark-theme` (no con `prefers-color-scheme`, para no oscurecer temas claros).
- El CSS del checkout se inyecta en línea (`<style>` en `wp_head`) y el JS va pegado a `wc-blocks-checkout`: si tu sitio usa una CSP estricta, permítelos.
- Las reglas dependen de las clases de los bloques de WooCommerce; una versión nueva de WooCommerce puede cambiarlas.

## Licencia

GPL-2.0 o posterior. Ver `LICENSE`.

## Autor y apoyo

**Andrés Valencia Tobón** · [GitHub](https://github.com/quijotevitruvio) · [LinkedIn](https://www.linkedin.com/in/andr%C3%A9s-valencia-tob%C3%B3n/)
Desarrollado para librosmedellin.com.
Apoya el proyecto: https://paypal.me/bibliotecologo
