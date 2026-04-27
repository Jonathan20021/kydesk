<?php
namespace App\Controllers;

use App\Core\Controller;

class LandingController extends Controller
{
    public const FEATURES = [
        'inbox' => [
            'title' => 'Bandeja unificada',
            'tagline' => 'Una bandeja para todo · Portal, email, teléfono, chat e interno en una sola vista',
            'icon' => 'inbox',
            'color' => '#7c5cff',
            'bg' => '#f3e8ff',
            'category' => 'CORE',
            'hero_kpis' => [['Reducción de tiempo', '42%'], ['Canales', '5+'], ['Adopción', '< 1 día']],
            'description' => 'Centraliza cada conversación con tus clientes. Sin importar si entró por email, WhatsApp, llamada o el portal — toda la actividad converge priorizada por SLA, prioridad y empresa. Tu equipo deja de perder tiempo saltando entre apps.',
            'steps' => [
                ['Conectá tus canales', 'Email, portal público, teléfono, chat o webhook. Setup en minutos sin código.', 'plug'],
                ['Priorización inteligente', 'Tickets ordenados por SLA, prioridad, antigüedad y tier de cliente automáticamente.', 'list-tree'],
                ['Resolvés desde un solo lugar', 'Plantillas, asignación, escalamiento y notas internas — todo a un atajo de distancia.', 'sparkles'],
            ],
            'benefits' => [
                ['mail','Email-to-ticket','Convierte emails en tickets con threading inteligente y attachments.'],
                ['globe','Portal público','Tus clientes abren tickets sin registrarse, con un link único de seguimiento.'],
                ['phone','Logging telefónico','Registra llamadas con un click — duración, motivo y resumen.'],
                ['lock','Notas internas','Conversaciones privadas del equipo separadas de la respuesta al cliente.'],
            ],
            'mockup' => 'inbox',
            'faqs' => [
                ['¿Puedo usar mi dominio para el email?','Sí. Configurás un MX o un forward y los emails entran como tickets con tu dominio visible para el cliente.'],
                ['¿Hay límite de canales?','Starter incluye 2 canales (portal + email). Pro y Enterprise incluyen los 5 canales sin límite.'],
                ['¿Soporta multimedia y adjuntos?','Sí. Imágenes, videos, audios y documentos hasta 25MB por archivo.'],
            ],
        ],
        'sla' => [
            'title' => 'Reloj SLA que late',
            'tagline' => 'Políticas de tiempo de respuesta y resolución por prioridad · Alertas antes de la brecha',
            'icon' => 'gauge',
            'color' => '#f59e0b',
            'bg' => '#fef3c7',
            'category' => 'OPERACIONES',
            'hero_kpis' => [['SLA cumplido', '+98%'], ['Alertas', 'Pre-brecha'], ['Niveles', 'Hasta 4']],
            'description' => 'Cada ticket arranca un cronómetro. Tu equipo ve en tiempo real qué tan cerca está cada caso de la brecha y recibe alertas antes — no después. Los tickets en riesgo escalan automáticamente sin intervención humana.',
            'steps' => [
                ['Definí tus políticas', 'Por prioridad: 15m / 1h / 4h / 1 día. Aplicá políticas distintas según empresa o categoría.', 'settings-2'],
                ['Reloj automático', 'El SLA empieza a correr al crear el ticket. Pausa solo si esperás respuesta del cliente.', 'timer'],
                ['Alertas + escalamiento', 'A 80% del tiempo, el supervisor recibe una alerta. A 100%, escalación automática a N2.', 'alarm-clock'],
            ],
            'benefits' => [
                ['shield-check','Compromiso visible','Cada ticket muestra el reloj con color por urgencia restante.'],
                ['trending-up','Escalación automática','Cuando un ticket se atraza, sube de nivel sin intervención.'],
                ['bell-ring','Alertas pre-brecha','Notificación al supervisor a 80% del tiempo restante.'],
                ['line-chart','Métricas SLA','Reportes de cumplimiento por técnico, categoría y empresa.'],
            ],
            'mockup' => 'sla',
            'faqs' => [
                ['¿Puedo tener SLAs distintos por cliente?','Sí. Las empresas Enterprise pueden tener SLAs diferentes a las premium o standard.'],
                ['¿Pausa el SLA si espero al cliente?','Sí. Cuando marcás el ticket "En espera" el reloj se pausa hasta que el cliente responda.'],
                ['¿Cuántos niveles de escalamiento hay?','Hasta 4 niveles: agente → técnico → supervisor → ingeniería.'],
            ],
        ],
        'kanban' => [
            'title' => 'Tablero Kanban',
            'tagline' => 'Drag & drop entre columnas · Tu flujo, tu forma',
            'icon' => 'kanban-square',
            'color' => '#1d4ed8',
            'bg' => '#dbeafe',
            'category' => 'PRODUCTIVIDAD',
            'hero_kpis' => [['Cambio de estado', '1 click'], ['Visualización', 'Real-time'], ['Filtros', 'Avanzados']],
            'description' => 'Visualiza el flujo completo de tickets como un tablero Trello. Arrastra cards entre columnas para cambiar estado, agrupa por técnico o categoría, y aplica filtros sin recargar. Perfecto para standups y daily syncs.',
            'steps' => [
                ['Vista de flujo', 'Columnas por estado: Abierto, En progreso, En espera, Resuelto.', 'columns-3'],
                ['Drag & drop', 'Arrastrá cards entre columnas y el estado se actualiza con audit log.', 'move'],
                ['Agrupá como quieras', 'Por técnico, por prioridad, por empresa — cambia la perspectiva en un click.', 'group'],
            ],
            'benefits' => [
                ['move','Drag intuitivo','Arrastra para cambiar estado, asignar o priorizar.'],
                ['filter','Filtros guardados','Vistas personalizadas por usuario que persisten entre sesiones.'],
                ['users','Modo colaborativo','Múltiples técnicos pueden mover tickets sin conflictos.'],
                ['zap','Atajos de teclado','J/K para navegar, A para asignar, R para resolver — sin tocar el mouse.'],
            ],
            'mockup' => 'kanban',
            'faqs' => [
                ['¿Puedo personalizar las columnas?','Sí. Podés ocultar estados o crear columnas custom según tu workflow.'],
                ['¿Funciona en mobile?','El kanban se adapta: en móvil mostramos una columna a la vez con swipe lateral.'],
                ['¿Hay sincronización en vivo?','Cuando un técnico mueve un ticket, los demás lo ven actualizado al instante.'],
            ],
        ],
        'automations' => [
            'title' => 'Automatizaciones IA',
            'tagline' => 'Reglas que ejecutan acciones solas · Si esto entonces aquello, sin código',
            'icon' => 'workflow',
            'color' => '#7e22ce',
            'bg' => '#f3e8ff',
            'category' => 'IA & WORKFLOWS',
            'hero_kpis' => [['Tickets auto-cerrados', '30%'], ['Triggers', '12+'], ['Acciones', '20+']],
            'description' => 'Reglas tipo IFTTT que reaccionan a eventos del helpdesk. "Cuando entra un ticket urgente de Acme, asignar a Marco y notificar a Slack." Construilas con un editor visual sin escribir código. La IA sugiere reglas según el patrón de tu equipo.',
            'steps' => [
                ['Elegí un disparador', 'Ticket creado, comentado, escalado, en SLA-risk, resuelto, etc.', 'zap'],
                ['Define condiciones', 'Por categoría, prioridad, empresa, canal o cualquier campo.', 'filter'],
                ['Encadena acciones', 'Asignar, etiquetar, notificar, cambiar estado, enviar email…', 'workflow'],
            ],
            'benefits' => [
                ['user-cog','Auto-asignación','Tickets de hardware → Marco. Software → Ana. Sin pensarlo.'],
                ['mail','Notificaciones','Slack, Teams, email o webhook al disparar reglas.'],
                ['sparkles','Sugerencias IA','Detecta patrones y propone reglas: "noté que siempre cerrás los tickets X — ¿automatizo?".'],
                ['history','Audit completo','Log de cada ejecución con resultado, duración y errores.'],
            ],
            'mockup' => 'automations',
            'faqs' => [
                ['¿Hay límite de reglas?','Pro: hasta 999 reglas. Enterprise: sin límite.'],
                ['¿Funciona offline?','Las reglas corren en el servidor — siempre activas, no dependen de tu navegador.'],
                ['¿Puedo usar webhooks externos?','Sí. Acción "HTTP request" para integrar con cualquier API.'],
            ],
        ],
        'analytics' => [
            'title' => 'Analítica profunda',
            'tagline' => 'Métricas que mueven decisiones · Reportes en tiempo real con drill-down',
            'icon' => 'line-chart',
            'color' => '#047857',
            'bg' => '#d1fae5',
            'category' => 'DATA',
            'hero_kpis' => [['Dashboards', '15+'], ['Exportable', 'CSV/PDF'], ['Refresh', 'Real-time']],
            'description' => 'Visualiza tu operación al detalle. Tickets/día, tiempo de respuesta, cumplimiento SLA, ranking de técnicos, tendencias por categoría. Drill-down desde cualquier número hasta el ticket individual. Exportable para presentar a tu CEO.',
            'steps' => [
                ['Datos en vivo', 'Sin batches ni delays — los gráficos se refrescan al instante.', 'activity'],
                ['Drill-down', 'Hacé click en cualquier número para ver los tickets que lo componen.', 'mouse-pointer-2'],
                ['Comparativas', 'Esta semana vs anterior, este mes vs el pasado, técnico vs equipo.', 'bar-chart-3'],
            ],
            'benefits' => [
                ['target','Tasa de resolución','% de tickets resueltos en plazo, por equipo y técnico.'],
                ['clock','Tiempo medio','First response, time to resolve, time on hold.'],
                ['trophy','Ranking técnicos','Quién resuelve más, más rápido y con mejor CSAT.'],
                ['download','Exportación','CSV para Excel, PDF para presentar, API para BI tools.'],
            ],
            'mockup' => 'analytics',
            'faqs' => [
                ['¿Hay límite de retención?','Starter: 7 días. Pro: 30 días. Enterprise: indefinido.'],
                ['¿Puedo programar reportes?','Sí. Reportes por email diarios o semanales a stakeholders.'],
                ['¿Soporta dashboards custom?','Enterprise: dashboards personalizables con drag&drop de widgets.'],
            ],
        ],
        'kb' => [
            'title' => 'Base de conocimiento',
            'tagline' => 'Artículos públicos e internos · Reduce tickets repetitivos',
            'icon' => 'book-open',
            'color' => '#b45309',
            'bg' => '#fef3c7',
            'category' => 'AUTOSERVICIO',
            'hero_kpis' => [['Reducción de tickets', '38%'], ['Editor', 'Markdown'], ['Búsqueda', 'Full-text']],
            'description' => 'Tus clientes resuelven solos las dudas frecuentes. Crea artículos con editor markdown, organizalos por categorías con icon y color, y publicalos en tu portal público. Los técnicos también acceden a artículos internos solo para el equipo.',
            'steps' => [
                ['Editor markdown', 'Sintaxis simple con preview en vivo. Adjuntá imágenes y videos.', 'pen-square'],
                ['Categorización', 'Categorías con icon y color custom para navegación visual.', 'folder-tree'],
                ['Sugerencias automáticas', 'Al abrir un ticket, sugerimos los 3 artículos más relevantes.', 'sparkles'],
            ],
            'benefits' => [
                ['globe','Portal público','Tus clientes encuentran respuestas sin abrir ticket.'],
                ['users','Solo para el equipo','Artículos internos para procesos y troubleshooting.'],
                ['search','Búsqueda full-text','Encuentra cualquier palabra del cuerpo o título.'],
                ['thumbs-up','Útil/no útil','Métricas de qué artículos funcionan mejor.'],
            ],
            'mockup' => 'kb',
            'faqs' => [
                ['¿Hay límite de artículos?','Starter: 10. Pro: 999. Enterprise: ilimitado.'],
                ['¿Soporta versionado?','Sí. Cada edición queda en historial con autor y diff.'],
                ['¿Puedo personalizar el portal?','Sí. Logo, color primario y dominio custom (Enterprise).'],
            ],
        ],
        'multitenant' => [
            'title' => 'Multi-tenant nativo',
            'tagline' => 'Aísla cada organización con sus datos, equipo y branding propios',
            'icon' => 'building-2',
            'color' => '#be185d',
            'bg' => '#fce7f3',
            'category' => 'ARQUITECTURA',
            'hero_kpis' => [['Aislamiento', '100%'], ['Workspaces', 'Ilimitados'], ['SLA', '99.99%']],
            'description' => 'Si das soporte a varios clientes (MSP) o tienes múltiples marcas, Kydesk aísla cada workspace a nivel de datos. Cada uno tiene sus usuarios, tickets, categorías, SLAs y branding propio. Sin contaminación cruzada, sin riesgos de compliance.',
            'steps' => [
                ['Workspace por cliente', 'Cada org tiene su slug propio: kydesk.kyrosrd.com/t/acme y kydesk.kyrosrd.com/t/globex.', 'building-2'],
                ['Datos aislados', 'Foreign keys con tenant_id en cada tabla. Imposible filtrar entre tenants.', 'shield'],
                ['Branding por workspace', 'Color primario, logo y dominio custom por organización.', 'palette'],
            ],
            'benefits' => [
                ['shield','Aislamiento de datos','Cada query lleva tenant_id. Cero riesgo de fuga cruzada.'],
                ['palette','Branding propio','Color primario, logo, dominio custom por workspace.'],
                ['users-round','Equipo independiente','Cada org tiene sus técnicos, roles y permisos.'],
                ['repeat','Plantilla onboarding','Crea workspaces clonando categorías, roles y SLAs.'],
            ],
            'mockup' => 'multitenant',
            'faqs' => [
                ['¿Puedo cambiar entre workspaces?','Sí. El selector arriba muestra todos los workspaces a los que perteneces.'],
                ['¿Funciona como MSP?','Perfecto para MSPs: un técnico atiende múltiples clientes desde un panel unificado.'],
                ['¿Hay residencia de datos?','Pro: US o EU. Enterprise: US, EU o LATAM con SLA garantizado por región.'],
            ],
        ],
        'departments' => [
            'title' => 'Departamentos',
            'tagline' => 'Organiza el equipo y enruta tickets por área · SLAs y reportes propios',
            'icon' => 'layers',
            'color' => '#3b82f6',
            'bg' => '#eff6ff',
            'category' => 'OPERACIONES',
            'hero_kpis' => [['Áreas funcionales', 'Ilimitadas'], ['Auto-asignación', 'Al líder'], ['Reportes', 'Por área']],
            'description' => 'Estructura tu helpdesk como tu organización: Soporte Técnico, Ventas, Facturación, RRHH. Cada departamento tiene sus agentes, su líder, sus SLAs y sus reportes. Los tickets se enrutan automáticamente al área correcta según las reglas que definas.',
            'steps' => [
                ['Crea áreas funcionales', 'Departamentos con icono, color y descripción. Asigna técnicos al equipo.', 'layers'],
                ['Define al líder', 'Cada área tiene un manager que recibe la asignación automática de tickets nuevos.', 'user-check'],
                ['Reportes y SLAs propios', 'Mide cada área independientemente — volumen, brechas SLA, tiempo medio.', 'bar-chart-3'],
            ],
            'benefits' => [
                ['users','Agentes por área','Pivote many-to-many: un técnico puede pertenecer a varios departamentos.'],
                ['workflow','Enrutamiento automático','Las automatizaciones pueden derivar tickets a un departamento según condiciones.'],
                ['gauge','SLAs específicos','Política de SLA propia por área — soporte 15min, ventas 1h, etc.'],
                ['line-chart','Métricas independientes','Panel de desempeño por departamento con tasa de resolución y SLA.'],
            ],
            'mockup' => 'departments',
            'faqs' => [
                ['¿Hay límite de departamentos?','No. En Pro o superior puedes crear todos los que necesites.'],
                ['¿Un agente puede estar en varios?','Sí. Soportamos asignación múltiple con flag de líder por departamento.'],
                ['¿Se pueden tener SLAs distintos por área?','Sí. Cada departamento puede tener sus propias políticas SLA además de las globales.'],
            ],
        ],
        'integrations' => [
            'title' => 'Integraciones',
            'tagline' => 'Conecta con Slack, Discord, Teams, Telegram, Zapier y más · Setup desde la UI',
            'icon' => 'plug',
            'color' => '#0ea5e9',
            'bg' => '#e0f2fe',
            'category' => 'IA & WORKFLOWS',
            'hero_kpis' => [['Proveedores', '12+'], ['Setup', '< 2 min'], ['Eventos', '12 disparadores']],
            'description' => 'Marketplace de integraciones nativas: Slack, Discord, Telegram, Microsoft Teams, Mattermost, Rocket.Chat, Zapier, n8n, Make, Webhook genérico, Email forwarding y Pushover. Cada evento del helpdesk dispara las integraciones que tengas activas — todo configurable desde la UI sin tocar código.',
            'steps' => [
                ['Elegí del marketplace', 'Browse 12+ proveedores agrupados por categoría: chat, automation, devops, notify.', 'shopping-bag'],
                ['Pega tu webhook o token', 'Cada proveedor define su config schema: URL, bot token, chat ID, etc. Validación en tiempo real.', 'plug'],
                ['Selecciona eventos', 'Marca qué eventos disparan: ticket.created, sla.breach, ticket.resolved, etc.', 'zap'],
            ],
            'benefits' => [
                ['message-square','Slack, Discord, Teams','Mensajes ricos con embeds, fields y colores por tipo de evento.'],
                ['send','Telegram & Pushover','Notificaciones push directas a tu móvil donde estés.'],
                ['workflow','Zapier, n8n, Make','Conecta con miles de apps externas via webhooks de automation.'],
                ['shield-check','HMAC firmado','Webhook genérico con firma HMAC-SHA256 opcional para seguridad.'],
            ],
            'mockup' => 'integrations',
            'faqs' => [
                ['¿Cuántas integraciones puedo tener?','Pro: 5. Business: 15. Enterprise: ilimitadas. Configurable desde super admin.'],
                ['¿Cómo manejo los errores?','Cada integración tiene log de los últimos 200 envíos con HTTP status, latencia y respuesta.'],
                ['¿Puedo pausar sin borrar?','Sí. Toggle activar/pausar conserva la configuración para reactivar cuando quieras.'],
                ['¿Hay botón de prueba?','Sí. Cada integración tiene un botón "Probar" que envía un ping de test con latencia visible.'],
            ],
        ],
        'roles' => [
            'title' => 'Roles 30+',
            'tagline' => 'Permisos granulares por módulo · Quién hace qué, exacto',
            'icon' => 'shield',
            'color' => '#b91c1c',
            'bg' => '#fee2e2',
            'category' => 'SEGURIDAD',
            'hero_kpis' => [['Permisos', '30+'], ['Roles base', '5'], ['Custom', 'Ilimitados']],
            'description' => 'Define con precisión quién puede ver, crear, editar o eliminar en cada módulo. 5 roles del sistema (Owner, Admin, Supervisor, Técnico, Agente) más roles personalizados ilimitados. Asignación granular por usuario.',
            'steps' => [
                ['Roles del sistema', '5 roles base con permisos pre-configurados según jerarquía.', 'shield-check'],
                ['Roles custom', 'Crea roles propios. Ej: "QA Reviewer" con view+comment pero sin edit.', 'shield-plus'],
                ['Asignación', 'Cada usuario tiene un rol. Cambio de rol aplica permisos al instante.', 'user-cog'],
            ],
            'benefits' => [
                ['key','Granularidad','30+ permisos atómicos: tickets.view, tickets.delete, sla.edit, etc.'],
                ['lock','Defaults seguros','Owner full-access, Agente solo crea tickets — sin sorpresas.'],
                ['shield-check','Audit por rol','Cada acción queda en log con el rol del usuario al momento.'],
                ['users-round','SCIM (Enterprise)','Provisioning automático desde tu IdP corporativo.'],
            ],
            'mockup' => 'roles',
            'faqs' => [
                ['¿Puedo modificar los roles del sistema?','Los del sistema son protegidos. Podés clonarlos y modificar la copia.'],
                ['¿Hay roles temporales?','Sí. Asignás un rol con fecha de expiración (ideal para vendors externos).'],
                ['¿Soporta SSO + SCIM?','Enterprise: SSO con SAML 2.0 + SCIM para provisioning automático.'],
            ],
        ],
    ];

