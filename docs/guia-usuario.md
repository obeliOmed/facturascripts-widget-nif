# WidgetNif — Guía de usuario

## ¿Qué hace este campo?

El campo de NIF/NIE/CIF valida automáticamente el documento de identificación fiscal español mientras lo escribes, y te avisa con un aviso visual (✓/✗) si el formato tiene un error.

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

Los pasaportes extranjeros no siguen el algoritmo matemático español. Por ello, el sistema aceptará cualquier combinación de 6 a 12 caracteres alfanuméricos como un pasaporte válido, siempre que no coincida con la estructura de un NIF/NIE/CIF — sin comprobar que el pasaporte sea real.

Si tu negocio no trabaja con clientes/pacientes extranjeros y quieres que el campo sea estricto (solo NIF/NIE/CIF españoles), pide a soporte que active esa restricción en ese formulario concreto. Importante: esto solo cambia el aviso visual (✓/✗) — que el guardado se bloquee de verdad depende de que el desarrollador del formulario lo haya configurado también en el modelo (ver siguiente apartado).

## ⚠️ El aviso ✗/✓ es solo una ayuda visual, no un bloqueo garantizado

El aviso verde/rojo te ayuda a detectar errores mientras escribes, pero **por sí solo no impide guardar el formulario**. Que el guardado quede realmente bloqueado ante un NIF inválido depende de cómo esté configurado ESE formulario en concreto. Si detectas que se ha guardado un registro con un NIF/NIE/CIF que sabes que es incorrecto, repórtalo a soporte — es un formulario que necesita reforzarse, no un fallo que debas resolver tú.

## Preguntas frecuentes

**¿Por qué me da error si el NIF es correcto?**
Revisa que la letra final coincida exactamente con el número — el sistema calcula la letra correcta a partir de los 8 dígitos. Un error de transcripción en cualquier dígito cambia la letra esperada.

**¿Puedo dejarlo en blanco?**
Sí, el campo vacío no da error de formato (la obligatoriedad del campo, si aplica, la controla el formulario, no este validador).

**¿Distingue mayúsculas/minúsculas?**
No — puedes escribir en minúscula, se convierte a mayúscula automáticamente.
