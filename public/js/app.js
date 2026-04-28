// Kydesk — client JS
(function () {
    'use strict';

    /* ─────────── Flash toast autohide ─────────── */
    document.querySelectorAll('.fixed.top-4.right-4').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .35s, transform .35s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-12px)';
            setTimeout(() => el.remove(), 400);
        }, 4200);
    });

    /* ─────────── Detección de SO ─────────── */
    const ua = navigator.userAgent || '';
    const isMac = /Mac|iPhone|iPad|iPod/.test(ua);
    const isWin = /Windows/.test(ua);
    const isLinux = !isMac && !isWin && /Linux/.test(ua);
    window.KYDESK_OS = isMac ? 'mac' : (isWin ? 'win' : (isLinux ? 'linux' : 'win'));

    /* ─────────── Helpers de navegación ─────────── */
    function tenantBase() {
        const m = location.pathname.match(/^\/t\/([^\/]+)/);
        return m ? '/t/' + m[1] : null;
    }
    function adminBase() {
        return location.pathname.startsWith('/admin') ? '/admin' : null;
    }
    function navTenant(path) {
        const base = tenantBase();
        if (!base) return false;
        location.href = base + path;
        return true;
    }
    function navAdmin(path) {
        const base = adminBase();
        if (!base) return false;
        location.href = base + path;
        return true;
    }
    function clickIfExists(selector) {
        const el = document.querySelector(selector);
        if (el) { el.click(); return true; }
        return false;
    }
    function focusSearch() {
        const el = document.querySelector('.search-pill input, input[type="search"], input[name="q"]');
        if (!el) return false;
        if (el.readOnly) {
            const body = document.body;
            if (body.__x && body.__x.$data) { body.__x.$data.cmd = true; return true; }
        }
        el.focus();
        return true;
    }
    function isTyping(target) {
        if (!target) return false;
        const tag = (target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return true;
        if (target.isContentEditable) return true;
        if (target.closest && target.closest('[contenteditable="true"]')) return true;
        return false;
    }

    /* ─────────── Toast helper ─────────── */
    function toast(msg, kind) {
        kind = kind || 'info';
        const colors = {
            info: { bg: '#0f0d18', icon: '✓', icColor: '#a78bfa' },
            warn: { bg: '#0f0d18', icon: '!', icColor: '#fbbf24' },
            err:  { bg: '#0f0d18', icon: '×', icColor: '#f87171' },
        };
        const c = colors[kind] || colors.info;
        const el = document.createElement('div');
        el.style.cssText = 'position:fixed;bottom:80px;right:20px;background:' + c.bg + ';color:white;padding:10px 16px;border-radius:14px;font-size:13px;font-weight:500;box-shadow:0 12px 32px -10px rgba(0,0,0,.4);z-index:90;display:flex;align-items:center;gap:8px;transition:transform .25s,opacity .25s;transform:translateY(20px);opacity:0';
        el.innerHTML = '<span style="color:' + c.icColor + ';font-weight:700">' + c.icon + '</span><span>' + msg + '</span>';
        document.body.appendChild(el);
        requestAnimationFrame(() => { el.style.transform = 'translateY(0)'; el.style.opacity = '1'; });
        setTimeout(() => {
            el.style.transform = 'translateY(20px)';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 280);
        }, 1600);
    }

    /* ─────────── Mapa G + letra ─────────── */
    const TENANT_GO = {
        'd': '/dashboard',
        't': '/tickets',
        'b': '/tickets/board',
        'n': '/notes',
        'o': '/todos',
        'k': '/kb',
        'e': '/companies',
        'c': '/categories',
        'a': '/assets',
        'u': '/users',
        'r': '/reports',
        'm': '/meetings',
        'i': '/retainers',
        's': '/settings',
        'p': '/profile',
        'h': '/help',
        'l': '/audit',
        'y': '/automations',
        'g': '/integrations',
        'q': '/sla',
        'f': '/custom-fields',
        'x': '/itsm',
    };
    const ADMIN_GO = {
        'd': '/dashboard',
        't': '/tenants',
        'p': '/plans',
        's': '/subscriptions',
        'i': '/invoices',
        'u': '/users',
        'r': '/reports',
        'c': '/changelog',
        'h': '/super-admins',
        'a': '/audit',
        'g': '/settings',
    };

    /* ─────────── Estado y handler global ─────────── */
    let pendingG = false;
    let pendingGTimer = null;

    document.addEventListener('keydown', (e) => {
        const meta = e.metaKey || e.ctrlKey;
        const key = (e.key || '').toLowerCase();

        // ── ⌘K / Ctrl+K — siempre disponible (incluso dentro de inputs)
        if (meta && !e.shiftKey && !e.altKey && key === 'k') {
            const body = document.body;
            if (body.__x && body.__x.$data && 'cmd' in body.__x.$data) {
                e.preventDefault();
                body.__x.$data.cmd = !body.__x.$data.cmd;
                return;
            }
        }
        // ── ⌘/Ctrl+Shift+P — paleta alt (estilo VSCode)
        if (meta && e.shiftKey && key === 'p') {
            const body = document.body;
            if (body.__x && body.__x.$data && 'cmd' in body.__x.$data) {
                e.preventDefault();
                body.__x.$data.cmd = true;
                return;
            }
        }
        // ── ⌘/Ctrl+B — toggle sidebar
        if (meta && !e.shiftKey && !e.altKey && key === 'b') {
            const body = document.body;
            if (body.__x && body.__x.$data && typeof body.__x.$data.toggleSidebar === 'function') {
                e.preventDefault();
                body.__x.$data.toggleSidebar();
                return;
            }
        }
        // ── ⌘/Ctrl+/ — abre atajos
        if (meta && !e.shiftKey && key === '/') {
            const body = document.body;
            if (body.__x && body.__x.$data && 'shortcuts' in body.__x.$data) {
                e.preventDefault();
                body.__x.$data.shortcuts = true;
                return;
            }
        }
        // ── ⌘/Ctrl+, — abre settings
        if (meta && !e.shiftKey && key === ',') {
            if (tenantBase()) { e.preventDefault(); navTenant('/settings'); return; }
            if (adminBase())  { e.preventDefault(); navAdmin('/settings'); return; }
        }
        // ── ⌘/Ctrl+Enter — enviar formulario activo (reply, comment)
        if (meta && key === 'enter') {
            const form = e.target.closest && e.target.closest('form');
            if (form) {
                const submit = form.querySelector('button[type="submit"], button:not([type])');
                if (submit && !submit.disabled) {
                    e.preventDefault();
                    submit.click();
                    return;
                }
            }
        }

        // A partir de aquí ignoramos si está escribiendo
        if (isTyping(e.target)) return;

        // ── ? — atajos (Shift + /)
        if (e.shiftKey && key === '?') {
            const body = document.body;
            if (body.__x && body.__x.$data && 'shortcuts' in body.__x.$data) {
                e.preventDefault();
                body.__x.$data.shortcuts = true;
                return;
            }
        }
        // ── / — focus búsqueda (sin modificadores)
        if (!meta && !e.shiftKey && !e.altKey && key === '/') {
            if (focusSearch()) { e.preventDefault(); return; }
        }
        // ── Esc — cerrar drawers (Alpine ya escucha .escape)

        // ── Secuencia G + letra
        if (pendingG && !meta && !e.altKey) {
            clearTimeout(pendingGTimer);
            pendingG = false;
            const inAdmin = !!adminBase();
            const inTenant = !!tenantBase();
            const map = inAdmin ? ADMIN_GO : (inTenant ? TENANT_GO : null);
            if (map && map[key]) {
                e.preventDefault();
                if (inAdmin) navAdmin(map[key]); else navTenant(map[key]);
                return;
            }
            return;
        }
        if (key === 'g' && !meta && !e.shiftKey && !e.altKey) {
            pendingG = true;
            toast('G… esperando segunda tecla', 'info');
            pendingGTimer = setTimeout(() => { pendingG = false; }, 1500);
            return;
        }

        // ── Acciones de tecla simple (cuando no estás escribiendo)
        if (!meta && !e.shiftKey && !e.altKey) {
            switch (key) {
                case 'c': // C — crear nuevo (ticket en general)
                    if (clickIfExists('a[href*="/tickets/create"]')) { e.preventDefault(); return; }
                    if (clickIfExists('a[data-shortcut="create"]')) { e.preventDefault(); return; }
                    break;
                case 'a': // A — asignarme (en ticket)
                    if (clickIfExists('button[data-shortcut="assign-me"], a[data-shortcut="assign-me"]')) { e.preventDefault(); return; }
                    break;
                case 'r': // R — resolver ticket
                    if (clickIfExists('button[data-shortcut="resolve"], a[data-shortcut="resolve"]')) { e.preventDefault(); return; }
                    break;
                case 'e': // E — escalar
                    if (clickIfExists('button[data-shortcut="escalate"], a[data-shortcut="escalate"]')) { e.preventDefault(); return; }
                    break;
                case 'm': // M — abrir menú de macros (si existe)
                    if (clickIfExists('button[data-shortcut="macros"]')) { e.preventDefault(); return; }
                    break;
            }
        }
    });

    /* ─────────── Renderizado de teclas según SO ─────────── */
    const KEY_LABELS = {
        mac:   { MOD: '⌘', SHIFT: '⇧', ALT: '⌥', CTRL: '⌃', META: '⌘', RETURN: '⏎', ESC: 'esc', SPACE: '␣', UP: '↑', DOWN: '↓', LEFT: '←', RIGHT: '→' },
        win:   { MOD: 'Ctrl', SHIFT: 'Shift', ALT: 'Alt', CTRL: 'Ctrl', META: 'Win', RETURN: 'Enter', ESC: 'Esc', SPACE: 'Space', UP: '↑', DOWN: '↓', LEFT: '←', RIGHT: '→' },
        linux: { MOD: 'Ctrl', SHIFT: 'Shift', ALT: 'Alt', CTRL: 'Ctrl', META: 'Super', RETURN: 'Enter', ESC: 'Esc', SPACE: 'Space', UP: '↑', DOWN: '↓', LEFT: '←', RIGHT: '→' },
    };
    window.KYDESK_KEYS = KEY_LABELS;
    window.KYDESK_RENDER_COMBO = function (combo, os) {
        const dict = KEY_LABELS[os] || KEY_LABELS.win;
        return combo.split('+').map(k => {
            const up = k.toUpperCase();
            if (dict[up]) return dict[up];
            return k.length === 1 ? k.toUpperCase() : k;
        });
    };

    /* ─────────── Prevent double submit ─────────── */
    document.querySelectorAll('form').forEach(f => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type="submit"], button:not([type])');
            if (btn && !btn.disabled) {
                setTimeout(() => { btn.disabled = true; btn.style.opacity = '.65'; btn.style.cursor = 'wait'; }, 10);
            }
        });
    });

    /* ─────────── Reveal fallback (sin GSAP) ─────────── */
    if (!window.gsap) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(en => {
                if (en.isIntersecting) {
                    en.target.style.transition = 'opacity .7s ease, transform .7s cubic-bezier(.16,1,.3,1)';
                    en.target.style.opacity = '1';
                    en.target.style.transform = 'translateY(0)';
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px' });
        document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el));
    }

    if (window.gsap) {
        try { gsap.registerPlugin(window.ScrollTrigger); } catch (e) {}
    }
})();
