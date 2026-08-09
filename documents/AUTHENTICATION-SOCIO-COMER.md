# Autenticación API — Socio Comercial

## Sky Balloons API

Esta documentación explica cómo autenticarse como **Socio Comercial** contra la API utilizando `curl`.

El acceso de Socio Comercial utiliza la tabla:

```text
socio_accesos
```

y los siguientes campos:

```text
socio_id
email
password
api_token
token_expires_at
ultimo_acceso
estado
```

---

## 1. Endpoint de autenticación

### Desarrollo local

```text
http://localhost:8000
```

### Producción

```text
https://api.skyballoons.cloud
```

### Login Socio Comercial

```text
POST /api/socios/login
```

URL completa en local:

```text
http://localhost:8000/api/socios/login
```

URL completa en producción:

```text
https://api.skyballoons.cloud/api/socios/login
```

---

# 2. Requisitos

La API espera las credenciales mediante JSON:

```json
{
    "email": "mda@skyballoons.mx",
    "password": "TU_PASSWORD"
}
```

Los encabezados requeridos son:

```http
Accept: application/json
Content-Type: application/json
```

> **Nota:** No incluir credenciales reales dentro de documentación pública o repositorios Git.

---

# 3. Login

## Request

```bash
curl -i -X POST http://localhost:8000/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mda@skyballoons.mx",
    "password": "TU_PASSWORD"
  }'
```

---

# 4. Login en producción

```bash
curl -i -X POST https://api.skyballoons.cloud/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mda@skyballoons.mx",
    "password": "TU_PASSWORD"
  }'
```

---

# 5. Respuesta exitosa

Si las credenciales son correctas y el socio tiene:

```text
estado = activo
```

la API devuelve:

```text
HTTP/1.1 200 OK
```

Respuesta:

```json
{
    "message": "Autenticación exitosa",
    "token": "TOKEN_GENERADO",
    "token_type": "Bearer",
    "expires_at": "2026-08-10T16:39:29.000000Z",
    "socio": {
        "id": 123,
        "email": "mda@skyballoons.mx"
    }
}
```

El campo:

```text
token
```

es el token que debe utilizar el cliente para realizar peticiones autenticadas.

---

# 6. Login mostrando solamente JSON

Para ocultar los encabezados HTTP:

```bash
curl -s -X POST http://localhost:8000/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mda@skyballoons.mx",
    "password": "TU_PASSWORD"
  }'
```

Si tienes `jq`:

```bash
curl -s -X POST http://localhost:8000/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mda@skyballoons.mx",
    "password": "TU_PASSWORD"
  }' | jq
```

---

# 7. Credenciales incorrectas

Si el email no existe:

```bash
curl -i -X POST http://localhost:8000/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "socio@ejemplo.com",
    "password": "password"
  }'
```

Respuesta:

```text
HTTP/1.1 401 Unauthorized
```

```json
{
    "message": "Credenciales incorrectas"
}
```

La misma respuesta se devuelve cuando la contraseña es incorrecta.

---

# 8. Socio inactivo

Si el registro tiene:

```text
estado = inactivo
```

la autenticación será rechazada.

Respuesta:

```text
HTTP/1.1 403 Forbidden
```

```json
{
    "message": "El acceso del socio no está activo",
    "estado": "inactivo"
}
```

---

# 9. Socio bloqueado

Si el registro tiene:

```text
estado = bloqueado
```

la API también rechazará el acceso:

```text
HTTP/1.1 403 Forbidden
```

```json
{
    "message": "El acceso del socio no está activo",
    "estado": "bloqueado"
}
```

---

# 10. Email requerido

Si no se envía `email`:

```bash
curl -i -X POST http://localhost:8000/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "password"
  }'
```

Respuesta:

```text
HTTP/1.1 422
```

```json
{
    "message": "Email y password son requeridos"
}
```

---

# 11. Password requerido

```bash
curl -i -X POST http://localhost:8000/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mda@skyballoons.mx"
  }'
```

Respuesta:

```text
HTTP/1.1 422
```

```json
{
    "message": "Email y password son requeridos"
}
```

---

# 12. Token de autenticación

Después de un login exitoso, la API genera un token aleatorio.

Ejemplo:

```text
TOKEN_GENERADO
```

El cliente debe conservar este token.

El token se utiliza mediante:

```http
Authorization: Bearer TOKEN_GENERADO
```

Ejemplo:

```bash
curl -X GET http://localhost:8000/api/socios/perfil \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_GENERADO"
```

En producción:

```bash
curl -X GET https://api.skyballoons.cloud/api/socios/perfil \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_GENERADO"
```

> La ruta `/api/socios/perfil` requiere que el middleware de autenticación de socios esté implementado.

---

# 13. Expiración del token

El token generado actualmente tiene una duración de:

```text
24 horas
```

La fecha de expiración se almacena en:

```text
socio_accesos.token_expires_at
```

Ejemplo:

```text
2026-08-10 16:39:29
```

Una vez superada esta fecha, el token debe considerarse inválido.

---

# 14. Último acceso

Cada login exitoso actualiza:

```text
ultimo_acceso
```

Ejemplo:

