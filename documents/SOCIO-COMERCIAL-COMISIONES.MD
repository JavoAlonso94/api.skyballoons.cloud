Documentación de Pruebas API - Comisiones
📋 Información General
Endpoint Base: https://api.skyballoons.cloud/api/comisiones
Método: GET
Autenticación: Bearer Token
Formato de Respuesta: JSON

🔑 Token de Prueba
bash
TOKEN="dc79a8955d0d92689bfedc5a90cd05904cd363d48580621ad24dd2b7d8f734efee3e98ca0adc154b"
📝 Casos de Prueba
1. ✅ Consulta con Token Válido
Descripción: Consulta las comisiones del socio autenticado.

Comando:

bash
curl -X GET "https://api.skyballoons.cloud/api/comisiones" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer dc79a8955d0d92689bfedc5a90cd05904cd363d48580621ad24dd2b7d8f734efee3e98ca0adc154b"
Respuesta Esperada:

json
{
  "status": "success",
  "socio_autenticado": {
    "id": 18,
    "email": "prueba@skyballoons.cloud"
  },
  "filtros": {
    "socio_id": 18,
    "estado": null,
    "fecha_inicio": null,
    "fecha_fin": null,
    "pedido_venta_id": null
  },
  "resumen": {
    "sumatoria_comisiones": 1500.50,
    "total_registros": 25
  },
  "data": [
    {
      "id": 101,
      "socio_comercial_id": 18,
      "socio_nombre": "Socio de Prueba",
      "pedido_codigo": 1001,
      "tipo": "porcentaje",
      "valor_configurado": "10%",
      "base_calculo": "1,000.00",
      "monto_comision": "100.00",
      "estado": "pendiente",
      "fecha_generacion": "19/08/2026 10:30",
      "fecha_pago": "-",
      "usuario_pago_nombre": "-",
      "observaciones": "",
      "configuracion": "porcentaje (10)",
      "estado_badge": "<span class=\"badge bg-warning text-dark\">Pendiente</span>",
      "acciones": "<button class=\"btn btn-sm btn-success btn-pagar-comision\" data-id=\"101\">...</button>"
    }
  ]
}
Resultado Esperado: HTTP 200 OK

2. 🔒 Intento de Ver Comisiones de Otro Socio
Descripción: Intenta acceder a comisiones de otro socio (ID=5) pero el middleware forzará el ID del socio autenticado.

Comando:

bash
curl -X GET "https://api.skyballoons.cloud/api/comisiones?socio_id=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer dc79a8955d0d92689bfedc5a90cd05904cd363d48580621ad24dd2b7d8f734efee3e98ca0adc154b"
Respuesta Esperada:

json
{
  "status": "success",
  "socio_autenticado": {
    "id": 18,
    "email": "prueba@skyballoons.cloud"
  },
  "filtros": {
    "socio_id": 18,
    "estado": null,
    "fecha_inicio": null,
    "fecha_fin": null,
    "pedido_venta_id": null
  },
  "resumen": {
    "sumatoria_comisiones": 1500.50,
    "total_registros": 25
  },
  "data": [...]
}
⚠️ Nota Importante: Aunque se envía socio_id=5, el sistema retorna las comisiones del socio ID=18 (autenticado).

Resultado Esperado: HTTP 200 OK

3. 🔍 Consulta con Filtros Adicionales
Descripción: Consulta comisiones con filtros de estado y rango de fechas.

Comando:

bash
curl -X GET "https://api.skyballoons.cloud/api/comisiones?estado=pendiente&fecha_inicio=2026-08-01&fecha_fin=2026-08-31" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer dc79a8955d0d92689bfedc5a90cd05904cd363d48580621ad24dd2b7d8f734efee3e98ca0adc154b"
Respuesta Esperada:

json
{
  "status": "success",
  "socio_autenticado": {
    "id": 18,
    "email": "prueba@skyballoons.cloud"
  },
  "filtros": {
    "socio_id": 18,
    "estado": "pendiente",
    "fecha_inicio": "2026-08-01",
    "fecha_fin": "2026-08-31",
    "pedido_venta_id": null
  },
  "resumen": {
    "sumatoria_comisiones": 450.75,
    "total_registros": 8,
    "total_pendiente": 450.75,
    "total_pagada": 0,
    "total_cancelada": 0
  },
  "data": [
    {
      "id": 105,
      "socio_comercial_id": 18,
      "estado": "pendiente",
      "monto_comision": "75.50",
      "fecha_generacion": "15/08/2026 14:20",
      ...
    }
  ]
}
Resultado Esperado: HTTP 200 OK

