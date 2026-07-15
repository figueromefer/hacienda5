# Plan CTO — Lote de cambios Hacienda Cinco

**Fecha:** julio de 2026  
**Repositorio:** `figueromefer/hacienda5`  
**Stack confirmado:** Laravel 13, PHP 8.3+, Blade, Tailwind, Alpine, Spatie Permission, DomPDF.  
**Objetivo:** implementar el lote completo de mejoras sin romper permisos, información histórica, cálculos financieros ni flujos existentes.

---

## 1. Principios y decisiones arquitectónicas

### 1.1 Implementación por fases

Codex debe trabajar en una rama separada y completar las fases en orden. Cada fase debe terminar con pruebas y un commit independiente. No se acepta un único cambio masivo difícil de revisar o revertir.

### 1.2 Dinero

Todos los campos monetarios compartirán una solución reutilizable.

- Presentación esperada: símbolo `$`, coma para miles y punto para decimales, por ejemplo `$ 125,430.50`.
- Persistencia esperada: `125430.50`, sin símbolo ni separadores.
- La normalización debe realizarse antes de validar en servidor, aunque también exista formateo con Alpine/JavaScript.
- Los cálculos finales siempre se repetirán en servidor; los totales del navegador son solo informativos.
- Crear un componente Blade/Alpine o helper reutilizable, no copiar scripts distintos en eventos, cotizaciones, contratos y movimientos.
- Mantener dos decimales y evitar inconsistencias por flotantes.

### 1.3 Estados y traducciones

- Los nombres internos actuales se conservan en inglés para no romper datos, consultas o permisos.
- Las etiquetas en interfaz se centralizan en accessors, enums o helpers.
- Las clases visuales de estados también deben centralizarse cuando se reutilicen.
- Los roles de Spatie no se renombran; solo se muestran con la primera letra mayúscula.

### 1.4 Cliente, evento y cotización

Cualquier `event_id` enviado junto con un `client_id` debe pertenecer a ese cliente. La interfaz filtrada no es suficiente: `store` y `update` deben rechazar combinaciones manipuladas.

### 1.5 Fuente de verdad financiera del evento

Se elimina la dependencia funcional de `events.total_amount` como monto capturado manualmente.

Definiciones nuevas:

- **Costo del evento:** suma de `quotations.total` de cotizaciones asociadas al evento cuyo estado sea `approved`.
- **Ingresos cobrados:** suma de movimientos tipo `income`, estado `paid`, asociados al evento.
- **Pendiente por cobrar:** `max(costo_evento - ingresos_cobrados, 0)`.
- **Saldo a favor / sobrepago:** `max(ingresos_cobrados - costo_evento, 0)`. No es requisito mostrarlo en esta entrega, pero el servicio puede calcularlo para no ocultar el dato.
- Las cotizaciones en borrador, enviadas, rechazadas o vencidas no afectan el costo.
- Los gastos no reducen el pendiente por cobrar.
- Los movimientos cancelados no afectan ninguna suma.
- Una cotización aprobada sin evento no afecta ningún evento.

La lógica debe vivir en un servicio reutilizable, no en una vista.

### 1.6 Ciclo de vida de movimientos

- Los movimientos nuevos se registran como `paid` automáticamente.
- El formulario deja de ofrecer `pending` y `cancelled`.
- `cancelled` permanece como estado interno para auditoría.
- Cancelar será una acción `PATCH` explícita desde listados/detalle, con confirmación.
- Un movimiento cancelado se conserva, no se elimina, y queda excluido de todas las métricas.
- Los registros históricos `pending` no se convertirán silenciosamente. Si existen y se requiere una decisión de negocio, emitir `BLOQUEO CTO`.
- La eliminación física existente debe retirarse del flujo normal o quedar restringida a un caso administrativo claramente separado.

### 1.7 Acceso de clientes al portal

Para clientes nuevos, el acceso al portal deja de ser opcional.

