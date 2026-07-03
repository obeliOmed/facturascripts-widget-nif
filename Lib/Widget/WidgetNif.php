<?php
declare(strict_types=1);

namespace FacturaScripts\Plugins\WidgetNif\Lib\Widget;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\Widget\BaseWidget;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\WidgetNif\Lib\NifValidator;

/**
 * FacturaScripts widget for Spanish fiscal identification numbers (NIF/NIE/CIF).
 *
 * Usage in XMLView:
 *   <column name="nif"><widget type="nif" fieldname="nif" /></column>
 *
 * The widget normalizes input (trim + uppercase + strip spaces/dashes).
 * Validation (reject invalid values) must be done in the consumer model's test() method
 * using NifValidator::validate().
 *
 * Auto-discovered by FacturaScripts: type="nif" resolves to this class via ColumnItem.php.
 */
class WidgetNif extends BaseWidget
{
    /**
     * XML attribute "allowpassport" (default true). Set allowpassport="false" to
     * make the client-side ✓/✗ feedback treat passport-shaped values as invalid.
     *
     * IMPORTANT: this ONLY affects the visual JS feedback. It does not, and
     * cannot, reach the consumer model's test() automatically — FacturaScripts
     * has no bridge from XMLView widget attributes to model validation. If you
     * need this enforced server-side, call
     * NifValidator::validate($this->nif, false) explicitly in your model.
     */
    private bool $allowPassport = true;

    public function __construct($data)
    {
        parent::__construct($data);
        $this->allowPassport = !isset($data['allowpassport'])
            || strtolower((string)$data['allowpassport']) !== 'false';
    }

    protected function assets(): void
    {
        $route = Tools::config('route');
        AssetManager::addCss($route . '/Plugins/WidgetNif/Assets/CSS/nif-widget.css');
        AssetManager::addJs($route . '/Plugins/WidgetNif/Assets/JS/nif-widget.js');
    }

    protected function inputHtml($type = 'text', $extraClass = ''): string
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        $value = $this->escapeHtml((string)($this->value ?? ''));

        // data-nif-validate triggers client-side feedback (✓/✗ badge on blur).
        // autocomplete="off" — fiscal IDs should not be auto-filled by browsers.
        // maxlength="20" — enough room for spaced/dashed input before normalization.
        $input = '<input type="text"'
            . ' name="' . $this->fieldname . '"'
            . ' value="' . $value . '"'
            . ' class="' . $class . '"'
            . ' maxlength="20"'
            . ' autocomplete="off"'
            . ' data-nif-validate="1"'
            . ' data-nif-allow-passport="' . ($this->allowPassport ? 'true' : 'false') . '"'
            . $this->inputHtmlExtraParams()
            . '/>';

        // Wrap in input-group so JS badge appends cleanly as input-group-text sibling.
        return '<div class="input-group">' . $input . '</div>';
    }

    public function processFormData(&$model, $request): void
    {
        $raw = $request->request->get($this->fieldname, '');
        if (empty($raw)) {
            $model->{$this->fieldname} = null;
            return;
        }

        // Normalize: uppercase + trim + strip spaces, dashes, dots.
        // Validation is the consumer model's responsibility via NifValidator::validate().
        $model->{$this->fieldname} = NifValidator::normalize((string)$raw);
    }
}
