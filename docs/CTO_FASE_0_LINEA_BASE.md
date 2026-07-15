# Fase 0 — Línea base e inventario

Fecha de ejecución: 15 de julio de 2026.

## Línea base

- Rama fuente autorizada: `agent/cto-plan-cambios-julio-2026`, sincronizada con `origin`.
- Rama de implementación: `agent/implementacion-cambios-julio-2026`.
- `php artisan test`: 118 pruebas, 588 aserciones, sin fallos.
- `npm run build`: exitoso con Vite 8.0.7.
- No se observaron fallos preexistentes.
- Las pruebas están protegidas para usar exclusivamente SQLite en memoria.

## Esquema relevante

- `users`: nombre, correo único, contraseña, teléfono nullable, indicador activo y campos de autenticación.
- `clients`: usuario nullable, tipo `prospect|active|past`, nombre, empresa, correo y teléfonos nullable, origen y notas.
- `events`: cliente obligatorio, datos del evento, presupuesto estimado decimal y `total_amount` decimal con valor predeterminado cero. La columna de dirección fue retirada; después se añadieron estado `reserved` y metadatos de Google Calendar.
- `quotations`: cliente obligatorio, evento nullable, folio nullable con índice único, estado, subtotal, descuento y total decimales, vigencia y notas.
- `transactions`: cliente actualmente nullable, evento/cotización/proveedor/concepto/cuenta por pagar nullable, tipo, alcance, fecha, importe decimal, método, categoría nullable, referencia única, token de recibo único, estado y notas.
- `documents`: cliente/evento/cargador nullable, categoría, nombre original, ruta, MIME, tamaño y notas.

Las migraciones revisadas son aditivas o reversibles dentro de su alcance. No se detectó una contradicción material entre esquema y modelos que requiera conversión destructiva.

## Consumidores y reglas actuales

- `events.total_amount` todavía alimenta `EventController`, `EventContractController`, `FinancialBalanceCalculator`, `FinancialBalanceWorkbook`, `EventContractGenerator`, portal del cliente y vistas/formularios de eventos y contratos. Las pruebas de calendario, contratos, recibos y exportación también lo fijan como dato de entrada.
- `transactions.category` se valida, persiste, busca y presenta en movimientos, eventos, clientes, recibos, correo, exportaciones y portal; también participa en generación de contratos. Debe retirarse por fases sin eliminar la columna histórica.
- Los estados de movimientos `pending|paid|cancelled` aparecen en dashboard, movimientos, gastos, recibos, cuentas por pagar, exportaciones y flujos históricos de pagos. Las sumas principales suelen filtrar `paid`, pero la pantalla de gastos mantiene un total pendiente separado que deberá corregirse en la Fase 5.
- Las referencias de movimientos se generan en `TransactionReferenceGenerator` mediante secuencias anuales independientes (`ING`/`GAS`) y tienen índice único. Los folios de cotización se generan actualmente como `COT-YYYYMMDDHHMMSS`; `quotations.folio` ya tiene índice único.
- La fuente financiera actual es `FinancialBalanceCalculator`: usa `events.total_amount`, suma ingresos/gastos pagados, expone ingresos pendientes e ignora cancelados en el saldo acumulado. `FinancialBalanceWorkbook` y las exportaciones dependen de ese contrato.

## Logo y DomPDF

- El componente `<x-application-logo>` usa `public/images/hacienda-cinco-logo.png`.
- Es el único activo de marca localizado: PNG RGBA de 512 × 475 píxeles.
- El recibo PDF ya lo carga desde `public_path('images/hacienda-cinco-logo.png')`; la generación DomPDF está cubierta por la suite. La misma ruta local puede reutilizarse en el PDF de cotización sin imágenes remotas.

## Pruebas y factories

- Existe únicamente `UserFactory`; clientes, eventos, cotizaciones y movimientos se construyen mediante helpers de pruebas.
- Hay cobertura Feature para autenticación, navegación, perfil, calendario, contratos, PDF de cotización, recibos, movimientos, gastos, proveedores, cuentas por pagar, exportaciones y referencias.
- `FinancialBalanceExportTest` ya caracteriza un caso límite del calculador. `FinancialBalanceCalculatorCharacterizationTest` agrega una frontera unitaria explícita para el total manual, estados, gastos y orden cronológico antes del reemplazo de la Fase 6.
