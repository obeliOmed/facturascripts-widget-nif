# WidgetNif — Spanish NIF/NIE/CIF validator for FacturaScripts

A free FacturaScripts community plugin that adds a `type="nif"` form widget with real-time
NIF / NIE / CIF validation and visual feedback.

---

## Features

- Validates **NIF** (8 digits + mod-23 check letter)
- Validates **NIE** (X/Y/Z prefix + 7 digits + check letter)
- Validates **CIF** (all entity types A–W with correct control char)
- **Passport** — pattern-based detection (no algorithmic check)
- **Normalizes** input on submit: trim + uppercase + strip spaces/dashes
- **Client-side badge feedback** on blur (✓ green / ✗ red) — vanilla JS, no dependencies
- **Server-side validation** via `NifValidator::validate()` — call from your `model::test()`

---

## Installation

1. Download this plugin and extract it to your FacturaScripts `Plugins/WidgetNif/` directory.
2. Go to **Admin → Plugins** and install **WidgetNif**.

---

## Usage in XMLView

```xml
<column name="nif" numcolumns="4" title="NIF/NIE/CIF">
    <widget type="nif" fieldname="nif" />
</column>
```

## Server-side validation in your model

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

## Restricting passports (no algorithmic check exists for them)

By default, any 6-12 alphanumeric value that doesn't match NIF/NIE/CIF is accepted as a
passport with no verification. To disable this in the widget's **visual feedback only**:

```xml
<widget type="nif" fieldname="nif" allowpassport="false" />
```

This does **not** reach your model automatically — FacturaScripts has no bridge from
XMLView widget attributes to model validation. To actually reject passports on save, pass
the flag explicitly in your own `test()`:

```php
if (!empty($this->nif) && !NifValidator::validate($this->nif, false)) {
    $this->toolBox()->log()->error('nif-validation-error');
    return false;
}
```

---

## Requirements

- FacturaScripts 2024.1 or higher
- PHP 8.0+

---

## License

GPL-3.0-only — see [LICENSE](LICENSE).

---

## Credits

Built by the **[obeliOmed](https://obeliomed.com)** team — open-source SaaS for medical clinics on FacturaScripts.

Contributions welcome via GitHub issues and pull requests.
