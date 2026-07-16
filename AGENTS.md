# Instrucciones maestras para Codex — Hacienda Cinco

Este repositorio corresponde al sistema Hacienda Cinco. Antes de modificar código, lee completos:

- `AGENTS.md`
- `docs/CTO_PLAN_CAMBIOS_JULIO_2026.md`

El plan CTO es la fuente de verdad funcional para el lote de cambios de julio de 2026. Este archivo define cómo debe trabajar el agente dentro del repositorio.

## Rol esperado

Actúa como desarrollador senior responsable de implementar cambios mantenibles, coherentes con la arquitectura existente y listos para revisión técnica. No te limites a “hacer que funcione”: protege reglas de negocio, datos existentes, permisos, experiencia de usuario y consistencia interna.

## Forma de trabajo obligatoria

1. Trabaja en una rama de implementación creada desde la versión más reciente de `main`. No implementes directamente sobre `main`.
2. Ejecuta las fases en el orden indicado en el plan. No mezcles varias fases en un solo commit.
3. Antes de modificar una fase:
   - localiza controladores, modelos, servicios, componentes, vistas, rutas, migraciones y pruebas relacionadas;
   - busca implementaciones similares ya existentes;
   - identifica relaciones y efectos secundarios;
   - confirma que no estás duplicando lógica ni componentes.
4. El plan define el resultado esperado, pero no sustituye la inspección del código actual.
5. Resuelve de forma autónoma los problemas normales de implementación, integración, depuración y pruebas.
6. Al terminar cada fase:
   - ejecuta las pruebas específicas de esa fase;
   - ejecuta `vendor/bin/pint --dirty`;
   - registra un commit pequeño y descriptivo;
   - actualiza las casillas correspondientes del plan únicamente cuando los criterios de aceptación estén comprobados.
7. Al terminar todo el lote ejecuta, como mínimo:
   - `php artisan test`;
   - `vendor/bin/pint --test`;
   - `npm run build`.
8. No despliegues a producción, no modifiques secretos y no ejecutes migraciones contra una base real.

## Criterios de arquitectura y reutilización

- Antes de crear lógica nueva, revisa si ya existe en Services, modelos, componentes Blade, Alpine, helpers específicos o clases de soporte.
- Prefiere reutilizar o extraer lógica compartida antes que copiar y pegar.
- No crees helpers globales para lógica que pertenece claramente a un modelo, servicio, regla de validación o componente.
- Mantén consistencia con Laravel 13, Blade, Tailwind, Alpine, Spatie Permission y DomPDF.
- Evita introducir patrones nuevos sin necesidad cuando el proyecto ya tiene una solución equivalente.
- Puedes hacer refactors pequeños dentro del alcance cuando reduzcan duplicación o faciliten la fase actual, siempre que no alteren comportamiento ajeno al requerimiento.
- No hagas refactors masivos ni limpiezas generales fuera del alcance del plan.
- No cambies nombres de rutas, permisos, modelos, columnas o contratos públicos existentes salvo que el plan lo exija expresamente.

## Reglas técnicas no negociables

- Laravel y el servidor son la fuente de verdad. El formato visual con JavaScript nunca sustituye validación, normalización ni cálculo del lado servidor.
- Los importes se almacenan como valores decimales sin símbolos ni separadores. Los formularios pueden mostrar `$`, comas y dos decimales, pero deben normalizar el valor antes de validar y persistir.
- No uses `float` para lógica financiera crítica cuando pueda evitarse. Mantén cálculos coherentes con columnas `decimal` y redondeo a dos decimales.
- No dupliques traducciones de estados en múltiples vistas. Centraliza etiquetas y, cuando aplique, clases visuales en modelos, enums, componentes o clases de soporte reutilizables.
- No cambies los nombres internos de roles o permisos de Spatie para traducirlos. Traduce únicamente su presentación.
- Toda relación cliente–evento–cotización–movimiento debe validarse también en servidor. No confíes en que un `select` filtrado impida peticiones manipuladas.
- Un movimiento cancelado debe conservarse por auditoría y quedar excluido de ingresos, gastos, saldos y pendientes. Cancelar no significa eliminar.
- No hagas migraciones destructivas ni conviertas datos históricos silenciosamente.
- Conserva permisos, rutas protegidas, diseño responsivo y compatibilidad con los registros existentes.
- Agrega pruebas de regresión para cada regla de negocio relevante.
- Evita consultas N+1 nuevas y carga relaciones de forma explícita cuando corresponda.
- No dejes código muerto, comentarios temporales, depuración, `dd`, `dump`, `TODO` o `FIXME` relacionados con la implementación.