- El formulario de cliente no debe mostrar checkbox para crear acceso.
- El correo del cliente será obligatorio y se usará como correo del usuario de portal, evitando dos correos que puedan desincronizarse.
- En alta se solicitará contraseña y confirmación, salvo que el flujo existente ya tenga una forma comprobada de enviar una invitación segura.
- En edición, la contraseña será opcional si el cliente ya tiene usuario; si no tiene usuario histórico, debe solicitarse la información necesaria para crearlo sin generar credenciales silenciosas.
- El cliente y su usuario deben actualizarse en una transacción de base de datos.
- El usuario relacionado tendrá rol interno `cliente` y permanecerá fuera del módulo administrativo de usuarios.

---

## 2. Fase 0 — Línea base, inventario y protección

### Tareas

- [x] Crear rama de implementación desde la rama del plan sincronizada, conforme a la instrucción posterior del propietario.
- [x] Ejecutar la suite actual y registrar fallos preexistentes.
- [x] Ejecutar `npm run build` antes de cambios.
- [x] Identificar migraciones y columnas actuales de `events`, `quotations`, `transactions`, `documents`, `clients` y `users`.
- [x] Localizar todos los usos de `events.total_amount`, `transactions.category`, estados de movimientos y generación de folios.
- [x] Localizar el activo oficial del logotipo usado por `<x-application-logo>` y comprobar si DomPDF puede cargarlo mediante `public_path()` o data URI.
- [x] Identificar pruebas existentes y factories disponibles.
- [x] Crear pruebas de caracterización mínimas para los cálculos financieros actuales antes de reemplazarlos.

### Criterios de aceptación

- Se conoce el alcance real del esquema y no hay cambios destructivos improvisados.
- Los fallos preexistentes quedan distinguidos de los generados por esta implementación.

### Commit sugerido

`test: documenta línea base del lote de cambios`

---

## 3. Fase 1 — Fundaciones compartidas de UI y dominio

### 3.1 Componente monetario reutilizable

- [ ] Crear una solución común para inputs monetarios que soporte `old()`, valores de edición, símbolo `$`, separadores y dos decimales.
- [ ] Crear normalización del lado servidor reutilizable para retirar `$`, espacios y comas antes de validar.
- [ ] Aplicar atributos accesibles y teclado móvil adecuado.
- [ ] Agregar pruebas unitarias de normalización: vacío, enteros, decimales, valores con comas y valores con símbolo.

### 3.2 Etiquetas comunes

- [ ] Centralizar etiquetas en español de estados de cotizaciones.
- [ ] Centralizar etiquetas y clases visuales de estados de eventos.
- [ ] Centralizar traducción de tipo de cliente: `prospect` → `Prospecto`, `active` → `Activo`, `past` → `Anterior` o la etiqueta española ya usada por el formulario.
- [ ] Mostrar roles con primera letra mayúscula sin modificar el valor interno.

### 3.3 Búsqueda reutilizable

- [ ] Definir patrón de búsquedas mediante parámetro `search`, consultas agrupadas y `withQueryString()`.
- [ ] Evitar consultas N+1 y escapar correctamente términos.

### Criterios de aceptación

- Un mismo valor monetario se comporta igual en todos los módulos.
- No hay `match` o traducciones duplicadas innecesariamente en varias vistas.

### Commit sugerido

`feat: agrega utilidades compartidas de dinero y etiquetas`

---

## 4. Fase 2 — Navegación, dashboard, usuarios, clientes y perfil

### 4.1 Navegación

Archivo principal esperado: `resources/views/layouts/navigation.blade.php`.

- [ ] Retirar del nivel principal: Usuarios, Clientes, Servicios, Conceptos de gasto y Gastos.
- [ ] Agregar como último elemento un menú **Catálogos** con: Usuarios, Clientes, Servicios y Conceptos de gasto.
- [ ] Respetar permisos individualmente; Catálogos solo aparece si el usuario puede ver al menos una opción.
- [ ] Implementar dropdown en escritorio y sección desplegable/accesible en móvil.
- [ ] Mantener Proveedores como opción principal porque no fue solicitado dentro de Catálogos.
- [ ] Gastos e Ingresos se accederán desde Movimientos, no desde la navegación principal.

