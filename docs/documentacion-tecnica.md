# WidgetNif — Documentación técnica

## Arquitectura

```
Lib/Widget/WidgetNif.php   extends BaseWidget — resolución automática vía type="nif"
Lib/NifValidator.php       lógica pura, sin dependencias FS — reutilizable standalone
Assets/JS/nif-widget.js    espejo del validador PHP para feedback instantáneo (blur)
```

`WidgetNif` se auto-registra por convención FS: `ColumnItem.php` resuelve `type="nif"` → `\FacturaScripts\Dinamic\Lib\Widget\WidgetNif` sin tocar `Init.php`.

## Flujo de datos

1. **Cliente (JS)**: `nif-widget.js` valida en el evento `blur` y pinta ✓/✗ — feedback inmediato, no bloquea el envío del formulario.
2. **Servidor (PHP)**: `WidgetNif::processFormData()` normaliza (trim + uppercase) y guarda el valor tal cual — **no rechaza el guardado si es inválido** (el rechazo real corresponde a `model::test()` del modelo consumidor, que debe llamar a `NifValidator::validate()` si quiere bloquear guardado).
3. `NifValidator` es la única fuente de verdad — JS y PHP implementan el mismo algoritmo por separado (sin llamada AJAX), así que si tocas uno, hay que tocar el otro (`Lib/NifValidator.php` y `Assets/JS/nif-widget.js`).

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

- **Pasaporte sin verificación real** (ver Sec anterior) — cualquier 6-12 alfanumérico no-NIF/NIE/CIF pasa. Mitigación disponible: atributo XML `allowPassport="false"` (pendiente de implementar si se solicita — actualmente el fallback está siempre activo).
- **NIF/NIE/CIF no verifican existencia real** — solo la estructura matemática. Un NIF con dígitos inventados pero letra de control correcta pasa validación (imposible verificar existencia real sin consultar AEAT).
- Normalización elimina espacios/guiones/puntos pero no otros caracteres especiales — un NIF con caracteres Unicode raros no se sanea.

## Tests

`Test/NifValidatorTest.php` — 53 casos: NIF válidos/inválidos, NIE X/Y/Z, CIF por cada grupo de entidad (siempre-letra/siempre-dígito/ambos), pasaportes, guardas anti-falso-positivo CIF-lookalike. Ejecutar: `phpunit --testsuite "Validator (standalone)"`.