4. ❌ Consulta Sin Token
Descripción: Intenta acceder al endpoint sin proporcionar token de autenticación.

Comando:

bash
curl -X GET "https://api.skyballoons.cloud/api/comisiones" \
  -H "Accept: application/json"
Respuesta Esperada:

json
{
  "status": "error",
  "message": "Token no proporcionado"
}
Resultado Esperado: HTTP 401 Unauthorized

5. ❌ Consulta con Token Inválido
Descripción: Intenta acceder con un token que no existe o ha expirado.

Comando:

bash
curl -X GET "https://api.skyballoons.cloud/api/comisiones" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer token_invalido"
Respuesta Esperada:

json
{
  "status": "error",
  "message": "Token inválido o sesión expirada"
}
Resultado Esperado: HTTP 401 Unauthorized

📊 Resumen de Resultados
#	Caso de Prueba	Método	Token	Código Esperado	Estado
1	Consulta normal	GET	✅ Válido	200 OK	✅
2	Acceso a otro socio	GET	✅ Válido	200 OK (forzado)	✅
3	Con filtros	GET	✅ Válido	200 OK	✅
4	Sin token	GET	❌ Ninguno	401 Unauthorized	✅
5	Token inválido	GET	❌ Inválido	401 Unauthorized	✅
🔧 Script de Prueba Completo
bash
#!/bin/bash

# Configuración
BASE_URL="https://api.skyballoons.cloud"
TOKEN="dc79a8955d0d92689bfedc5a90cd05904cd363d48580621ad24dd2b7d8f734efee3e98ca0adc154b"

# Función para mostrar resultados formateados
show_result() {
    echo "=========================================="
    echo "PRUEBA: $1"
    echo "=========================================="
    echo "COMANDO:"
    echo "$2"
    echo ""
    echo "RESPUESTA:"
    eval "$2"
    echo ""
    echo ""
}

# Prueba 1: Consulta normal
show_result "Consulta normal con token" \
"curl -s -X GET '$BASE_URL/api/comisiones' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer $TOKEN' | jq '.'"

# Prueba 2: Intento de acceso a otro socio
show_result "Intento de ver comisiones de otro socio" \
"curl -s -X GET '$BASE_URL/api/comisiones?socio_id=5' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer $TOKEN' | jq '.filtros, .socio_autenticado'"

# Prueba 3: Con filtros
show_result "Consulta con filtros" \
"curl -s -X GET '$BASE_URL/api/comisiones?estado=pendiente&fecha_inicio=2026-08-01&fecha_fin=2026-08-31' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer $TOKEN' | jq '.resumen'"

# Prueba 4: Sin token
show_result "Consulta sin token" \
"curl -s -X GET '$BASE_URL/api/comisiones' \
  -H 'Accept: application/json'"

# Prueba 5: Token inválido
show_result "Consulta con token inválido" \
"curl -s -X GET '$BASE_URL/api/comisiones' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer token_invalido'"
🚀 Ejecutar Script de Pruebas
Para guardar y ejecutar el script:

bash
# Guardar el script
cat > test_comisiones.sh << 'EOF'
#!/bin/bash

BASE_URL="https://api.skyballoons.cloud"
TOKEN="dc79a8955d0d92689bfedc5a90cd05904cd363d48580621ad24dd2b7d8f734efee3e98ca0adc154b"

echo "=== PRUEBA 1: Consulta normal ==="
curl -s -X GET "$BASE_URL/api/comisiones" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq '.'

echo -e "\n=== PRUEBA 2: Intento de ver otro socio ==="
curl -s -X GET "$BASE_URL/api/comisiones?socio_id=5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq '.socio_autenticado, .filtros'

echo -e "\n=== PRUEBA 3: Con filtros ==="
curl -s -X GET "$BASE_URL/api/comisiones?estado=pendiente&fecha_inicio=2026-08-01&fecha_fin=2026-08-31" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq '.resumen'

echo -e "\n=== PRUEBA 4: Sin token ==="
curl -s -X GET "$BASE_URL/api/comisiones" \
  -H "Accept: application/json"

echo -e "\n=== PRUEBA 5: Token inválido ==="
curl -s -X GET "$BASE_URL/api/comisiones" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer token_invalido"

echo -e "\n"
EOF

# Dar permisos de ejecución
chmod +x test_comisiones.sh

# Ejecutar el script
./test_comisiones.sh