### 4.2 Dashboard

- [ ] En el encabezado de Dashboard agregar accesos rápidos a: Nuevo cliente, Nuevo evento, Nueva cotización y Nuevo movimiento.
- [ ] Mostrar cada acceso solo si el usuario tiene su permiso correspondiente.
- [ ] Mantener diseño responsivo.

### 4.3 Usuarios

Archivos esperados: `UserController`, vistas `users/*` y pruebas Feature.

- [ ] Teléfono: permitir únicamente dígitos en interfaz y servidor. Usar `inputmode="numeric"` y patrón/sanitización; no usar `type="number"` porque puede alterar ceros iniciales.
- [ ] Mostrar nombres de roles con primera letra mayúscula.
- [ ] Mostrar errores de validación junto a contraseña y confirmación. La regla `confirmed` debe producir un mensaje visible en alta y edición.
- [ ] Excluir del índice a usuarios con rol `cliente`.
- [ ] Excluir el rol `cliente` de los dropdowns de alta y edición.
- [ ] Impedir acceso directo por URL a edición/eliminación de un usuario con rol `cliente`.
- [ ] No alterar los nombres internos de roles.

### 4.4 Clientes y acceso obligatorio

- [ ] Tipo predeterminado en alta: `prospect` / Prospecto, incluyendo después de un error de validación.
- [ ] Mostrar el tipo en español en la tabla.
- [ ] Eliminar el checkbox de acceso al portal.
- [ ] Hacer obligatorio el correo para nuevos clientes.
- [ ] Crear siempre usuario relacionado con rol `cliente` dentro de la misma transacción.
- [ ] Agregar confirmación de contraseña y errores visibles.
- [ ] Mantener sincronizados nombre, correo, teléfono y estado relevante entre cliente y usuario.
- [ ] En edición, contraseña opcional para clientes que ya tienen usuario; si se captura, exigir confirmación.
- [ ] Evitar correos duplicados con validación que ignore al usuario relacionado.
- [ ] Definir comportamiento seguro para clientes históricos sin usuario; no crear contraseñas desconocidas sin informar.

### 4.5 Recuperación de contraseña

- [ ] Confirmar que el enlace “Olvidé mi contraseña” está disponible en login.
- [ ] Crear prueba Feature que solicite recuperación para un usuario con rol `cliente` y verifique el envío de la notificación.
- [ ] Crear prueba del restablecimiento mediante token válido.
- [ ] Verificar que un usuario inactivo no obtenga acceso después de cambiar contraseña, si el login ya aplica esa regla.
- [ ] Documentar variables de correo necesarias para prueba manual; no modificar secretos.

### 4.6 Profile en español

- [ ] Traducir todos los textos visibles de `profile` y parciales relacionados.
- [ ] Traducir mensajes de ayuda, botones, confirmaciones y modal de eliminación.
- [ ] No modificar nombres de campos ni rutas.

### Criterios de aceptación

- Los clientes no aparecen en Usuarios y no pueden administrarse indirectamente por URL.
- Todo cliente nuevo queda enlazado a un usuario de portal funcional.
- La recuperación de contraseña está cubierta por pruebas.
- Navegación móvil y escritorio respetan permisos.

### Commit sugerido

`feat: reorganiza catálogos y consolida acceso de clientes`

---

## 5. Fase 3 — Eventos, documentos y contratos

### 5.1 Alta y edición de evento

- [ ] Cambiar etiqueta a **Presupuesto estimado total**.
- [ ] Aplicar componente monetario.
- [ ] Quitar el campo **Monto total** de alta y edición.
- [ ] Retirar `total_amount` de validación y asignación en controladores.
- [ ] Mantener temporalmente la columna existente para compatibilidad si otras partes aún la requieren; no eliminarla hasta completar el reemplazo financiero y comprobar referencias.

