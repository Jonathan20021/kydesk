<?php
namespace App\Controllers\Admin;

class SettingsController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('settings.view');
        $rows = $this->db->all('SELECT `key`, `value` FROM saas_settings');
        $settings = [];
        foreach ($rows as $r) $settings[$r['key']] = $r['value'];
        $this->render('admin/settings/index', [
            'title' => 'Ajustes',
            'pageHeading' => 'Ajustes del SaaS',
            'settings' => $settings,
        ]);
    }

    public function update(): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $allowed = [
            'saas_name','saas_company','saas_support_email','saas_billing_email',
            'saas_currency','saas_tax_rate','saas_invoice_prefix',
            'saas_default_plan','saas_default_trial_days','saas_allow_registration',
            'saas_terms_url','saas_privacy_url',
        ];
        foreach ($allowed as $key) {
            $value = (string)$this->input($key, '');
            $exists = $this->db->val('SELECT `key` FROM saas_settings WHERE `key` = ?', [$key]);
            if ($exists) {
                $this->db->update('saas_settings', ['value' => $value], '`key` = :key', ['key' => $key]);
            } else {
                $this->db->insert('saas_settings', ['key' => $key, 'value' => $value]);
            }
        }
        $this->superAuth->log('settings.update');
        $this->session->flash('success', 'Ajustes guardados.');
        $this->redirect('/admin/settings');
    }
}
