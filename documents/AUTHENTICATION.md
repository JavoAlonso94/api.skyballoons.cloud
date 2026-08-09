# Autenticación API

## Sky Balloons API

Esta documentación explica cómo autenticarse contra la API utilizando `curl`.

**Base URL local:**

```text
http://localhost:8000
```

**Endpoint de autenticación:**

```text
POST /api/login
```

---

## 1. Requisitos

La API espera las credenciales mediante JSON:

```json
{
    "email": "admin@admin.com",
    "password": "admin"
}
```

Los encabezados requeridos son:

```http
Accept: application/json
Content-Type: application/json
```

---

# 2. Login

## Request

```bash
curl -i -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@admin.com",
    "password": "admin"
  }'
```

## Respuesta exitosa

HTTP:

```text
HTTP/1.1 200 OK
```

Respuesta:

```json
{
    "message": "Autenticación exitosa",
    "user": {
        "id": 2,
        "name": "Administrador",
        "email": "admin@admin.com",
        "avatar": "avatars/XCgK66KjyIz3ZbEgE0SCUdw8cz42DZ2KKNt1WqBT.jpg",
        "email_verified_at": null,
        "created_at": "2026-06-26T19:51:53.000000Z",
        "updated_at": "2026-08-07T20:05:07.000000Z"
    }
}
```

---

# 3. Login mostrando solamente JSON

Para evitar los encabezados HTTP:

```bash
curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@admin.com",
    "password": "admin"
  }'
```

Si tienes `jq` instalado:

```bash
curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@admin.com",
    "password": "admin"
  }' | jq
```

Resultado:

```json
{
  "message": "Autenticación exitosa",
  "user": {
    "id": 2,
    "name": "Administrador",
    "email": "admin@admin.com",
    "avatar": "avatars/XCgK66KjyIz3ZbEgE0SCUdw8cz42DZ2KKNt1WqBT.jpg",
    "email_verified_at": null,
    "created_at": "2026-06-26T19:51:53.000000Z",
    "updated_at": "2026-08-07T20:05:07.000000Z"
  }
}
```

---

# 4. Credenciales incorrectas

Si se proporciona un usuario inexistente:

```bash
curl -i -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@ejemplo.com",
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

Lo mismo ocurre cuando el usuario existe pero la contraseña es incorrecta.

---

# 5. Email requerido

Si no se proporciona `email`:

```bash
curl -i -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "admin"
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

# 6. Password requerido

```bash
curl -i -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@admin.com"
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

# 7. Probar la API completa

## Endpoint principal

```bash
curl -i http://localhost:8000/
```

Respuesta esperada:

```text
HTTP/1.1 200 OK
```

Por ejemplo:

```json
{
    "app": "Sky Balloons API",
    "version": "Laravel Lumen (11.x)"
}
```

---

# 8. Prueba rápida de autenticación

Puedes ejecutar directamente:

```bash
curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@admin.com","password":"admin"}'
```

Una respuesta HTTP `200` significa que las credenciales fueron validadas correctamente.

---

# 9. Prueba desde producción

Cuando la API esté publicada, sustituye:

```text
http://localhost:8000
```

por:

```text
https://api.skyballoons.cloud
```

Por ejemplo:

```bash
curl -i -X POST https://api.skyballoons.cloud/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@admin.com",
    "password": "admin"
  }'
```

---

# 10. Importante: estado actual de autenticación

Actualmente el endpoint `/api/login` **valida las credenciales**, pero todavía **no genera un token de acceso**.

Por lo tanto, actualmente:

```text
POST /api/login
        │
        ▼
Validar email/password
        │
        ▼
Usuario encontrado
        │
        ▼
200 OK
```

Todavía no existe:

```text
Authorization: Bearer TOKEN
```

ni:

```text
POST /api/logout
POST /api/refresh
GET  /api/user
```

protegidos mediante un token.

---

# 11. Próxima implementación recomendada

Para utilizar esta API desde el ERP, frontend, aplicaciones móviles u otros servicios, se recomienda implementar autenticación mediante **Bearer Token**.

El flujo final sería:

```text
┌───────────────────────┐
│ POST /api/login       │
│ email + password      │
└───────────┬───────────┘
            │
            ▼
┌───────────────────────┐
│ Validar usuario       │
└───────────┬───────────┘
            │
            ▼
┌───────────────────────┐
│ Generar Access Token  │
└───────────┬───────────┘
            │
            ▼
┌───────────────────────┐
│ Cliente recibe token  │
└───────────┬───────────┘
            │
            ▼
Authorization: Bearer TOKEN
            │
            ▼
┌───────────────────────┐
│ Endpoint protegido    │
└───────────────────────┘
```

Ejemplo del flujo futuro:

```bash
curl -X POST https://api.skyballoons.cloud/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@admin.com",
    "password": "admin"
  }'
```

Respuesta futura:

```json
{
    "message": "Autenticación exitosa",
    "token": "TOKEN_GENERADO",
    "token_type": "Bearer",
    "user": {
        "id": 2,
        "name": "Administrador",
        "email": "admin@admin.com"
    }
}
```

Posteriormente:

```bash
curl -X GET https://api.skyballoons.cloud/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_GENERADO"
```

---

# 12. Seguridad

Nunca utilizar credenciales reales en documentación pública.

Para producción:

* Utilizar siempre `HTTPS`.
* No enviar contraseñas mediante URL/query string.
* No registrar contraseñas en logs.
* Mantener `password` oculto mediante `$hidden`.
* Utilizar tokens con expiración.
* No almacenar tokens en texto plano si el diseño de autenticación permite almacenarlos hasheados.
* Utilizar `Authorization: Bearer`.
* Revocar tokens cuando corresponda.
* Limitar intentos de login.
* Implementar rate limiting.
* No devolver información que permita enumerar usuarios.

## Estado actual

| Funcionalidad          | Estado |
| ---------------------- | ------ |
| Lumen 11               | ✅      |
| PHP 8.4                | ✅      |
| Eloquent               | ✅      |
| Login                  | ✅      |
| Validación de password | ✅      |
| HTTP 401               | ✅      |
| HTTP 422               | ✅      |
| Ocultar password       | ✅      |
| Bearer Token           | ⏳      |
| JWT / Access Token     | ⏳      |
| Refresh Token          | ⏳      |
| Logout                 | ⏳      |
| Rutas protegidas       | ⏳      |
