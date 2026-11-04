# Instalación de Nexus ERP para un cliente nuevo

Guía para poner en marcha Nexus a un cliente nuevo (venta o renta) en nuestro servidor.

**Modelo:** cada cliente tiene **su propia instalación**: su contenedor, su `.env`, su base de datos y su carpeta de archivos en Contabo. Nada se comparte entre clientes. Dentro de su instalación, el cliente puede tener varias empresas, cada una con sus sucursales y agencias.

**Siempre se crea el administrador del sistema:** el instalador crea un usuario con el rol **Administrador** en la empresa principal. Ese usuario **ve y administra todo**: todas las empresas (también las que se creen después), la configuración del sistema, los usuarios y los permisos. Si algo impidiera que quede así, el instalador se detiene sin instalar nada.

Archivos de esta carpeta:

| Archivo | Para qué |
|---|---|
| `README.md` | Esta guía |
| `env-cliente.example` | Plantilla del `.env` de cada cliente |
| `docker-compose.cliente.yml` | Contenedor de un cliente (es igual para todos) |

---

## 0. Una sola vez (no por cliente)

### 0.1 Plantilla de catálogos
Una empresa nueva necesita sus catálogos para funcionar: conceptos de nómina (IGSS, ISR, bonificación…), roles con sus permisos, departamentos y cargos, cuentas contables, centros de costo, líneas de negocio, etapas del embudo, SLA y categorías de tickets, etc. Se toman de una **empresa modelo** de nuestra instalación:

```bash
php artisan nexus:exportar-plantilla --empresa=1
```

Genera `database/seeders/datos/plantilla-empresa.json`, que va en el repositorio y entra en la imagen.

- Solo lleva definiciones: **nunca** clientes, empleados, facturas ni series.
- **Revisarla** antes de subirla: que no traiga nada propio de un cliente (nombres en plantillas de correo, cuentas contables muy específicas…).
- Volver a exportarla cuando se mejoren los catálogos modelo.

### 0.2 Imagen de Docker
Una sola imagen sirve para todos los clientes. Se construye en el servidor, desde el repositorio:

```bash
cd /srv/nexus/codigo && git pull
docker build -t sistema-nexuserp-php:latest .
```

(Opcional: etiquetar también con la fecha, `-t sistema-nexuserp-php:2026-11-04`, para poder volver a una versión anterior.)

### 0.3 Instalación original
La instalación que ya está en producción guardaba sus archivos en la raíz del bucket. En su `.env` debe ir:

```
CONTABO_USAR_RAIZ=true
```

Sin esa línea, desde esta versión **no podrá subir archivos**. No se le debe poner `CONTABO_CARPETA`: sus documentos privados quedaron guardados sin carpeta y dejarían de abrirse.

---

## 1. Datos que pedir al cliente

- Nombre comercial y razón social de la primera empresa
- NIT y país
- Nombre del grupo o consorcio (si tiene varias empresas)
- Nombre y correo de quien será el **administrador del sistema**
- Subdominio deseado (p. ej. `acme.tudominio.com`)
- Correo saliente (SMTP), si quiere que los avisos salgan con su dominio

Definir también:
- **Nombre corto** del cliente, en minúsculas y sin espacios: `acme`. Se usa en el contenedor, la BD y la carpeta.
- **Puerto local** libre: 8101, 8102, … (llevar la lista en la tabla del final).

## 2. Base de datos

En el servidor MySQL/MariaDB (con un usuario que pueda crear bases):

```sql
CREATE DATABASE nexus_acme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nexus_acme'@'%' IDENTIFIED BY '<contraseña-larga-y-única>';
GRANT ALL PRIVILEGES ON nexus_acme.* TO 'nexus_acme'@'%';
FLUSH PRIVILEGES;
```

Un usuario por cliente, con permiso **solo** sobre su base.

## 3. Carpeta del cliente en el servidor

```bash
mkdir -p /srv/nexus/clientes/acme && cd /srv/nexus/clientes/acme
cp /srv/nexus/codigo/docs/instalacion-clientes/docker-compose.cliente.yml docker-compose.yml
cp /srv/nexus/codigo/docs/instalacion-clientes/env-cliente.example .env
chmod 600 .env
```

Llenar el `.env` (todo lo que está entre `<…>`):

- `NEXUS_CLIENTE=acme` y `NEXUS_PUERTO=8101`
- `APP_KEY`: **obligatoria y única**. Generarla con:
  ```bash
  docker run --rm sistema-nexuserp-php php artisan key:generate --show
  ```
  Nunca cambiarla después: todos perderían la sesión y la verificación en dos pasos.
- `APP_URL=https://acme.tudominio.com`
- `DB_*` con la base y el usuario del paso 2
- `CONTABO_CARPETA=clientes/acme`: **obligatoria**. Sin ella el sistema no sube archivos, para que los de un cliente nunca queden junto a los de otro.
- `MAIL_*` y `TRUSTED_PROXIES`

## 4. Levantar el contenedor

