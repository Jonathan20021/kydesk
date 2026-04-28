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
        'retainers' => [
            'title' => 'Igualas (Retainers)',
            'tagline' => 'Contratos recurrentes para soporte TI, desarrollo, sistemas y más · 100% configurable desde la UI',
            'icon' => 'handshake',
            'color' => '#10b981',
            'bg' => '#ecfdf5',
            'category' => 'NEGOCIO',
            'hero_kpis' => [['Categorías', '11+'], ['Plantillas', '10+'], ['Setup', '< 5 min']],
            'description' => 'Centralizá todos tus contratos recurrentes en un solo módulo: soporte mensual, desarrollo de software a medida, mantenimiento de sistemas, ciberseguridad, marketing digital, asesoría legal o contable. Configurá categorías de servicio con sus propias unidades (horas, tickets, licencias, proyectos), líneas de detalle por contrato, plantillas reutilizables, ciclos de facturación, horas incluidas y tarifas de excedente. Cada período se cierra automáticamente con su consumo y excedente calculado.',
            'steps' => [
                ['Configurá tus categorías', 'Soporte TI, Desarrollo, Cloud/DevOps, Legal, Marketing… o las que necesites. Cada una con su unidad por defecto.', 'tags'],
                ['Creá una plantilla', 'Pre-cargá items, ciclo, monto y horas incluidas. Reusá la plantilla para acelerar la alta de cada cliente.', 'sparkles'],
                ['Asigná y cobrá', 'Contrato a empresa o cliente individual. El sistema arranca el primer período, registra consumos, calcula excedentes y avanza al siguiente automáticamente.', 'wallet'],
            ],
            'benefits' => [
                ['layers','Categorías ilimitadas','Soporte TI, dev software, sistemas, ciberseguridad, marketing, legal, contable, mantenimiento web, licencias SaaS — todo configurable.'],
                ['list','Items por línea','Combina horas dev + licencias + proyectos en un mismo contrato. Cada item con su tarifa y unidad.'],
                ['sparkles','Plantillas reutilizables','10 plantillas seed listas: Soporte Premium, Dev Sprint, Cloud Ops 24/7, Pentest mensual, Marketing 360…'],
                ['timer','Períodos automáticos','Apertura/cierre con consumos y cálculo de excedentes a tarifa configurable. Auto-renew o manual.'],
                ['users','Empresas e individuales','Soporta clientes corporativos y personas físicas (freelancers, profesionales independientes).'],
                ['gauge','SLA por contrato','Tiempos de respuesta y resolución específicos por iguala — sobre el ticket entra al cronómetro adecuado.'],
            ],
            'mockup' => 'retainers',
            'faqs' => [
                ['¿Cómo se diferencian las Igualas de las suscripciones?','Las suscripciones (Subscriptions del super admin) son tu pago a Kydesk. Las Igualas son los contratos que vos le facturás a tus clientes — independientes del plan SaaS.'],
                ['¿Puedo usar plantillas distintas para distintos servicios?','Sí. Creas una plantilla por tipo (ej: "Soporte TI Básico", "Dev Sprint Frontend") y al alta elegís cuál usar.'],
                ['¿Qué pasa si un mes el cliente se pasa de horas?','Se calcula el excedente automáticamente con la tarifa hora extra que definiste, y queda visible en el período cerrado para facturarlo aparte.'],
                ['¿Soporta clientes individuales sin empresa registrada?','Sí. El módulo permite "cliente individual" con nombre, documento, email y teléfono — sin necesidad de crear una empresa primero.'],
                ['¿En qué planes está disponible?','Business y Enterprise. Si necesitás verlo en otro plan puntual, el super admin puede habilitarlo por override desde el panel admin.'],
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
                ['¿Cuántas integraciones puedo tener?','Pro: 5. Business: 15. Enterprise: ilimitadas. Los límites se ajustan según tu plan.'],
                ['¿Cómo manejo los errores?','Cada integración tiene log de los últimos 200 envíos con HTTP status, latencia y respuesta.'],
                ['¿Puedo pausar sin borrar?','Sí. Toggle activar/pausar conserva la configuración para reactivar cuando quieras.'],
                ['¿Hay botón de prueba?','Sí. Cada integración tiene un botón "Probar" que envía un ping de test con latencia visible.'],
            ],
        ],
        'email_inbound' => [
            'title' => 'Email-to-Ticket',
            'tagline' => 'Recibí emails y convertilos en tickets automáticamente · IMAP o forward webhook',
            'icon' => 'mail-open',
            'color' => '#0ea5e9',
            'bg' => '#e0f2fe',
            'category' => 'CANALES',
            'hero_kpis' => [['Threading', 'RFC-compliant'], ['Métodos', 'IMAP + Forward'], ['Setup', '< 3 min']],
            'description' => 'La entrada #1 de soporte real. Conectá tu buzón con IMAP o configurá un forward webhook desde Mailgun, Postmark, SendGrid o Zapier. Cada email entrante se convierte en ticket nuevo, y las respuestas se enlazan automáticamente al hilo correcto vía Message-ID/In-Reply-To.',
            'steps' => [
                ['Conectá tu buzón', 'IMAP con SSL/TLS o webhook con header de auth. Soporta Gmail, Outlook, IONOS y cualquier provider.', 'plug'],
                ['Threading inteligente', 'Mensajes de respuesta se atan al ticket original sin duplicar. Detección de Message-ID + In-Reply-To.', 'git-merge'],
                ['Auto-asignación', 'Detecta empresa por dominio del email, asigna categoría y prioridad por defecto, agente automático.', 'zap'],
            ],
            'benefits' => [
                ['mail-check','IMAP fetcher','Pull de emails sin leer (UNSEEN), parser multipart, dedupe por Message-ID.'],
                ['webhook','Forward universal','Endpoint /api/v1/email-inbound/forward con header x-forward-token. Compatible con cualquier ESP.'],
                ['shield-check','Threading RFC-compliant','In-Reply-To y References siguen el estándar. No fragmenta hilos.'],
                ['building-2','Auto-detección empresa','Match por dominio en companies + contactos del tenant.'],
            ],
            'mockup' => 'email_inbound',
            'faqs' => [
                ['¿Qué proveedores soporta el forward?','Mailgun, Postmark, SendGrid, IONOS, Zapier, n8n, Make, IFTTT — cualquiera que pueda mandar POST con JSON.'],
                ['¿Cómo evita duplicados?','Por Message-ID. Si el mismo email llega dos veces, descarta el segundo.'],
                ['¿Soporta attachments?','El parser extrae text/html. Los attachments se ignoran en esta versión (próxima feature).'],
                ['¿Cuántos buzones puedo conectar?','Ilimitado. Configurá uno por área (soporte, ventas, billing, etc.).'],
            ],
        ],
        'live_chat' => [
            'title' => 'Live Chat / Messenger',
            'tagline' => 'Widget embebible para tu sitio · Chat en vivo con tu equipo',
            'icon' => 'message-square',
            'color' => '#10b981',
            'bg' => '#ecfdf5',
            'category' => 'CANALES',
            'hero_kpis' => [['Setup', '1 line snippet'], ['Real-time', 'Polling 3s'], ['Conv. → Ticket', '1-click']],
            'description' => 'Widget de chat embebible que pegás con un solo script tag en tu sitio web. Tus visitantes inician una conversación, tu equipo responde desde el agent inbox de Kydesk, y podés convertir cualquier conversación a ticket con un click. Color, mensajes y CORS configurables desde la UI.',
            'steps' => [
                ['Configurá el widget', 'Color primario, mensaje de bienvenida, requerir email del visitante, orígenes CORS permitidos.', 'palette'],
                ['Pegá el snippet', 'Una línea: <script async src=".../chat-widget/{key}.js"></script>. Listo, ya está activo.', 'code'],
                ['Respondé desde Kydesk', 'Inbox de conversaciones con polling en tiempo real. Convertí a ticket si necesita seguimiento.', 'inbox'],
            ],
            'benefits' => [
                ['zap','JS auto-contenido','200 líneas inline · sin dependencias externas · funciona en cualquier sitio.'],
                ['lock-keyhole','CORS configurable','Restringí el widget a dominios específicos para evitar abuso.'],
                ['ticket','1-click a ticket','Convertí conversación en ticket persistente con todo el historial.'],
                ['users','Inbox del equipo','Múltiples agentes ven y responden conversaciones asignadas.'],
            ],
            'mockup' => 'live_chat',
            'faqs' => [
                ['¿Necesita servidor de WebSocket?','No. Polling cada 3s del lado visitante y 4s del lado agente. Funciona con cualquier hosting.'],
                ['¿Persiste la conversación si el visitante recarga?','Sí. localStorage guarda el token del visitante, retoma la conversación al volver.'],
                ['¿Puedo personalizar el widget?','Color primario, mensajes, requerir o no email. Más customizaciones en próximas releases.'],
            ],
        ],
        'ai_assist' => [
            'title' => 'Kyros IA Asistente',
            'tagline' => 'Sugerir respuesta, resumir, clasificar, sentiment, traducir · Plan Enterprise',
            'icon' => 'sparkles',
            'color' => '#a78bfa',
            'bg' => '#f3e8ff',
            'category' => 'IA & WORKFLOWS',
            'hero_kpis' => [['Tecnología', 'Kyros IA'], ['Acciones', '5 builtins'], ['Disponible', 'Enterprise']],
            'description' => 'Kyros IA integrada al flujo de tickets. Sugerí respuestas a partir del contexto, resumí hilos largos en 4 líneas, auto-categorizá tickets, detectá sentiment del cliente y traducí entre idiomas — todo con un click. La infraestructura de IA la gestiona Kydesk de punta a punta: tu equipo no toca API keys ni billing del proveedor.',
            'steps' => [
                ['Kydesk activa la IA', 'El equipo de Kydesk asigna Kyros IA a tu workspace Enterprise con cuota mensual. Sin manejar API keys.', 'shield-check'],
                ['Tu equipo elige acciones', 'Toggle qué acciones usar (sugerir reply, resumir, categorizar, sentiment, traducir) e idioma destino.', 'sliders'],
                ['1-click en cada ticket', 'Botones "Sugerir respuesta" o "Resumir" en cada ticket — Kyros IA responde en segundos con tu contexto.', 'zap'],
            ],
            'benefits' => [
                ['message-square-quote','Sugerir respuesta','Genera draft de respuesta con tono profesional, basado en el hilo del ticket. Editá antes de enviar.'],
                ['file-text','Resumir hilo','Tickets con 30+ mensajes condensados en 4 oraciones — ideal para handoffs.'],
                ['tag','Auto-categorizar','Kyros IA propone categoría y prioridad analizando el contenido. Acelera la entrada.'],
                ['heart-pulse','Detectar sentiment','Marca tickets con sentimiento negativo o urgente para que tu supervisor priorice.'],
                ['languages','Traducir','Cliente escribe en inglés, agente responde en español — auto-translate maneja el flujo.'],
                ['shield','Enterprise-only','Cuota mensual visible, kill-switch global, audit log con cada completion (tokens, duración, status).'],
            ],
            'mockup' => 'ai_assist',
            'faqs' => [
                ['¿Quién paga el costo de la IA?','Kydesk gestiona la infraestructura y absorbe el costo dentro del plan Enterprise. Sin sorpresas en tu factura.'],
                ['¿Hay límite de uso?','Cuota mensual configurable por tenant. Si necesitás más, contactá al equipo de Kydesk para upgrade.'],
                ['¿Qué tecnología usa?','Kyros IA está construida sobre modelos de lenguaje de última generación seleccionados y optimizados por Kydesk.'],
                ['¿Mis datos se usan para entrenar?','No. Tus tickets quedan privados — los proveedores que utilizamos no entrenan con prompts de la API.'],
                ['¿En qué planes está disponible?','Solo Enterprise. Kydesk asigna Kyros IA explícitamente a cada tenant Enterprise tras revisión.'],
            ],
        ],
        'csat' => [
            'title' => 'CSAT / NPS',
            'tagline' => 'Encuestas post-resolución · Mide la satisfacción real de tus clientes',
            'icon' => 'smile',
            'color' => '#f59e0b',
            'bg' => '#fef3c7',
            'category' => 'OPERACIONES',
            'hero_kpis' => [['Escalas', 'CSAT 1-5 + NPS 0-10'], ['Auto-trigger', 'Al resolver'], ['Disponible', 'Todos los planes']],
            'description' => 'Encuestas automáticas que se disparan al resolver tickets. Tu cliente recibe un email con un link a una encuesta mobile-first, califica con emojis (CSAT 1-5) o escala de recomendación (NPS 0-10), y deja un comentario opcional. El dashboard muestra NPS Score, % satisfechos, distribución y comentarios en tiempo real.',
            'steps' => [
                ['Configurá la encuesta', 'Subject del email, intro, mensaje de agradecimiento, demora en minutos después de resolver.', 'settings-2'],
                ['Cliente califica', 'Email con link a /csat/{token}. Página pública mobile-first con emojis/escala. 30 segundos para responder.', 'star'],
                ['Dashboard en tiempo real', 'NPS Score (promotores - detractores), CSAT % satisfechos, distribución por estrellas, últimas respuestas.', 'bar-chart-3'],
            ],
            'benefits' => [
                ['emoji','CSAT con emojis','Escala 1-5 con 😡😞😐🙂😍 — mucho más friendly que estrellas planas.'],
                ['trending-up','NPS profesional','Promotores (9-10), Pasivos (7-8), Detractores (0-6) — métrica estándar de la industria.'],
                ['zap','Auto-trigger','Hook automático en estado resolved · sin configuración por ticket.'],
                ['message-square','Comentarios libres','Texto libre opcional · feedback cualitativo además del score.'],
            ],
            'mockup' => 'csat',
            'faqs' => [
                ['¿Las encuestas son anónimas?','El email del cliente queda asociado a la respuesta para seguimiento. Solo el equipo del workspace lo ve.'],
                ['¿Puedo enviar encuesta manualmente?','Sí. Botón "Enviar encuesta" en cada ticket resuelto.'],
                ['¿CSAT y NPS al mismo tiempo?','Sí. Cada uno con su config independiente. Podés activar uno, otro, o ambos.'],
                ['¿Customizo el branding?','Subject, texto intro y agradecimiento personalizables. Color y logo siguen tu tema del workspace.'],
            ],
        ],
        'itsm' => [
            'title' => 'ITSM · ITIL',
            'tagline' => 'Service Catalog, Change Management, Problems · Para equipos enterprise',
            'icon' => 'workflow',
            'color' => '#0284c7',
            'bg' => '#dbeafe',
            'category' => 'IT MANAGEMENT',
            'hero_kpis' => [['Approvals', 'Multi-step'], ['ITIL', 'v4 compliant'], ['Disponible', 'Business+']],
            'description' => 'IT Service Management completo siguiendo ITIL v4: Service Catalog con SLA por item, Change Requests con tipo/riesgo/impacto/rollback plan, multi-step approvals donde todos los aprobadores deben aprobar para que el change pase a "approved", y Problem Management con root cause/workaround/known error.',
            'steps' => [
                ['Service Catalog', 'Definí items de servicio (Solicitar VPN, Reset password, etc.) con SLA, categoría y aprobador específico.', 'package'],
                ['Change Requests', 'Submit changes con tipo standard/normal/emergency, riesgo y plan de rollback. Asigna aprobadores.', 'git-pull-request'],
                ['Approvals + ejecución', 'Aprobadores aprueban/rechazan con comentario. Cuando todos aprueban, el change pasa a scheduled/in_progress/completed.', 'check-circle'],
            ],
            'benefits' => [
                ['package','Service Catalog','Items con SLA, departamento, aprobador. Genera tickets pre-categorizados.'],
                ['git-pull-request','Change Management','Borrador → Pendiente → Aprobado/Rechazado → Programado → En curso → Completado/Fallido.'],
                ['users','Multi-step Approvals','Todos los aprobadores deben aprobar. Comentarios obligatorios en rechazos.'],
                ['bug','Problem Management','Distinto de tickets: tracks root causes, workarounds y known errors.'],
                ['shield','Risk + Impact matrix','Bajo/Medio/Alto en ambas dimensiones — usá la combinación para priorizar.'],
            ],
            'mockup' => 'itsm',
            'faqs' => [
                ['¿Es ITIL v3 o v4?','ITIL v4 — usamos los términos modernos (Change Requests, Problems, Service Catalog) sin los procesos pesados de v3.'],
                ['¿Puedo definir múltiples aprobadores?','Sí. Multi-step: hasta que todos aprueben, el change queda pending. Cualquier rechazo lo manda a rejected.'],
                ['¿Se integra con tickets normales?','Sí. La columna tickets.change_id liga tickets a un Change Request específico.'],
                ['¿Qué tipo de empresa lo usa?','Cualquier equipo IT con cambios planeados (deploys, mantenimientos, migraciones) que necesita governance.'],
            ],
        ],
        'time_tracking' => [
            'title' => 'Time Tracking',
            'tagline' => 'Cronómetro por ticket · Integrado a Igualas · Descuenta horas automáticamente',
            'icon' => 'timer',
            'color' => '#dc2626',
            'bg' => '#fee2e2',
            'category' => 'OPERACIONES',
            'hero_kpis' => [['Auto-deduct', 'Sí'], ['Manual entry', 'Sí'], ['Disponible', 'Pro+']],
            'description' => 'Cronómetro real por ticket con un click. Inicia un timer, hace su trabajo, lo detiene — el tiempo queda registrado y, si la empresa del ticket tiene una iguala activa, se descuenta automáticamente del período abierto. Manual entry también para registrar tiempo retroactivo. Filtros por usuario, rango de fechas, facturable/no.',
            'steps' => [
                ['Click en "Iniciar timer"', 'Desde cualquier ticket. Elegís tarifa horaria y si es facturable. Solo un timer running por agente.', 'play-circle'],
                ['Trabajás', 'El cronómetro corre en background. Si abrís otro timer, detiene el anterior automáticamente.', 'timer'],
                ['Detener → consumo', 'El tiempo queda registrado y, si hay iguala activa, descuenta horas del período abierto y recalcula excedente.', 'stop-circle'],
            ],
            'benefits' => [
                ['timer','Cronómetro en vivo','UI Alpine.js con HH:MM:SS actualizado cada segundo. Visible en sidebar del ticket.'],
                ['handshake','Integración Igualas','Auto-detecta iguala de la empresa del ticket. Descuenta del período abierto y recalcula excedente.'],
                ['plus','Manual entry','Registrá tiempo retroactivo con horas, fecha de inicio, descripción y tarifa.'],
                ['filter','Filtros y reportes','Por usuario, rango de fechas, facturable/no — KPIs de horas totales, billable y monto.'],
            ],
            'mockup' => 'time_tracking',
            'faqs' => [
                ['¿Qué pasa si olvido detener el timer?','Queda corriendo en background. Cualquier nuevo timer auto-detiene el anterior — no se duplican.'],
                ['¿Funciona sin Igualas?','Sí. El time tracking funciona standalone. Si hay iguala activa, se integra; si no, registra tiempo solo.'],
                ['¿Puedo cobrar diferente a distintos clientes?','Sí. La tarifa se setea al iniciar cada timer. Podés tener tarifas distintas por cliente/iguala.'],
            ],
        ],
        'status_page' => [
            'title' => 'Status Page Pública',
            'tagline' => 'Comunicá incidentes y disponibilidad a tus clientes · Suscripción por email',
            'icon' => 'activity',
            'color' => '#16a34a',
            'bg' => '#d1fae5',
            'category' => 'COMUNICACIÓN',
            'hero_kpis' => [['Components', 'Ilimitados'], ['Subscribers', 'Email opt-in'], ['Disponible', 'Todos los planes']],
            'description' => 'Página pública de estado tipo statuspage.io en /status/{slug}. Define los componentes de tu producto, reportá incidentes con timeline de updates (investigando → identified → monitoring → resolved), y los suscriptores reciben un email automático cada vez que publicás un update. Estado general calculado del peor componente.',
            'steps' => [
                ['Definí componentes', 'API, Portal, Dashboard, Database — los servicios visibles en tu status page con su estado actual.', 'server'],
                ['Reportá incidentes', 'Severidad minor/major/critical, descripción, componentes afectados. Cambia estado de componentes en cascada.', 'megaphone'],
                ['Updates en timeline', 'Investigando → Identified → Monitoring → Resolved. Cada update notifica suscriptores por email.', 'clock'],
            ],
            'benefits' => [
                ['globe','Pública en /status/{slug}','URL pública branded · sin login · ideal para compartir en banners y redes.'],
                ['bell','Suscripción por email','Double opt-in con confirmación. Notificación automática en cada update + resolved.'],
                ['layers','Components con cascada','Cambiá estado de componentes al reportar incidente · estado general se recalcula.'],
                ['history','Historial público','Últimos 12 incidentes resueltos visibles para transparencia.'],
            ],
            'mockup' => 'status_page',
            'faqs' => [
                ['¿La status page tiene mi dominio?','En esta versión usa el dominio de Kydesk con tu slug. Custom domain en próxima release.'],
                ['¿Los suscriptores ven incidentes privados?','No. Solo notifica los marcados como is_public. Podés crear incidentes internos solo para tu equipo.'],
                ['¿Hay severidad de mantenimiento programado?','Sí. Severidad "maintenance" — los suscriptores entienden que es planificado.'],
            ],
        ],
        'customer_portal' => [
            'title' => 'Customer Portal con Login',
            'tagline' => 'Tus clientes acceden con email/password · Histórico autenticado',
            'icon' => 'lock-keyhole',
            'color' => '#7c5cff',
            'bg' => '#f3e8ff',
            'category' => 'PORTAL',
            'hero_kpis' => [['Auth completo', 'Login + Reset'], ['Verificación', 'Email opt-in'], ['Disponible', 'Todos los planes']],
            'description' => 'Portal autenticado donde tus clientes ven SOLO sus tickets, históricamente. Sistema completo: registro, verificación email, login, forgot/reset password, edición de perfil. Vinculación opcional con company. Las empresas grandes ya no piden tokens manuales — sus usuarios entran con sus credenciales y ven el histórico.',
            'steps' => [
                ['Cliente se registra', 'En /portal/{slug}/register. Email + password + opcional company. Confirma vía email.', 'user-plus'],
                ['Login con credenciales', 'Email + password. Sesión persistente. Forgot password con reset por email.', 'log-in'],
                ['Ve sus tickets', 'Dashboard con sus tickets (filtra por portal_user_id o requester_email). Stats: abiertos, resueltos, total.', 'inbox'],
            ],
            'benefits' => [
                ['lock-keyhole','Auth completo','Registro + verify email + login + forgot/reset password + perfil — UX moderna.'],
                ['building-2','Vinculación con empresa','Asocia el cliente a una company para reportes agregados.'],
                ['user-cog','Gestión desde admin','Super tenant ve y gestiona usuarios del portal: activar/desactivar/eliminar.'],
                ['shield','Aislamiento por tenant','Cada tenant tiene su propio set de portal_users. Sin filtración cross-tenant.'],
            ],
            'mockup' => 'customer_portal',
            'faqs' => [
                ['¿Coexiste con el portal anónimo?','Sí. Los tickets creados sin login siguen funcionando con tokens. El login es opcional para historial.'],
                ['¿Es OAuth o email/password?','Email/password con bcrypt cost 12. OAuth (Google, MS) en próxima release.'],
                ['¿Puedo restringir registro?','En esta versión es abierto. Próximamente: invite-only y SSO para empresas.'],
            ],
        ],
        'reports_builder' => [
            'title' => 'Reports Builder',
            'tagline' => 'Constructor visual de reportes · 10 widgets · Filtros guardados',
            'icon' => 'bar-chart-3',
            'color' => '#7e22ce',
            'bg' => '#f3e8ff',
            'category' => 'ANALYTICS',
            'hero_kpis' => [['Widgets', '10 builtins'], ['Filtros', 'Guardados'], ['Disponible', 'Business+']],
            'description' => 'Crea reportes personalizados arrastrando widgets a un canvas: tickets por estado/prioridad/categoría/agente, evolución por día, tiempo medio de resolución, cumplimiento SLA, CSAT score, top empresas. Filtros de fecha guardados, marcar como favorito, compartir con el equipo, programar envío por email.',
            'steps' => [
                ['Elegí widgets', '10 widgets: donut, bar, line, KPI, table — agregá los que necesites desde el panel lateral.', 'layout-dashboard'],
                ['Configurá filtros', 'Rango de fechas guardado por reporte. Aplica a todos los widgets.', 'filter'],
                ['Compartí o programá', 'Marcá como favorito, compartí con el equipo, agendá envío por email a stakeholders.', 'send'],
            ],
            'benefits' => [
                ['pie-chart','Donut + Bar + Line','Distribuciones, rankings, evolución temporal — los 3 tipos básicos cubiertos.'],
                ['gauge','KPIs','Tiempo medio resolución, % SLA cumplido, tickets abiertos, CSAT score.'],
                ['table','Top tables','Top 10 empresas/agentes por volumen — ranking inline con barras.'],
                ['arrows-up-down','Reordenable','Drag-up/down de widgets. Tu reporte, tu orden.'],
            ],
            'mockup' => 'reports_builder',
            'faqs' => [
                ['¿Puedo crear widgets custom?','Los 10 builtins cubren 90% de casos. Widgets custom con SQL en próxima release.'],
                ['¿Los reportes se exportan?','Por ahora visualización en pantalla. Export PDF/Excel en próxima release.'],
                ['¿Los reportes scheduled funcionan ya?','La config se guarda; el cron de envío se está integrando con la cola de jobs.'],
            ],
        ],
        'custom_fields' => [
            'title' => 'Custom Fields',
            'tagline' => 'Campos personalizados por categoría · 10 tipos · Globales o específicos',
            'icon' => 'list-plus',
            'color' => '#06b6d4',
            'bg' => '#cffafe',
            'category' => 'PERSONALIZACIÓN',
            'hero_kpis' => [['Tipos', '10'], ['Por categoría', 'Sí'], ['Disponible', 'Todos los planes']],
            'description' => 'Extendé los tickets con campos personalizados. Definí campos globales (visibles en todos los tickets) o filtrados por categoría específica. 10 tipos: text, textarea, number, date, select, multiselect, checkbox, url, email, phone. Required, visible en portal, orden, opciones, placeholder, help text — todo configurable desde la UI.',
            'steps' => [
                ['Definí el campo', 'Label, tipo, opciones (si select), categoría, required, visible en portal.', 'list-plus'],
                ['Aparece en tickets', 'Los tickets de la categoría correcta muestran el campo en create + show.', 'edit-3'],
                ['Datos estructurados', 'Los valores quedan en ticket_field_values — buscables, exportables, integrables con reportes.', 'database'],
            ],
            'benefits' => [
                ['type','10 tipos completos','text, textarea, number, date, select, multiselect, checkbox, url, email, phone.'],
                ['filter','Por categoría','Limitá campos a categorías específicas. Ej: campos "Modelo" y "Serial" solo para Hardware.'],
                ['eye','Visible en portal','Decidí si el cliente ve el campo en el portal público o solo es interno del equipo.'],
                ['list-checks','Required','Marcá campos obligatorios — el formulario los valida antes de crear el ticket.'],
            ],
            'mockup' => 'custom_fields',
            'faqs' => [
                ['¿Los valores van en JSON o columnas?','En tabla relacional ticket_field_values con field_id + value. Performance ok hasta millones de tickets.'],
                ['¿Puedo cambiar el tipo de un campo?','Sí, pero los valores existentes quedan como string. Cambios destructivos requieren confirmación.'],
                ['¿Filtro tickets por custom field?','En el roadmap de la próxima release.'],
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
