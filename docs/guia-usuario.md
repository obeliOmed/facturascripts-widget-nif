# WidgetNif — Guía de usuario

## ¿Qué hace este campo?

El campo de NIF/NIE/CIF valida automáticamente el documento de identificación fiscal español mientras lo escribes, y te avisa si tiene un error antes de guardar el formulario.

## Cómo usarlo

1. Escribe el NIF, NIE, CIF o pasaporte en el campo.
2. Al salir del campo (hacer clic en otro sitio), aparece un aviso a la derecha:
   - **✓ NIF** / **✓ NIE** / **✓ CIF** / **✓ PAS** (verde) — el documento es correcto.
   - **✗** (rojo) — el documento no es válido.
3. Puedes escribir con o sin guiones/espacios (`12345678Z`, `12345678-Z`, `12345678 Z` funcionan igual). El sistema lo normaliza automáticamente a mayúsculas.

## Qué tipos de documento acepta

| Tipo | Formato | Ejemplo |
|---|---|---|
| NIF | 8 dígitos + letra | `12345678Z` |
| NIE | Letra (X/Y/Z) + 7 dígitos + letra | `X1234567L` |
| CIF | Letra de entidad + 7 dígitos + letra o dígito de control | `B12345674` |
| Pasaporte | 6 a 12 caracteres alfanuméricos | `AB123456` |

## ⚠️ Importante sobre pasaportes

Los pasaportes **no tienen ningún control matemático** — a diferencia del NIF/NIE/CIF, España no dispone de un algoritmo público para verificar pasaportes extranjeros. Esto significa que **cualquier texto de 6 a 12 letras/números que no parezca un NIF/NIE/CIF español se acepta como pasaporte válido**, sin comprobar que sea real.

Si necesitas que el campo sea estricto (solo NIF/NIE/CIF de residentes españoles, sin aceptar "pasaportes"), pide a soporte que active la opción `allowPassport="false"` en ese formulario.

## Preguntas frecuentes

**¿Por qué me da error si el NIF es correcto?**
Revisa que la letra final coincida exactamente con el número — el sistema calcula la letra correcta a partir de los 8 dígitos. Un error de transcripción en cualquier dígito cambia la letra esperada.

**¿Puedo dejarlo en blanco?**
Sí, el campo vacío no da error de formato (la obligatoriedad del campo, si aplica, la controla el formulario, no este validador).

**¿Distingue mayúsculas/minúsculas?**
No — puedes escribir en minúscula, se convierte a mayúscula automáticamente.
