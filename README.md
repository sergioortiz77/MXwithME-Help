# MXwithME-Help
Código parcial del proyecto **MX with ME** para revisión colaborativa de la comunidad.

## MX with ME — MU Plugins (Privacidad, Roles y Sincronización)

> **Autoría y asistencia:**  
> Documento redactado por **Sergio Ortiz**, con asistencia de una **IA**.  
> Sergio se encuentra en fase de aprendizaje en desarrollo WordPress, por lo que se solicita colaboración de la comunidad para validación, revisión y mejoras técnicas.  
> Toda la descripción se basa en el **código real** incluido en este repositorio. No se han hecho suposiciones.

---

## 🧭 Propósito del repositorio

Estos archivos representan la **arquitectura modular de MU-Plugins** del proyecto MX with ME, desarrollados para manejar:

- Integración entre **Proyectos (CPT)**, **Grupos BuddyPress** y **Foros bbPress**.  
- Aplicación de reglas de **privacidad irreversible** (público, privado, oculto).  
- Sincronización entre estados **ACF ↔ BuddyPress ↔ bbPress**.  
- Control de **roles, capacidades y seguridad**.  
- Interfaz y endpoints **AJAX** de soporte.

Actualmente, **solo `mxwm-pmp-installer.php`** se encuentra activo en el servidor de producción.  
Los demás archivos se publican aquí para revisión técnica y colaboración.

---

## 📂 Archivos incluidos

### 1. `mxwm-pmp-installer.php` ✅ (activo en producción)

Define roles y capacidades base para el ecosistema MX with ME.  
Se ejecuta como *must-use plugin*, asegurando que los permisos PMP y de administración estén disponibles incluso si cambia el tema.

- Crea capacidades como `override_group_privacy_lock` y `manage_mxwm_privacy`.  
- Requiere estar en `wp-content/mu-plugins/` (no en el tema).  
- Provee la base de permisos que otros módulos usan para validar acciones administrativas.

---

### 2. `mxwm-buddypress-privacy.php`

**Núcleo de privacidad y sincronización.**  
Interconecta ACF (frontend), BuddyPress (grupos) y bbPress (foros) para mantener coherencia de estados.  
Implementa además la regla de **irreversibilidad de privacidad**.

#### Funciones destacadas

- `mxwm_get_group_id_for_project()` sincroniza metadatos antiguos (`_mxwm_grupo_id`) y nuevos (`mxwm_group_id`).  
- `mxwm_privacy_normalize_group_status()` normaliza los valores recibidos (público, privado, oculto).  
- `mxwm_privacy_apply_group_status()` aplica cambios al grupo respetando irreversibilidad.  
- `mxwm_privacy_sync_forum_status()` refleja los cambios de privacidad en el foro asociado.  
- Bloquea en `groups_group_before_save` cualquier intento de revertir un grupo privado/oculto a público.  
- Limpia cachés y sincroniza tras `groups_group_after_save`.

📎 **Depende de:** `mxwm-helpers.php`, `mxwm-transitions.php`, `mxwm-security.php`, `mxwm-ajax.php`.

---

### 3. `mxwm-buddypress-privacy (antiguo).php`

Versión **v1.2** previa del módulo de privacidad.  
Implementa la misma lógica central (sincronización ACF ↔ BuddyPress ↔ bbPress), pero organizada como clase `MXWM_Privacy`.

Incluye:

- Inicialización modular con carga condicional de dependencias (`helpers`, `capabilities`, `transitions`, `security`, `ajax`, `ui`).  
- Registro automático de capacidades (`MXWM_Capabilities::activate`).  
- Sistema de *fallback* si BuddyPress no está activo.

> Esta versión se conserva por motivos de referencia y comparación estructural.

---

### 4. `mxwm-capabilities.php`

Registra capacidades especiales para administradores y roles definidos en Paid Memberships Pro.

```php
$role->add_cap('override_group_privacy_lock');
$role->add_cap('manage_mxwm_privacy');
```

Se usa como dependencia del núcleo de privacidad y puede integrarse con el instalador PMP.

---

### 5. `mxwm-helpers.php`

Conjunto de utilidades reutilizables:

- `MXWM_Helpers::log($msg, $level)` — logging unificado (nivel info/debug).  
- `MXWM_Helpers::sanitize_privacy_value($val)` — normaliza valores de privacidad (soporta equivalentes español/inglés).  

**Dependencia base** para casi todos los módulos (AJAX, Transitions, Security).

---

### 6. `mxwm-transitions.php`

Maneja la **sincronización de estado de grupos** y la **irreversibilidad**.

#### Funciones principales

