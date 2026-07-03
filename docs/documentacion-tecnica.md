# WidgetNif — Documentación técnica

## Arquitectura

```
Lib/Widget/WidgetNif.php   extends BaseWidget — resolución automática vía type="nif"
Lib/NifValidator.php       lógica pura, sin dependencias FS — reutilizable standalone
Assets/JS/nif-widget.js    espejo del validador PHP para feedback instantáneo (blur)
```

`WidgetNif` se auto-registra por convención FS: `ColumnItem.php` resuelve `type="nif"` → `\FacturaScripts\Dinamic\Lib\Widget\WidgetNif` sin tocar `Init.php`.

## Flujo de datos

1. **Cliente (JS)**: `nif-widget.js` valida en el evento `blur` y pinta ✓/✗ — feedback inmediato, **no bloquea el envío del formulario** (no hay `preventDefault()` en el submit, ni deshabilita el botón guardar).
2. **Servidor (PHP)**: `WidgetNif::processFormData()` normaliza (trim + uppercase) y guarda el valor tal cual — **no rechaza el guardado si es inválido**. El widget en sí NUNCA bloquea el guardado.
3. `NifValidator` es la única fuente de verdad — JS y PHP implementan el mismo algoritmo por separado (sin llamada AJAX), así que si tocas uno, hay que tocar el otro (`Lib/NifValidator.php` y `Assets/JS/nif-widget.js`).

### ⚠️ El bloqueo real de guardado es responsabilidad del modelo consumidor

Si el modelo que usa este widget **no** llama a `NifValidator::validate()` en su `test()` (ver ejemplo en `README.md`), el usuario verá ✗ en rojo, pulsará "Guardar", y el sistema **guardará el NIF inválido igualmente**. Esto no es un fallback silencioso deseable — es una responsabilidad de integración que cada plugin/formulario consumidor debe cumplir explícitamente:

```php
use FacturaScripts\Plugins\WidgetNif\Lib\NifValidator;

public function test(): bool
{
    if (!empty($this->nif) && !NifValidator::validate($this->nif)) {
        $this->toolBox()->log()->error('nif-validation-error');
        return false;
    }
    return parent::test();
}
```

Al auditar cualquier plugin que use `type="nif"`, comprobar SIEMPRE que su modelo tiene este guard — si no lo tiene, el campo es puramente decorativo.

### `detectType()` vs `validate()` — dos pasos separados

`detectType()` clasifica por **estructura** (regex), `validate()` comprueba la **matemática** del tipo detectado. Son pasos distintos y pueden discrepar: un valor con la ESTRUCTURA de un NIF pero letra de control incorrecta se detecta como `'nif'` y luego falla en `validateNif()` — NO cae a pasaporte ni a `'unknown'`.

Ejemplo: `12345678A`
1. `detectType()` → coincide con `^[0-9]{8}[A-Z]$` → devuelve `'nif'`.
2. `validate()` → llama a `validateNif()` → `12345678 % 23 = 14` → `TABLA[14] = 'Z'`. La letra escrita (`A`) no coincide con la esperada (`Z`) → `validateNif()` devuelve `false`.
3. Resultado final: `validate()` devuelve `false`. Nunca se reclasifica como pasaporte — una vez detectado como `'nif'` por estructura, solo puede validar o fallar como NIF.

## Algoritmos de validación

### NIF — módulo 23

```
letra_esperada = TABLA[número_8_dígitos % 23]
TABLA = "TRWAGMYFPDXBNJZSQVHLCKE"  (índices 0-22)
```
Excluye I, O, U, Ñ (no aparecen en la tabla — nunca son letra válida de NIF).

### NIE — prefijo sustituido + mismo algoritmo NIF

```
X → 0, Y → 1, Z → 2
número = prefijo_sustituido + 7_dígitos
letra_esperada = TABLA[número % 23]   (misma tabla que NIF)
```

### CIF — letra de entidad + dígito de control (módulo 10, Luhn-like)

```
Para cada uno de los 7 dígitos (posición 1-indexed):
  posición impar (1,3,5,7) → dígito × 2; si ≥10, sumar sus dígitos (equivalente a restar 9)
  posición par   (2,4,6)   → dígito tal cual
suma_total = Σ todo lo anterior
control_num = (10 - (suma_total % 10)) % 10
control_letra = "JABCDEFGHI"[control_num]
```

Letra de entidad determina si el control es letra, dígito, o cualquiera de los dos:

