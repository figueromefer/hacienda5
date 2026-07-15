# Instrucciones maestras para Codex — Hacienda Cinco

Este repositorio corresponde al sistema Hacienda Cinco. Antes de modificar código, lee completo:

- `docs/CTO_PLAN_CAMBIOS_JULIO_2026.md`

Ese documento es la fuente de verdad para el lote de cambios de julio de 2026.

## Forma de trabajo obligatoria

1. Trabaja en una rama de implementación creada desde la versión más reciente de `main`. No implementes directamente sobre `main`.
2. Ejecuta las fases en el orden indicado en el plan. No mezcles varias fases en un solo commit.
3. Antes de tocar una fase, inspecciona controladores, modelos, vistas, rutas, migraciones y pruebas relacionadas. El plan define el resultado esperado, no sustituye la lectura del código.
4. Al terminar cada fase:
   - ejecuta las pruebas específicas de esa fase;
   - ejecuta `vendor/bin/pint --dirty`;
   - registra un commit pequeño y descriptivo;
   - actualiza las casillas correspondientes del plan únicamente cuando los criterios de aceptación estén comprobados.
5. Al terminar todo el lote ejecuta, como mínimo:
   - `php artisan test`;
   - `vendor/bin/pint --test`;
   - `npm run build`.
6. No despliegues a producción, no modifiques secretos y no ejecutes migraciones contra una base real.

## Reglas técnicas no negociables

- Laravel y el servidor son la fuente de verdad. El formato visual con JavaScript nunca sustituye validación, normalización ni cálculo del lado servidor.
- Los importes se almacenan como valores decimales sin símbolos ni separadores. Los formularios pueden mostrar `$`, comas y dos decimales, pero deben normalizar el valor antes de validar y persistir.
- No uses `float` para lógica financiera crítica cuando pueda evitarse. Mantén cálculos coherentes con columnas `decimal` y redondeo a dos decimales.
- No dupliques traducciones de estados en múltiples vistas. Centraliza etiquetas y, cuando aplique, clases visuales en modelos, enums, componentes o helpers reutilizables.
- No cambies los nombres internos de roles o permisos de Spatie para “traducirlos”. Traduce únicamente su presentación.
- Toda relación cliente–evento–cotización–movimiento debe validarse también en servidor. No confíes en que un `select` filtrado impida peticiones manipuladas.
- Un movimiento cancelado debe conservarse por auditoría y quedar excluido de ingresos, gastos, saldos y pendientes. Cancelar no significa eliminar.
- No hagas migraciones destructivas ni conviertas datos históricos silenciosamente.
- Conserva permisos, rutas protegidas, diseño responsivo y compatibilidad con los registros existentes.
- Agrega pruebas de regresión para cada regla de negocio relevante.

## Cuándo detenerte y pedir revisión del CTO

Continúa de forma autónoma salvo que aparezca uno de estos bloqueos reales:

1. No existe un archivo de logotipo adecuado para el PDF o hay varios activos y no es posible determinar cuál es el oficial.
2. Los datos históricos requieren una conversión irreversible, especialmente movimientos con estado `pending`, clientes sin usuario de portal o folios duplicados.
3. El esquema real o las migraciones existentes contradicen de forma material el código actual.
4. Una regla solicitada tiene dos interpretaciones con impactos financieros distintos y el plan no la resuelve.
5. Las pruebas revelan una regresión fuera del alcance que no puede corregirse sin rediseñar otro módulo.

Cuando ocurra, no improvises. Entrega al usuario este formato:

```text
BLOQUEO CTO
Fase:
Hallazgo comprobado:
Archivos/datos afectados:
Riesgo de continuar:
Opción A:
Opción B:
Recomendación de Codex:
Pregunta concreta para Alex (CTO):
```

El usuario compartirá ese bloque con Alex. Para problemas normales de implementación, depuración o pruebas, resuélvelos sin escalar.

## Inicio recomendado para una sesión nueva de Codex

```text
Lee AGENTS.md y docs/CTO_PLAN_CAMBIOS_JULIO_2026.md completos. Inspecciona el estado actual del repositorio y comienza por la Fase 0. Trabaja fase por fase, crea commits separados, ejecuta las pruebas indicadas y solo detente si debes emitir un BLOQUEO CTO conforme a AGENTS.md. No despliegues a producción.
```