### 5.2 Listado de eventos

- [ ] Agregar buscador por título, cliente, tipo de evento y, cuando sea razonable, fecha.
- [ ] Conservar paginación y filtros en query string.
- [ ] Mostrar cada estado con etiqueta española y color consistente.
- [ ] Asegurar contraste accesible y no depender solo del color.

### 5.3 Perfil del evento

- [ ] Agregar acceso rápido a cotizaciones del evento, usando filtro `event_id` en el índice de cotizaciones.
- [ ] Agregar campo/tarjeta **Costo evento** con la suma de cotizaciones aprobadas.
- [ ] Mostrar **Pendiente por cobrar** con la nueva regla financiera.
- [ ] Los botones `+ Ingreso` y `+ Gasto` deben abrir el formulario de movimiento con `event_id` y `type` correctos.
- [ ] La lista de movimientos debe ofrecer Cancelar para movimientos no cancelados y mostrar claramente los cancelados.
- [ ] No ofrecer eliminación como sustituto de cancelación.

### 5.4 Carga de documentos desde evento

- [ ] Al entrar desde el evento, precargar y mostrar cliente y evento.
- [ ] El servidor debe derivar el cliente desde el evento y rechazar una combinación distinta manipulada.
- [ ] Mantener la posibilidad de cambiar de evento únicamente cuando el flujo general de documentos lo requiera.

### 5.5 Contratos

- [ ] Aplicar el componente monetario a renta total, anticipo, segundo pago, depósito por daños y costo de hora extra.
- [ ] Normalizar en servidor antes de validar.
- [ ] Conservar cálculos y generación de contrato existentes.

### Criterios de aceptación

- Ya no se captura manualmente un monto total duplicado para eventos.
- El evento muestra costo y pendiente derivados, no valores manuales.
- Los documentos abiertos desde un evento llegan correctamente preseleccionados.

### Commit sugerido

`feat: mejora gestión y resumen financiero de eventos`

---

## 6. Fase 4 — Cotizaciones

### 6.1 Listado

- [ ] Agregar búsqueda por folio, cliente, evento y estado/etiqueta cuando sea viable.
- [ ] Agregar filtro `event_id` para el acceso rápido desde el evento.
- [ ] Mostrar estados en español.
- [ ] Mantener paginación con parámetros.

### 6.2 Alta y edición

- [ ] Al seleccionar cliente, el selector de evento debe contener únicamente “Sin evento” y eventos de ese cliente.
- [ ] En edición, conservar selección válida y limpiar un evento si se cambia a otro cliente al que no pertenece.
- [ ] Validar en servidor que el evento pertenece al cliente en `store` y `update`.
- [ ] Mover Descuento después de Items.
- [ ] Aplicar formato monetario a precio unitario, total por item, subtotal, descuento y total.
- [ ] Los totales visuales deben actualizarse al cambiar cantidad, precio o descuento, pero el servidor recalcula todo.
- [ ] Antes de Items agregar un recuadro informativo del evento seleccionado: nombre/título, fecha, tipo, número de invitados, estado y presupuesto estimado. Si se elige “Sin evento”, mostrar un estado vacío claro.
- [ ] No cargar todos los eventos sin filtrar en el DOM si puede evitarse; puede usarse un mapa JSON pequeño o endpoint protegido.

### 6.3 Folio

- [ ] Para nuevas cotizaciones usar formato corto y seguro basado en ID, recomendado: `C-000123`.
- [ ] Generarlo después de crear el registro dentro de la misma transacción para evitar colisiones.
- [ ] Conservar folios históricos sin migración destructiva.
- [ ] Agregar índice único si no existe y si los datos actuales lo permiten; si existen duplicados, emitir `BLOQUEO CTO`.

### 6.4 PDF premium

