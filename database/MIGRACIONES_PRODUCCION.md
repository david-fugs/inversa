# Migraciones pendientes para producción — Tarifas / Cobros (GPU + ACU) y Bases

Resumen de **todas** las migraciones SQL generadas hasta ahora para el módulo de
Tarifas / Cobros, en el orden exacto en que deben ejecutarse. Ya fueron
aplicadas y verificadas contra la base de datos de trabajo del proyecto
(`softepuc_inversa`); faltan por ejecutar en el servidor de **producción**.

Cada migración también existe como archivo `.sql` independiente en esta misma
carpeta (`database/`), por si prefieren correrlas desde ahí en vez de copiar y
pegar.

> **Antes de ejecutar en producción:** hacer un respaldo de la tabla
> `tarifas_gpu` (`mysqldump softepuc_inversa tarifas_gpu > backup_tarifas_gpu.sql`
> o el nombre de base que corresponda en prod). Ambas migraciones son
> `ALTER TABLE`, que en MySQL/InnoDB hacen commit automático — no se pueden
> revertir con un simple `ROLLBACK`.

---

## 1. `migration_tarifas_gpu_bases.sql` — Tarifas por aerolínea **y base**

Agrega la columna `base_id` a `tarifas_gpu`. Antes, una tarifa aplicaba a una
aerolínea sin importar la base; ahora se puede configurar una tarifa distinta
por aerolínea + base específica, o dejarla en "Todas las bases".

- `base_id = NULL` → la tarifa aplica a **todas las bases** de esa aerolínea.
- `base_id = N` → la tarifa aplica solo a esa aerolínea + esa base. Tiene
  prioridad sobre la de "todas las bases" si ambas existen.

### 1.1. Lo nuevo (columna + foreign key)

```sql
-- Agregar la columna base_id (NULL = "todas las bases" para esa aerolínea)
ALTER TABLE `tarifas_gpu`
    ADD COLUMN `base_id` INT UNSIGNED NULL
        COMMENT 'NULL = aplica a todas las bases de la aerolínea'
        AFTER `airline_id`;

-- Foreign key hacia bases (RESTRICT: no se puede borrar una base
-- que tenga tarifas configuradas específicamente para ella)
ALTER TABLE `tarifas_gpu`
    ADD CONSTRAINT `fk_tarifas_gpu_base`
        FOREIGN KEY (`base_id`) REFERENCES `bases` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT;
```

### 1.2. Modificaciones sobre lo existente

```sql
-- La tabla tenía UNIQUE KEY (airline_id): una sola tarifa por aerolínea.
-- Ahora puede haber varias (una por base + una para "todas las bases"),
-- así que se reemplaza por un índice normal.
ALTER TABLE `tarifas_gpu`
    DROP INDEX `uq_tarifas_gpu_airline`,
    ADD INDEX `idx_tarifas_gpu_airline_base` (`airline_id`, `base_id`);

-- Registros existentes: dejarlos explícitamente como "Todas las bases"
-- (ya quedan así por el DEFAULT NULL del ALTER anterior; este UPDATE
-- es solo por seguridad/idempotencia).
UPDATE `tarifas_gpu` SET `base_id` = NULL WHERE `base_id` IS NOT NULL;
```

---

## 2. `migration_tarifas_cobro_tipo.sql` — Tipo de cobro (GPU / ACU)

**Requiere haber aplicado antes la migración #1** (usa la columna `base_id`).

Agrega la columna `tipo_cobro` a `tarifas_gpu`. La tabla deja de ser exclusiva
de la planta eléctrica (GPU); ahora también sirve para configurar la tarifa
de Aire Acondicionado (ACU) por aerolínea y base.

- `tipo_cobro = 'gpu'` → Planta eléctrica (todo lo existente hasta hoy queda
  así por el `DEFAULT`).
- `tipo_cobro = 'acu'` → Aire acondicionado.

Con esto, una misma combinación aerolínea + base puede tener **hasta dos**
tarifas: una `gpu` y una `acu`.

### 2.1. Lo nuevo (columna)

```sql
-- Agregar la columna tipo_cobro (todo lo existente hasta hoy es GPU)
ALTER TABLE `tarifas_gpu`
    ADD COLUMN `tipo_cobro` ENUM('gpu','acu') NOT NULL DEFAULT 'gpu'
        COMMENT 'gpu = Planta eléctrica, acu = Aire acondicionado'
        AFTER `base_id`;
```

### 2.2. Modificaciones sobre lo existente

```sql
-- El índice de búsqueda (airline_id, base_id) pasa a incluir el tipo,
-- ya que ahora puede haber una tarifa GPU y otra ACU para la misma
-- combinación aerolínea + base.
ALTER TABLE `tarifas_gpu`
    DROP INDEX `idx_tarifas_gpu_airline_base`,
    ADD INDEX `idx_tarifas_gpu_airline_base_tipo` (`airline_id`, `base_id`, `tipo_cobro`);

-- Registros existentes: dejarlos explícitamente como 'gpu' (ya quedan
-- así por el DEFAULT del ALTER anterior; este UPDATE es solo por
-- seguridad/idempotencia).
UPDATE `tarifas_gpu` SET `tipo_cobro` = 'gpu' WHERE `tipo_cobro` <> 'gpu';
```

---

## 3. Verificación post-migración

Después de correr ambas migraciones en producción, confirmar que la
estructura quedó correcta:

```sql
SHOW CREATE TABLE tarifas_gpu;

-- Debe verse: id, airline_id, base_id, tipo_cobro, primeros_minutos,
-- fraccion_minutos, created_at, updated_at — con FKs a airlines y bases,
-- y el índice idx_tarifas_gpu_airline_base_tipo (sin uq_tarifas_gpu_airline
-- ni idx_tarifas_gpu_airline_base, esos quedaron reemplazados).

SELECT id, airline_id, base_id, tipo_cobro, primeros_minutos, fraccion_minutos
FROM tarifas_gpu;

-- Todos los registros que ya existían antes de estas migraciones deben
-- quedar con base_id = NULL y tipo_cobro = 'gpu' (equivalen a "Todas las
-- bases" + "Planta Eléctrica", el comportamiento que tenían antes).
```

## 4. Qué habilitan estas migraciones en la app

- `/tarifas-cobros`: el formulario de crear/editar tarifa ahora pide
  **Tipo de Cobro** (Planta Eléctrica / Aire Acondicionado) y **Base**
  (o "Todas las bases"), además de la aerolínea.
- `/flight-services/create` y `/flight-services/edit/{id}`: el cálculo de
  **Fracciones ADC GPU** ya tenía en cuenta la tarifa de la aerolínea; ahora
  también tiene en cuenta la base seleccionada, y las **Fracciones Hora ACU**
  / **Fracciones por Fracción ACU** se calculan con la misma lógica a partir
  de la tarifa ACU configurada para esa aerolínea/base (antes eran fijas:
  1/0 según el switch "ACU" y bloques fijos de 15 minutos). Si no hay tarifa
  ACU configurada para la aerolínea/base seleccionada, se muestra un aviso y
  las fracciones quedan en 0.
