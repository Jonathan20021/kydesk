# Kyros Helpdesk — SaaS Multi-Tenant

Plataforma completa de helpdesk multi-tenant construida con **PHP + MySQL + Tailwind CSS + Chart.js + GSAP + Alpine.js**. Incluye tickets, escalamientos, notas, tareas (estilo Todoist), usuarios, roles y permisos, reportes, portal público para clientes y panel administrativo moderno.

---

## ⚡ Instalación rápida (XAMPP)

1. **Copia la carpeta** a `C:\xampp\htdocs\kyros-helpdesk`
2. **Inicia Apache y MySQL** desde el panel de XAMPP
3. **Abre en el navegador:** <http://localhost/kyros-helpdesk/install>
4. Haz clic en **"Instalar ahora"**. Se creará la base de datos, las tablas, los roles, permisos, un tenant demo y usuarios de prueba.
5. Inicia sesión en <http://localhost/kyros-helpdesk/auth/login>

### 🔐 Credenciales demo

| Rol         | Email                   | Contraseña |
|-------------|-------------------------|------------|
| Owner/Admin | `admin@demo.com`        | `admin123` |
| Técnico     | `tecnico@demo.com`      | `tech123`  |
| Supervisor  | `supervisor@demo.com`   | `tech123`  |

---

## 📍 Rutas principales

### Públicas
- `/` — Landing page
- `/features`, `/pricing`, `/contact` — Páginas informativas
- `/auth/login`, `/auth/register` — Acceso y registro de nuevas organizaciones

### Panel de tenant (requiere login)
- `/t/{slug}/dashboard` — Dashboard con métricas y Chart.js
- `/t/{slug}/tickets` — Listado, filtros, creación, detalle, comentarios, escalamiento, asignación
- `/t/{slug}/notes` — Notas tipo sticky con etiquetas y colores
- `/t/{slug}/todos` — Listas de tareas estilo Todoist
- `/t/{slug}/users` — Gestión de usuarios y técnicos
- `/t/{slug}/roles` — Roles y matriz de permisos granular
- `/t/{slug}/reports` — Reportes con gráficas y desempeño de técnicos
- `/t/{slug}/settings` — Ajustes del tenant
- `/t/{slug}/profile` — Perfil del usuario actual

### Portal público de clientes
- `/portal/{slug}` — Página de bienvenida
- `/portal/{slug}/new` — Crear ticket sin cuenta
- `/portal/{slug}/ticket/{token}` — Ver y responder un ticket por token único

---

## 🧩 Características

### Helpdesk
- Tickets con código único por tenant (`TK-01-00001`)
- 5 estados: abierto, en progreso, en espera, resuelto, cerrado
- 4 niveles de prioridad con colores y ordenamiento
- Categorías con íconos y colores
- Canales: portal, email, teléfono, chat, interno
- Comentarios internos y públicos
- Escalamientos con historial y niveles (N1, N2, N3…)
- Asignación de técnicos
- SLA por ticket

### Productividad
- **Notas:** 8 colores, pin, etiquetas, búsqueda
- **Tareas estilo Todoist:** listas, prioridades, fechas límite, completadas
- **Dashboard:** métricas en vivo, ranking de técnicos, gráficas

### Multi-tenant
- Aislamiento por `tenant_id` en todas las tablas
- Cada organización registra su propio slug, usuarios, roles, categorías y ajustes
- Portal público único por tenant

### Seguridad
- Hashing de contraseñas con bcrypt (cost 12)
- Protección CSRF en todos los formularios POST
- Validación de tenant en cada controlador (`requireTenant`)
- Permisos granulares por módulo (30+ permisos)
- Sesiones con `httponly` + `samesite=Lax`
- PDO con prepared statements contra SQL injection

### Roles incluidos por defecto
| Rol          | Acceso                                                |
|--------------|-------------------------------------------------------|
| Owner        | Total (no restringible, bypass de permisos)           |
| Admin        | Todos los permisos                                    |
| Supervisor   | Tickets + reportes + ver usuarios/roles               |
| Técnico      | Tickets, notas, tareas, reportes                      |
| Agente       | Crear tickets, notas, tareas                          |

---

## 🏗️ Arquitectura

```
kyros-helpdesk/
├── index.php              # Front controller
├── .htaccess              # URL rewriting
├── config/
│   └── config.php         # Configuración (DB, app, sesión, seguridad)
├── database/
│   └── schema.sql         # Esquema completo
├── app/
│   ├── Core/              # Framework mínimo (Router, DB, Auth, View, Session, CSRF, Helpers)
│   ├── Controllers/       # 11 controladores
│   └── Views/             # Vistas con layouts: public, auth, app
└── public/
    ├── css/app.css
    ├── js/app.js
    └── uploads/
```

### Stack
- **Backend:** PHP 8.0+ puro (sin framework pesado), PDO, PSR-4 autoloader propio
- **Frontend:** Tailwind CSS (CDN), Alpine.js, Chart.js 4, GSAP 3 + ScrollTrigger, Lucide icons
- **DB:** MySQL 5.7+ / MariaDB (InnoDB, FKs, utf8mb4)
- **Navegadores:** Chrome/Edge/Firefox/Safari modernos

---

## 🎨 Diseño

- Paleta: brand indigo (`#6366f1`), acento pink (`#ec4899`) y amber (`#f59e0b`)
- Tipografía Inter
- Layouts responsivos (mobile-first)
- Sidebar colapsable, topbar con menú de usuario y notificaciones
- Landing con hero animado, mockup de app, features grid, stats, testimonios y CTA
- Animaciones con GSAP al hacer scroll (`data-reveal`)
- Gráficas con gradientes y tooltips custom en Chart.js

---

## 🧪 Crear tu primera organización

1. Ve a <http://localhost/kyros-helpdesk/auth/register>
2. Completa el formulario: nombre de la organización, tu nombre, email, contraseña
3. El slug se genera automáticamente desde el nombre (puedes editarlo)
4. Al crear, serás owner del nuevo tenant y redirigido a tu dashboard

---

## 🔧 Configuración

Edita `config/config.php` para cambiar:
- Nombre de la app, URL base, zona horaria
- Credenciales de MySQL (por defecto: `root` sin contraseña, DB `kyros_helpdesk`)
- Parámetros de sesión y seguridad
- Configuración de uploads

Si cambias la URL base (por ejemplo para producción), actualiza también `.htaccess`:

```apache
RewriteBase /tu-nueva-ruta/
```

---

## 🚀 Despliegue en producción

1. Cambia `'env' => 'production'` y `'debug' => false` en `config/config.php`
2. Usa credenciales MySQL fuertes
3. Activa HTTPS y marca `'secure' => true` en la sesión
4. Configura `'url'` con tu dominio
5. Si usas Apache, asegúrate de que `mod_rewrite` esté activo

---

## 📝 Licencia

Proyecto de referencia. Úsalo, modifícalo y aprende de él libremente.

---

**Hecho con ♥ para equipos de soporte modernos.**