- [ ] Incorporar logotipo oficial de Hacienda Cinco con una ruta compatible con DomPDF.
- [ ] Rediseñar encabezado, jerarquía tipográfica, datos de cliente/evento, tabla de conceptos y bloque de totales para una apariencia premium y sobria.
- [ ] Mantener tamaño carta y evitar cortes de tabla o totales entre páginas.
- [ ] Probar con 1 item, muchos items, descuento cero y descuento positivo.
- [ ] No enlazar imágenes remotas en el PDF.

### Criterios de aceptación

- No es posible asociar una cotización a un evento de otro cliente, ni desde UI ni mediante petición manual.
- Folios nuevos son cortos, únicos y legibles.
- PDF incluye logo local y mantiene legibilidad con varias páginas.

### Commit sugerido

`feat: refina flujo y presentación de cotizaciones`

---

## 7. Fase 5 — Movimientos, ingresos, gastos y comprobantes

### 7.1 Pantallas de Ingresos y Gastos

- [ ] Mantener Movimientos como pantalla principal.
- [ ] Agregar botones visibles a **Ingresos** y **Gastos** dentro de Movimientos.
- [ ] Crear pantalla de Ingresos equivalente a la existente de Gastos.
- [ ] Refactorizar consultas/vistas compartidas para no duplicar dos módulos completos.
- [ ] En ambas pantallas, excluir cancelados de totales. Los cancelados pueden mostrarse si el filtro lo solicita, pero nunca sumarse.
- [ ] Corregir el total general de Gastos para que no incluya cancelados ni mezcle pendientes históricos indebidamente.

### 7.2 Alta y edición

- [ ] Aplicar componente monetario al monto.
- [ ] Si la URL contiene `type=income`, preseleccionar Ingreso.
- [ ] Si contiene `type=expense`, preseleccionar Gasto.
- [ ] Si contiene `event_id`, precargar evento y cliente coherentes.
- [ ] Agregar botón Cancelar que regrese al evento cuando existe `event_id`; en caso contrario, a Movimientos.
- [ ] Retirar Categoría de alta, edición y validación. Mantener columna histórica nullable por compatibilidad salvo que una auditoría demuestre que puede eliminarse sin riesgo.
- [ ] Retirar selector de estado. Los nuevos movimientos se guardan como `paid` en servidor.
- [ ] No permitir editar directamente un movimiento cancelado, salvo campos explícitamente seguros definidos después de auditoría.
- [ ] Validar coherencia entre cliente, evento y cotización.

### 7.3 Cancelación

- [ ] Agregar ruta `PATCH` y método específico para cancelar.
- [ ] Solo permitir cancelar movimientos que no estén cancelados.
- [ ] Hacer la operación idempotente o devolver un mensaje claro si ya estaba cancelado.
- [ ] Conservar referencia, comprobante, autoría y datos del movimiento.
- [ ] Registrar `cancelled_at` y `cancelled_by` mediante migración si el esquema no tiene auditoría equivalente.
- [ ] Excluir cancelados de recibos vigentes, balances, ingresos, gastos, pendiente por cobrar y cuentas por pagar relacionadas.
- [ ] Revisar efectos automáticos de `SupplierPayable::refreshAutomaticStatus()` al cancelar.

### 7.4 Comprobante

Como el requisito es un archivo por movimiento, la implementación recomendada es agregar metadatos nullable en `transactions`:

- `proof_file_path`
- `proof_original_name`
- `proof_mime_type`
- `proof_file_size`

Tareas:

- [ ] Aceptar PDF, JPG, JPEG, PNG y WEBP, con límite razonable documentado.
- [ ] Guardar con nombre generado, nunca con el nombre original como ruta.
- [ ] Mostrar enlace de descarga/vista en detalle y listados relevantes.
- [ ] Proteger la descarga con autenticación y permiso `manage payments`; no exponer una ruta arbitraria recibida del usuario.
- [ ] Al reemplazar comprobante, borrar el archivo anterior después de guardar correctamente el nuevo.
- [ ] Si ocurre rollback de base de datos, evitar dejar archivos huérfanos.
- [ ] Al cancelar, conservar el comprobante.

