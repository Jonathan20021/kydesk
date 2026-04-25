<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Prefs;

class PreferencesController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        Prefs::ensureSchema($this->db);
        $user = $this->auth->user();
        $prefs = Prefs::get($user);
        $this->render('preferences/index', [
            'title' => 'Personalizar panel',
            'prefs' => $prefs,
            'accents' => Prefs::ACCENT_PRESETS,
            'wallpapers' => Prefs::WALLPAPERS,
        ]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->validateCsrf();
        $userId = $this->auth->userId();
        if (!$userId) $this->redirect('/auth/login');

        $incoming = [];
        foreach (Prefs::DEFAULTS as $k => $default) {
            $raw = $this->input($k, null);
            if (is_int($default)) {
                $incoming[$k] = ($raw === null || $raw === '' || $raw === '0' || $raw === 0 || $raw === false) ? 0 : 1;
            } else {
                $incoming[$k] = $raw === null ? $default : (string)$raw;
            }
        }
        Prefs::save($this->db, $userId, $incoming);
        $this->session->flash('success', 'Preferencias guardadas. Tu panel ya está actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/preferences');
    }

    public function reset(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->validateCsrf();
        $userId = $this->auth->userId();
        if (!$userId) $this->redirect('/auth/login');
        Prefs::save($this->db, $userId, Prefs::DEFAULTS);
        $this->session->flash('success', 'Preferencias restauradas a los valores por defecto.');
        $this->redirect('/t/' . $tenant->slug . '/preferences');
    }
}