    public function index(): void
    {
        $plans = [];
        try {
            $plans = $this->db->all(
                "SELECT * FROM plans WHERE is_active = 1 AND is_public = 1 ORDER BY sort_order ASC, price_monthly ASC"
            );
        } catch (\Throwable $e) { /* tabla no existe en setups antiguos */ }

        $featured = null;
        try {
            $featured = $this->db->one(
                "SELECT id, version, title, hero_pill_label
                 FROM changelog_entries
                 WHERE is_featured = 1 AND is_published = 1
                 ORDER BY published_at DESC LIMIT 1"
            );
        } catch (\Throwable $e) { /* tabla no existe en setups antiguos */ }

        $this->render('landing/index', [
            'title' => 'Kydesk Helpdesk — El helpdesk que tu equipo merece',
            'plans' => $plans,
            'featuredChangelog' => $featured,
        ], 'public');
    }

    public function pricing(): void
    {
        $plans = [];
        try {
            $plans = $this->db->all(
                "SELECT * FROM plans WHERE is_active = 1 AND is_public = 1 ORDER BY sort_order ASC, price_monthly ASC"
            );
        } catch (\Throwable $e) { /* tabla no existe en setups antiguos */ }
        $this->render('landing/pricing', [
            'title' => 'Planes y precios',
            'plans' => $plans,
        ], 'public');
    }