### Criterios de aceptación

- Todos los movimientos nuevos quedan pagados y solo se cancelan mediante acción específica.
- Un cancelado no altera ninguna cifra.
- Los accesos desde evento preseleccionan correctamente Ingreso o Gasto.
- El comprobante puede cargarse y consultarse con permisos.

### Commit sugerido

`feat: consolida ingresos gastos y cancelación de movimientos`

---

## 8. Fase 6 — Motor financiero y regresiones cruzadas

### 8.1 Servicio financiero

Actualizar o reemplazar `App\Services\FinancialBalanceCalculator` para que sea la fuente única de cálculos del evento.

Debe devolver al menos:

- `approved_quotation_total`
- `paid_income`
- `paid_expenses`
- `pending_receivable`
- `overpayment`
- `cash_balance` o equivalente, si ya se usa en interfaz
- movimientos con saldo acumulado, excluyendo cancelados del efecto

### 8.2 Consultas

- [ ] Usar relaciones cargadas o consultas agregadas eficientes.
- [ ] Evitar que distintas pantallas calculen el mismo concepto de forma distinta.
- [ ] Revisar Dashboard, evento, cliente, exportaciones XLSX, recibos, portal de cliente y cualquier reporte que use `total_amount` o estados.
- [ ] Decidir si `events.total_amount` queda deprecado temporalmente o puede retirarse en una migración posterior. No eliminar hasta demostrar que no tiene consumidores.

### 8.3 Pruebas financieras obligatorias

- [ ] Evento sin cotizaciones: costo 0, pendiente 0.
- [ ] Una cotización aprobada: costo igual a su total.
- [ ] Varias aprobadas: suma correcta.
- [ ] Cotizaciones no aprobadas: no cuentan.
- [ ] Ingreso pagado: reduce pendiente.
- [ ] Ingreso cancelado: no reduce pendiente.
- [ ] Gasto pagado: no reduce pendiente por cobrar.
- [ ] Sobrepago: pendiente 0 y sobrepago positivo.
- [ ] Movimiento histórico pending: no cuenta como cobrado.
- [ ] Cotización aprobada de otro evento: no cuenta.
- [ ] Cambiar una cotización de approved a rejected actualiza el cálculo.
- [ ] Cancelar y volver a consultar no deja totales cacheados incorrectos.

### Criterios de aceptación

- Todas las pantallas y exportaciones relevantes coinciden en costo, cobrado y pendiente.
- Ninguna cifra depende de un campo manual duplicado.

### Commit sugerido

`refactor: centraliza cálculo financiero por cotizaciones aprobadas`

---

## 9. Fase 7 — Homologación visual, QA y cierre

### 9.1 Conceptos de gasto

- [ ] Homologar botones de acciones con el patrón visual y accesible de otras tablas.
- [ ] Revisar desktop y móvil.

### 9.2 Matriz manual mínima

Probar con roles administrador, empleado con permisos parciales y cliente:

- [ ] Navegación Catálogos y permisos.
- [ ] Alta/edición de usuario con teléfono inválido y contraseñas distintas.
- [ ] Alta de cliente, acceso al portal y recuperación de contraseña.
- [ ] Alta/edición/búsqueda de evento.
- [ ] Alta/edición/búsqueda/PDF de cotización.
- [ ] Restricción de eventos por cliente.
- [ ] Alta de ingreso y gasto desde evento y desde Movimientos.
- [ ] Cancelación y actualización inmediata de totales.
- [ ] Carga y descarga de comprobante.
- [ ] Contratos con montos formateados.
- [ ] Carga de documento con cliente/evento precargados.
- [ ] Profile completamente en español.

### 9.3 Validación automática final

- [ ] `php artisan test`
- [ ] `vendor/bin/pint --test`
- [ ] `npm run build`
- [ ] Revisar `php artisan route:list` para rutas nuevas y permisos.
- [ ] Revisar migraciones con base de datos de pruebas limpia.
- [ ] Probar rollback de las migraciones nuevas.