| Grupo | Entidades | Control |
|---|---|---|
| Siempre letra | P, Q, K, L, M, S | `control_letra` |
| Siempre dígito | A, B, E, H | `control_num` (como string) |
| Cualquiera | C, D, F, G, J, N, R, U, V, W | letra o dígito, ambos válidos |

Letras de entidad NO válidas (excluidas del regex): `I, O, T, X, Y, Z`.

### Pasaporte — sin checksum

```regex
^[A-Z0-9]{6,12}$
```
`validate()` devuelve `true` incondicionalmente si cuadra este patrón y no cuadra NIF/NIE/CIF antes. **No hay verificación real** — ver limitación conocida más abajo.

## Orden de detección (`detectType()`)

1. NIF (`^[0-9]{8}[A-Z]$`)
2. NIE (`^[XYZ][0-9]{7}[A-Z]$`)
3. CIF (`^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$`) — letra de entidad válida
4. **Guarda anti-falso-positivo**: si estructuralmente parece CIF (letra+7dígitos+control válido) pero con letra de entidad NO válida (p.ej. `O1234567E`), devuelve `'unknown'` en vez de colar como pasaporte — evita clasificar un typo de CIF como documento extranjero válido.
5. Pasaporte (`^[A-Z0-9]{6,12}$`)
6. `'unknown'` → `validate()` devuelve `false`

## Limitaciones conocidas

- **Pasaporte sin verificación real** (ver Sec anterior) — cualquier 6-12 alfanumérico no-NIF/NIE/CIF pasa por defecto. `NifValidator::validate($value, false)` rechaza pasaportes; el atributo XML `allowpassport="false"` en el widget solo cambia el aviso visual JS — **no bloquea el guardado por sí solo**, el modelo consumidor debe pasar `false` explícitamente en su propia llamada a `validate()` (FS no tiene puente automático entre atributos XML del widget y `model::test()`).
- **NIF/NIE/CIF no verifican existencia real** — solo la estructura matemática. Un NIF con dígitos inventados pero letra de control correcta pasa validación (imposible verificar existencia real sin consultar AEAT).
- **⚠️ La guarda anti-falso-positivo de CIF (Sec "Orden de detección", paso 4) puede rechazar pasaportes extranjeros legítimos.** Cualquier pasaporte real de exactamente 9 caracteres con la forma `[letra][7 dígitos][dígito o letra A-J]` cuya letra inicial sea I/O/T/X/Y/Z se clasifica como `'unknown'` (inválido) en vez de `'passport'`. La guarda se diseñó para evitar colar un CIF mal tecleado (p.ej. `O1234567E` en vez de `01234567E`... aunque los CIF no empiezan por dígito, el caso real es letra de entidad inválida) como si fuera un pasaporte siempre-válido — pero el efecto colateral es bloquear pasaportes reales que coincidan por casualidad con ese patrón. **Si el negocio opera con clientes internacionales, esto es un riesgo real, no solo teórico** — pendiente decidir si se mantiene, se relaja, o se hace configurable (ver `_SESSION-LOG.md` para la decisión pendiente).
- **Saneamiento insuficiente para copy-paste**: `normalize()` solo elimina espacio simple, guion (`-`) y punto (`.`). No elimina espacios de no separación (`&nbsp;`/` `), guiones largos (`—`/`–`) ni caracteres Unicode invisibles — habituales al copiar un NIF desde un PDF o email. Un NIF con estos caracteres "ocultos" fallará la validación sin que el usuario entienda por qué (visualmente parece correcto).

## Mantenimiento — riesgo de divergencia JS/PHP

`Lib/NifValidator.php` y `Assets/JS/nif-widget.js` implementan el MISMO algoritmo por duplicado (sin llamada AJAX, por rendimiento). No hay ningún mecanismo automático que los mantenga sincronizados. **Cualquier cambio en uno de los dos ficheros debe replicarse manualmente en el otro** — incluyendo cambios en `detectType()`, las tablas de letras, o las guardas anti-falso-positivo. Si se olvida, el síntoma es sutil: el badge de feedback dice ✓ pero el guardado real falla (o viceversa), y solo se detecta si alguien compara ambos ficheros línea a línea. Al revisar un PR que toque `NifValidator.php`, comprobar SIEMPRE si `nif-widget.js` necesita el mismo cambio.

## Tests

`Test/NifValidatorTest.php` — 53 casos: NIF válidos/inválidos, NIE X/Y/Z, CIF por cada grupo de entidad (siempre-letra/siempre-dígito/ambos), pasaportes, guardas anti-falso-positivo CIF-lookalike. Ejecutar: `phpunit --testsuite "Validator (standalone)"`.
