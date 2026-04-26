<?php
namespace App\Controllers\Admin;

use App\Core\Mailer;

class SettingsController extends AdminController
{
    public function testEmail(): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $to = trim((string)$this->input('test_to', ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email de prueba inválido.');
            $this->redirect('/admin/settings');
        }
        $inner = '<p>Este es un correo de prueba enviado desde el panel super admin.</p>'
            . '<p>Si lo recibiste, tu configuración de email está funcionando correctamente.</p>'
            . '<p><strong>Hora del servidor:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        $res = (new Mailer())->send($to, 'Prueba de envío · Kydesk Helpdesk', Mailer::template('Correo de prueba', $inner));
        if ($res['ok']) {
            $this->session->flash('success', 'Correo enviado vía ' . $res['driver'] . (isset($res['id']) ? ' (id: ' . $res['id'] . ')' : ''));
        } else {
            $this->session->flash('error', 'Falló el envío: ' . ($res['error'] ?? 'desconocido'));
        }
        $this->redirect('/admin/settings');
    }

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
            // Email
            'mail_driver','mail_from_email','mail_from_name','mail_reply_to',
            'resend_api_key',
            'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_secure',
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