### 9.4 Entrega de Codex

Codex debe entregar:

1. Resumen por fase.
2. Lista de migraciones creadas.
3. Lista de pruebas agregadas.
4. Resultado de comandos de validación.
5. Riesgos o decisiones pendientes.
6. Pasos exactos de despliegue, sin ejecutarlos.
7. Checklist manual para Fercho.

### Commit sugerido

`test: completa regresión del lote de julio`

---

## 10. Trazabilidad de solicitudes

| ID | Solicitud | Fase |
|---|---|---|
| R01 | Menú Catálogos con Usuarios, Clientes, Servicios y Conceptos de gasto | 2 |
| R02 | Gastos dentro de Movimientos y nueva pantalla de Ingresos | 5 |
| R03 | Teléfono de usuarios solo con números | 2 |
| R04 | Roles con primera letra mayúscula | 1–2 |
| R05 | Error visible cuando las contraseñas no coinciden | 2 |
| R06 | Ocultar clientes de Usuarios y acceso de portal obligatorio | 2 |
| R07 | Prospecto preseleccionado | 2 |
| R08 | Tipo de cliente en español | 1–2 |
| R09 | Validar recuperación de contraseña del cliente | 2 |
| R10 | Homologar acciones en Conceptos de gasto | 7 |
| R11 | Presupuesto estimado total con formato monetario | 3 |
| R12 | Quitar monto total manual del evento | 3 y 6 |
| R13 | Buscador de eventos | 3 |
| R14 | Colores por estado de evento | 1 y 3 |
| R15 | Buscador de cotizaciones | 4 |
| R16 | Acceso a cotizaciones desde evento | 3–4 |
| R17 | Accesos rápidos en Dashboard | 2 |
| R18 | Eventos de cotización filtrados por cliente | 4 |
| R19 | Descuento después de Items | 4 |
| R20 | Montos de cotización con formato | 1 y 4 |
| R21 | Recuadro informativo del evento en cotización | 4 |
| R22 | Logo y formato premium en PDF | 4 |
| R23 | Estado de cotización en español | 1 y 4 |
| R24 | Folio corto | 4 |
| R25 | Montos de contrato con formato | 3 |
| R26 | Documento con cliente/evento precargados | 3 |
| R27 | Monto de movimiento con formato | 5 |
| R28 | Pendiente basado en cotizaciones aprobadas menos ingresos | 6 |
| R29 | Cancelar movimiento sin afectar cifras | 5–6 |
| R30 | Tipo preseleccionado desde +Ingreso/+Gasto | 3 y 5 |
| R31 | Retirar categoría de movimientos | 5 |
| R32 | Cargar comprobante en movimientos | 5 |
| R33 | Profile en español | 2 |
| R34 | Botón Cancelar en nuevo movimiento | 5 |
| R35 | Costo evento por cotizaciones aprobadas | 3 y 6 |

---

## 11. Despliegue previsto, no ejecutar desde Codex

Cuando todas las pruebas pasen y el CTO apruebe el PR:

1. Crear respaldo de base de datos y archivos.
2. Activar modo mantenimiento si las migraciones lo ameritan.
3. `git pull` de la versión aprobada.
4. `composer install --no-dev --optimize-autoloader`.
5. `npm ci && npm run build` si los assets se construyen en servidor; en caso contrario desplegar assets ya generados conforme al proceso actual.
6. `php artisan migrate --force`.
7. `php artisan optimize:clear`.
8. `php artisan config:cache` y `php artisan route:cache` solo si el proyecto los usa actualmente sin errores.
9. Verificar permisos de `storage` y `bootstrap/cache`.
10. Verificar almacenamiento de comprobantes y enlace/descarga.
11. Ejecutar smoke test de login, cliente, evento, cotización, movimiento y cancelación.
12. Desactivar mantenimiento.

No convertir datos históricos ni limpiar columnas deprecadas durante este despliegue sin una fase aprobada por separado.