```bash
cd /srv/nexus/clientes/acme
docker compose up -d
docker compose logs -f app
```

Con `RUN_MIGRATIONS=true`, el primer arranque crea todas las tablas en la BD vacía (carga `database/schema/mysql-schema.sql` y luego las migraciones nuevas). En los logs debe verse `[entrypoint] Listo`.

## 5. Instalar

```bash
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan nexus:instalar \
    --empresa="Seguridad Acme" --razon-social="Seguridad Acme, S.A." --nit=1234567-8 --pais=GT \
    --consorcio="Grupo Acme" --usuario=admin --correo=admin@acme.com --nombre="Juan Pérez"
```

- `db:seed` carga los catálogos del sistema: módulos y permisos, monedas, países y geografía, la configuración inicial.
- `nexus:instalar` crea:
  - la primera empresa, con su **casa matriz** y los catálogos de la plantilla;
  - el **administrador del sistema** (rol Administrador, acceso total a todas las empresas). Debe cambiar la contraseña la primera vez que entre;
  - el nombre del consorcio en la configuración.
- Si no se da `--password`, el instalador **genera una contraseña segura y la muestra una sola vez**: copiarla y entregarla al cliente por un medio seguro.
- Solo corre en una BD sin empresas. Si se equivocó algún dato, se corrige desde el sistema (Configuración → Empresas).

Opciones: `--iva=12` (tasa de IVA), `--sin-catalogos` (empresa vacía, sin plantilla).

## 6. Publicar el subdominio

En el proxy (Nginx/Caddy/Traefik), con HTTPS:

```
acme.tudominio.com  →  http://127.0.0.1:8101
```

Si el proxy es un contenedor de `red-external-network`, puede apuntar directo a `http://nexus-acme:80`.

## 7. Revisar antes de entregar

- [ ] `https://acme.tudominio.com` abre el inicio de sesión con candado (HTTPS)
- [ ] El administrador entra y el sistema le pide cambiar la contraseña
- [ ] En **Configuración → Empresas** aparece la empresa; en **Sucursales**, la casa matriz
- [ ] Subir un logo en la empresa funciona (confirma Contabo y la carpeta del cliente)
- [ ] El logo quedó en `clientes/acme/…` dentro del bucket, no en la raíz
- [ ] Nómina tiene conceptos, el CRM tiene etapas y Tickets tiene SLA (catálogos cargados)
- [ ] Un correo de prueba (recuperar contraseña) llega
- [ ] El cliente está anotado en la tabla del final

## 8. Actualizar a todos los clientes

Con una versión nueva del sistema:

```bash
cd /srv/nexus/codigo && git pull
docker build -t sistema-nexuserp-php:latest .
for dir in /srv/nexus/clientes/*/; do
    echo "== $dir"
    (cd "$dir" && docker compose up -d --force-recreate)
done
```

Cada contenedor, al arrancar, aplica las migraciones nuevas en **su** base (`RUN_MIGRATIONS=true`). Revisar los logs de cada uno (`docker compose logs app | grep -i migr`): el entrypoint no se detiene si una migración falla.

**Antes** de actualizar, sacar el respaldo (paso 9).

## 9. Respaldos

Uno por base, todos los días, guardado fuera del servidor:

```bash
mysqldump --single-transaction --routines nexus_acme | gzip > /respaldos/nexus_acme_$(date +%F).sql.gz
```

Probar de vez en cuando que un respaldo se puede restaurar en una base de prueba. Los archivos (logos, expedientes) están en Contabo, en la carpeta de cada cliente.

## 10. Problemas comunes

| Síntoma | Causa y solución |
|---|---|
| «La subida de archivos está bloqueada: falta CONTABO_CARPETA» | Falta `CONTABO_CARPETA` en el `.env` del cliente. Agregarla y `docker compose up -d --force-recreate`. |
| El instalador dice «La base de datos está vacía» | No corrió la migración: revisar `RUN_MIGRATIONS=true` y los logs, o correr `docker compose exec app php artisan migrate --force`. |
| «mysql: not found» al migrar | Imagen vieja: reconstruirla (desde esta versión trae el cliente de MySQL). |
| El instalador dice «Faltan los catálogos del sistema» | Correr `php artisan db:seed --force`. |
| El instalador dice «Falta la plantilla de catálogos» | Exportarla (paso 0.1), reconstruir la imagen, o usar `--sin-catalogos`. |
| Error de cifrado o «The MAC is invalid» | Se cambió la `APP_KEY`. Volver a la original (por eso no se cambia nunca). |
| Todos los usuarios aparecen con la misma IP | Falta `TRUSTED_PROXIES` con la IP del proxy. |

## Clientes instalados

| Cliente | Nombre corto | Subdominio | Puerto | Base de datos | Carpeta Contabo | Instalado | Tipo |
|---|---|---|---|---|---|---|---|
| (instalación original) | — | — | 8080 | — | raíz (`CONTABO_USAR_RAIZ`) | — | — |
| | | | | | | | venta / renta |
