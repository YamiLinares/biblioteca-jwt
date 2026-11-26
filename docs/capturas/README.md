# Capturas de evidencia

Las capturas `login.png`, `registro.png`, `dashboard.png`, `catalogo.png`, `registro-libro.png`, `perfil.png` y `swagger.png` son capturas reales de Edge sobre la aplicación local. Para completar las evidencias de respuestas de API y pruebas, usa los pasos de abajo. Antes de guardar una imagen, redacta cualquier token, contraseña o dato personal.

| Archivo | URL y operación | Evidencia esperada |
|---|---|---|
| `login.png` | `http://127.0.0.1:8015/#/login` | Formulario de inicio de sesión |
| `registro.png` | `http://127.0.0.1:8015/#/register` | Registro y reglas de contraseña |
| `dashboard.png` | Inicia sesión y abre `#/dashboard` | Resumen calculado desde `/api/books` |
| `catalogo.png` | Navegación Catálogo | Títulos y existencias de la API |
| `registro-libro.png` | Catálogo → Registrar libro | Formulario para crear un libro |
| `edicion-libro.png` | Catálogo → Editar un título | Datos existentes y edición |
| `perfil.png` | Navegación Mi perfil | Perfil recibido desde `/api/auth/me` |
| `swagger.png` | `http://127.0.0.1:8015/docs` | Swagger UI y seguridad Bearer JWT |
| `login-exitoso.png` | Inicia sesión con una cuenta de prueba | Dashboard tras emitir JWT; oculta el token |
| `acceso-protegido.png` | Swagger: autoriza y ejecuta `GET /books` | HTTP 200 con Bearer token; no captures el valor del token |
| `acceso-denegado-401.png` | Ejecuta `GET /books` sin token | HTTP 401 y mensaje JSON |
| `token-invalidado.png` | Logout y posterior consulta `/auth/me` | Cierre exitoso y 401 posterior; oculta token |
| `pruebas-exitosas.png` | Ejecuta `php artisan test` en VS Code | Resumen real de pruebas aprobadas |

En Windows usa Recortes (`Win+Shift+S`) o `Ctrl+Shift+S` del navegador. Coloca cada PNG aquí con el nombre exacto de la tabla.