- `mxwm_sync_group_status($group_id, $nuevo_estado)`  
  - Aplica transiciones seguras (`public` ↔ `private` ↔ `hidden`).  
  - Previene reversión de privados a públicos sin permiso `override_group_privacy_lock`.  
  - Marca grupos como *irreversibles* (`mxwm_irreversible`, `mxwm_fecha_irreversible`).  
  - Registra logs detallados de transición.  
- `mxwm_refresh_bp_group_cache()`  
  - Limpia la caché de grupo tras actualización.  
  - Hookeado en `groups_group_after_save` y `groups_settings_updated`.

📎 **Depende de:** `MXWM_Helpers`.

---

### 7. `mxwm-security.php`

Filtra acceso a grupos y foros según el estado de privacidad.

#### Componentes

- `bp_user_can_view_group` → bloquea contenido de grupos privados u ocultos a usuarios no miembros.  
- `template_redirect` → redirige fuera de foros privados a usuarios no autenticados o no miembros.  
- Genera mensajes de error con `bp_core_add_message()` y registra en log (`MXWM_Helpers::log`).  

**Protección verificada** contra acceso directo a foros privados.

---

### 8. `mxwm-ajax.php`

Define endpoint seguro para **confirmar privacidad irreversible** de grupos vía AJAX.

```php
add_action('wp_ajax_mxwm_confirmar_privacidad_grupo', 'mxwm_ajax_confirmar_privacidad_grupo');
```

#### Flujo

1. Verifica nonce (`mxwm_privacidad_nonce`).  
2. Actualiza metadatos `mxwm_irreversible` y `mxwm_fecha_irreversible`.  
3. Devuelve respuesta JSON con `wp_send_json_success()` o `wp_send_json_error()`.  

📎 **Depende de:** `MXWM_Helpers` y del script `mxwm-ui.php`.

---

### 9. `mxwm-ui.php`

Carga y localiza los recursos JavaScript del sistema de privacidad.

```js
mxwm_privacidad = {
  ajaxurl: admin_url('admin-ajax.php'),
  nonce: wp_create_nonce('mxwm_privacidad_nonce'),
  group_id: bp_get_current_group_id()
}
```

Facilita comunicación AJAX segura con `mxwm-ajax.php`.

---

## 🔗 Relaciones y dependencias

```text
mxwm-pmp-installer.php → mxwm-capabilities.php
                            ↓
                mxwm-buddypress-privacy.php
                     ↙        ↓         ↘
        mxwm-helpers.php   mxwm-transitions.php   mxwm-security.php
                            ↓             ↓
                    mxwm-ajax.php ←→ mxwm-ui.php
```

- **Helpers** provee utilidades básicas.  
- **Capabilities** establece permisos base.  
- **Privacy** coordina la lógica general.  
- **Transitions** aplica cambios irreversibles.  
- **Security** controla el acceso.  
- **UI** + **AJAX** manejan la interfaz y las confirmaciones.

---

## 🧩 Contexto técnico del issue

El sistema buscaba que un cambio de privacidad en proyectos y grupos:

- Se sincronizara entre ACF ↔ BuddyPress ↔ bbPress.  
- Bloqueara la reversión de privado/oculto → público.  
- Registrara marca temporal de irreversibilidad.  

A pesar de múltiples iteraciones, la sincronización y la irreversibilidad no se lograron consistentemente, por lo que se **suspendió la implementación activa** y se documenta aquí para análisis y colaboración.

---

## 🚀 Solicitud a la comunidad

Se solicita ayuda para:

1. Revisar y refactorizar la lógica de sincronización `mxwm-buddypress-privacy.php` / `mxwm-transitions.php`.  
2. Mejorar el sistema de irreversibilidad y asegurar su cumplimiento a nivel BuddyPress core.  
3. Sugerir mecanismos de testing automatizado (WP-CLI, PHPUnit).  
4. Validar la seguridad AJAX y la localización UI.  
5. Proponer optimizaciones de rendimiento o cacheo seguro.

---

## ⚙️ Instalación y pruebas

1. Copiar todos los archivos en `wp-content/mu-plugins/`.  
2. Confirmar que **BuddyPress**, **bbPress**, **ACF** y **Paid Memberships Pro** estén activos.  
3. Activar `WP_DEBUG` y monitorear `wp-content/debug.log`.  
4. Probar transiciones de privacidad y creación de grupo desde la interfaz de proyecto.

---

## 🔐 Buenas prácticas

- No incluir datos personales, SQL dumps ni `wp-config.php`.  
- Validar `nonce` y `capabilities` en toda interacción AJAX.  
- Sanitizar entradas (`sanitize_text_field`) y escapar salidas (`esc_html`, `esc_attr`).  
- Mantener copias de *staging* antes de aplicar en producción.

---

## 🧾 Créditos

Proyecto **MX with ME** — creado por **Sergio Ortiz**.  
Redacción técnica y documentación asistida por IA (modelo GPT-5).  
Código y descripciones verificadas directamente desde los archivos fuente.