```sql
SELECT
    socio_id,
    email,
    ultimo_acceso
FROM socio_accesos
WHERE email = 'mda@skyballoons.mx';
```

Resultado:

```text
+----------+----------------------+---------------------+
| socio_id | email                | ultimo_acceso       |
+----------+----------------------+---------------------+
|      123 | mda@skyballoons.mx   | 2026-08-09 16:39:29 |
+----------+----------------------+---------------------+
```

---

# 15. Token almacenado en la base de datos

Por seguridad, el token que recibe el cliente:

```text
TOKEN_GENERADO
```

no debe almacenarse directamente en `socio_accesos`.

La API almacena:

```text
SHA-256(TOKEN_GENERADO)
```

en:

```text
api_token
```

Por lo tanto:

```text
Cliente
   │
   │ TOKEN
   ▼
API
   │
   │ hash SHA-256
   ▼
MariaDB
   │
   └── socio_accesos.api_token
```

El cliente conserva el token original.

---

# 16. Flujo completo

```text
┌──────────────────────────────────┐
│       Socio Comercial            │
└───────────────┬──────────────────┘
                │
                │ email + password
                ▼
┌──────────────────────────────────┐
│ POST /api/socios/login           │
└───────────────┬──────────────────┘
                │
                ▼
┌──────────────────────────────────┐
│ Buscar socio_accesos             │
│ por email                        │
└───────────────┬──────────────────┘
                │
                ▼
        ┌───────────────┐
        │ estado activo │
        └───────┬───────┘
                │
                ▼
┌──────────────────────────────────┐
│ Verificar password                │
│ Hash::check()                     │
└───────────────┬──────────────────┘
                │
                ▼
┌──────────────────────────────────┐
│ Generar token                    │
│ random_bytes()                   │
└───────────────┬──────────────────┘
                │
                ├──► Guardar SHA-256
                │
                ├──► token_expires_at
                │
                └──► ultimo_acceso
                │
                ▼
┌──────────────────────────────────┐
│ Devolver Bearer Token            │
└───────────────┬──────────────────┘
                │
                ▼
      Authorization: Bearer TOKEN
                │
                ▼
┌──────────────────────────────────┐
│ Endpoint protegido de socio      │
└──────────────────────────────────┘
```

---

# 17. Ejemplo completo

### Paso 1 — Login

```bash
RESPONSE=$(curl -s -X POST http://localhost:8000/api/socios/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mda@skyballoons.mx",
    "password": "TU_PASSWORD"
  }')

echo "$RESPONSE" | jq
```

### Paso 2 — Obtener token

```bash
TOKEN=$(echo "$RESPONSE" | jq -r '.token')

echo "$TOKEN"
```

### Paso 3 — Utilizar token

```bash
curl -i http://localhost:8000/api/socios/perfil \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

---

# 18. Diferencia entre Administrador y Socio Comercial

La API maneja dos tipos de acceso:

| Tipo | Tabla | Login |
|---|---|---|
| Administrador | `users` | `/api/login` |
| Socio Comercial | `socio_accesos` | `/api/socios/login` |

### Administrador

```text
POST /api/login
```

### Socio Comercial

```text
POST /api/socios/login
```

Los dos sistemas de autenticación son independientes.

---

# 19. Seguridad

Para producción:

- Utilizar siempre HTTPS.
- No enviar contraseñas mediante URL.
- No almacenar contraseñas en texto plano.
- Utilizar `Hash::check()` para verificar contraseñas.
- No devolver `password` en respuestas JSON.
- Almacenar el hash del `api_token`.
- Utilizar tokens con expiración.
- Validar `estado` antes de autenticar.
- Actualizar `ultimo_acceso`.
- Implementar rate limiting para `/api/socios/login`.
- No registrar contraseñas en logs.
- No incluir contraseñas reales en repositorios Git.
- No compartir tokens Bearer.
- Revocar el token cuando el socio cierre sesión.

---

# 20. Estado actual

| Funcionalidad | Estado |
|---|---:|
| Lumen 11 | ✅ |
| PHP 8.4 | ✅ |
| Eloquent | ✅ |
| Modelo `SocioAcceso` | ✅ |
| Login Socio Comercial | ✅ |
| Validación email/password | ✅ |
| Validación `estado` | ✅ |
| Estado `activo` | ✅ |
| Estado `inactivo` | ✅ |
| Estado `bloqueado` | ✅ |
| Generación de token | ✅ |
| Token Bearer | ✅ |
| Expiración 24 horas | ✅ |
| `ultimo_acceso` | ✅ |
| Token almacenado como SHA-256 | ✅ |
| Middleware Bearer | ⏳ |
| Endpoint `/api/socios/perfil` protegido | ⏳ |
| Logout / revocación | ⏳ |
| Refresh Token | ⏳ |

---

## Resumen

El flujo de Socio Comercial es:

```text
POST /api/socios/login
        │
        │ email + password
        ▼
   socio_accesos
        │
        ▼
   estado = activo
        │
        ▼
    password OK
        │
        ▼
    generar token
        │
        ▼
  Bearer Token
        │
        ▼
 /api/socios/*
```

La autenticación de administrador y Socio Comercial permanece separada para permitir posteriormente aplicar **roles, permisos y endpoints diferentes** a cada tipo de usuario.