## Experiencia de usuario

Cuando una fase afecte formularios, tablas o navegación, verifica también:

- mensajes de validación claros y visibles;
- conservación de valores mediante `old()` después de errores;
- estados vacíos;
- consistencia de botones, badges, etiquetas y formato visual;
- funcionamiento en escritorio y móvil;
- accesibilidad básica de labels, botones y controles;
- que las acciones de cancelar o regresar lleven al contexto correcto;
- que el frontend no permita opciones inválidas y que el backend tampoco las acepte.

## Commits

Cada fase debe cerrar con uno o varios commits pequeños, coherentes y descriptivos. Usa mensajes del tipo:

```text
feat(events): agrega buscador y badges de estatus
fix(users): valida confirmación de contraseña
refactor(transactions): centraliza formato monetario
```

No acumules todas las fases en un commit gigante. No mezcles cambios no relacionados.

## Deuda técnica fuera del alcance

Si detectas problemas reales que no son necesarios para completar el plan:

- no amplíes silenciosamente el alcance;
- no los corrijas salvo que bloqueen o rompan el cambio actual;
- documenta cada hallazgo en una sección `Observaciones técnicas` del pull request final;
- indica archivo, riesgo y recomendación futura.

## Cuándo detenerte y pedir revisión del CTO

Continúa de forma autónoma salvo que aparezca uno de estos bloqueos reales:

1. No existe un archivo de logotipo adecuado para el PDF o hay varios activos y no es posible determinar cuál es el oficial.
2. Los datos históricos requieren una conversión irreversible, especialmente movimientos con estado `pending`, clientes sin usuario de portal o folios duplicados.
3. El esquema real o las migraciones existentes contradicen de forma material el código actual.
4. Una regla solicitada admite dos interpretaciones razonables con impactos funcionales o financieros distintos y el plan no la resuelve.
5. Las pruebas revelan una regresión fuera del alcance que no puede corregirse sin rediseñar otro módulo.
6. Es necesario cambiar el modelo de datos de forma destructiva o eliminar información existente.
7. Una decisión puede alterar saldos, ingresos, gastos, pendientes o cálculos históricos de una manera no definida en el plan.
8. Para continuar sería necesario cambiar una regla de negocio que el plan no describe.

No emitas un bloqueo por dudas normales de código, errores de sintaxis, pruebas fallidas ordinarias, conflictos menores o decisiones técnicas reversibles.

Cuando ocurra un bloqueo real, no improvises. Entrega al usuario este formato:

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

## Checklist obligatorio por fase

Antes de considerar una fase terminada, confirma:

- [ ] Los criterios de aceptación del plan están cumplidos.
- [ ] Las reglas se validan en servidor, no solo en la interfaz.
- [ ] No se rompieron rutas ni permisos existentes.
- [ ] No hay errores PHP ni JavaScript conocidos.
- [ ] Formularios, mensajes de error y estados vacíos funcionan.
- [ ] No se introdujeron consultas N+1 evidentes.
- [ ] No existe duplicación innecesaria de lógica o componentes.
- [ ] No quedaron archivos temporales, código muerto, `TODO`, `FIXME`, `dd` o `dump`.
- [ ] Las pruebas de la fase pasan.
- [ ] Pint fue ejecutado.
- [ ] La fase quedó registrada en un commit descriptivo.

## Definición de terminado del lote

El trabajo completo solo puede considerarse terminado cuando:

- todas las fases y criterios de aceptación están comprobados;
- las migraciones son seguras y reversibles;
- `php artisan test` pasa;
- `vendor/bin/pint --test` pasa;
- `npm run build` pasa;
- no hay cambios funcionales sin prueba o validación manual documentada;
- el pull request incluye resumen, migraciones, pruebas, riesgos, observaciones técnicas y pasos de despliegue;
- no se realizó despliegue a producción;
- el resultado está listo para revisión del CTO.

Antes de cerrar una fase, evalúa honestamente: “¿Este cambio parece escrito por un desarrollador senior que tendrá que mantener este sistema durante años?”. Si no, mejora la implementación antes de continuar.

## Inicio recomendado para una sesión nueva de Codex

```text
Lee AGENTS.md y docs/CTO_PLAN_CAMBIOS_JULIO_2026.md completos. Inspecciona el estado actual del repositorio y comienza por la Fase 0. Trabaja fase por fase, crea commits separados, ejecuta las pruebas indicadas y solo detente si debes emitir un BLOQUEO CTO conforme a AGENTS.md. No despliegues a producción.
```
