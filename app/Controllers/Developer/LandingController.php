<?php
namespace App\Controllers\Developer;

class LandingController extends DeveloperController
{
    public function index(): void
    {
        if ($this->devAuth->check()) {
            $this->redirect('/developers/dashboard');
        }
        $plans = [];
        try {
            $plans = $this->db->all("SELECT * FROM dev_plans WHERE is_active=1 AND is_public=1 ORDER BY sort_order ASC, price_monthly ASC");
        } catch (\Throwable $e) { /* tabla puede no existir aún */ }
        echo $this->view->render('developers/landing/index', [
            'title' => 'Developers · Kydesk API',
            'plans' => $plans,
            'enabled' => $this->setting('dev_portal_enabled', '1') === '1',
            'allowRegistration' => $this->setting('dev_portal_allow_registration', '1') === '1',
            'portalName' => $this->setting('dev_portal_name', 'Kydesk Developers'),
            'tagline' => $this->setting('dev_portal_tagline', 'API helpdesk para construir tus apps'),
        ], 'developers_public');
    }

    public function pricing(): void
    {
        $plans = [];
        try {
            $plans = $this->db->all("SELECT * FROM dev_plans WHERE is_active=1 AND is_public=1 ORDER BY sort_order ASC, price_monthly ASC");
        } catch (\Throwable $e) {}
        echo $this->view->render('developers/landing/pricing', [
            'title' => 'Planes API · Developers',
            'plans' => $plans,
        ], 'developers_public');
    }

    public function docs(): void
    {
        echo $this->view->render('developers/landing/docs', [
            'title' => 'Documentación API · Developers',
        ], 'developers_public');
    }

    protected function setting(string $key, ?string $default = null): ?string
    {
        try {
            $row = $this->db->one('SELECT `value` FROM saas_settings WHERE `key` = ?', [$key]);
            return $row['value'] ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