    public function features(): void
    {
        $this->render('landing/features', ['title' => 'Funcionalidades', 'features' => self::FEATURES], 'public');
    }

    public function feature(array $params): void
    {
        $key = $params['key'] ?? '';
        if (!isset(self::FEATURES[$key])) {
            http_response_code(404);
            $this->render('errors/404', ['message' => 'Funcionalidad no encontrada'], 'public');
            return;
        }
        $f = self::FEATURES[$key];
        $this->render('landing/feature', [
            'title' => $f['title'] . ' · Kydesk',
            'feature' => $f,
            'featureKey' => $key,
            'allFeatures' => self::FEATURES,
        ], 'public');
    }

    public function contact(): void
    {
        $this->render('landing/contact', ['title' => 'Contacto'], 'public');
    }

    public function clients(): void
    {
        $this->render('landing/clients', ['title' => 'Clientes que confían en Kydesk'], 'public');
    }

    public function careers(): void
    {
        $this->render('landing/careers', ['title' => 'Carreras · Trabajá con nosotros'], 'public');
    }

    public function docs(): void
    {
        $this->render('landing/docs', ['title' => 'Documentación'], 'public');
    }

    public function status(): void
    {
        $this->render('landing/status', ['title' => 'Estado del servicio'], 'public');
    }

    public function changelog(): void
    {
        $entries = [];
        try {
            $rows = $this->db->all(
                "SELECT id, version, title, summary, release_type, published_at
                 FROM changelog_entries
                 WHERE is_published = 1
                 ORDER BY published_at DESC, id DESC LIMIT 50"
            );
            foreach ($rows as $r) {
                $items = $this->db->all(
                    "SELECT item_type, text FROM changelog_items WHERE entry_id = ? ORDER BY sort_order ASC, id ASC",
                    [$r['id']]
                );
                $r['items'] = $items;
                $entries[] = $r;
            }
        } catch (\Throwable $e) { /* tabla no existe */ }
        $this->render('landing/changelog', [
            'title' => 'Changelog · Novedades',
            'entries' => $entries,
        ], 'public');
    }

    public function privacy(): void
    {
        $this->render('landing/privacy', ['title' => 'Política de privacidad'], 'public');
    }

    public function terms(): void
    {
        $this->render('landing/terms', ['title' => 'Términos y condiciones'], 'public');
    }
}
