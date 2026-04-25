<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\License;

class LicenseController extends Controller
{
    public function locked(array $params): void
    {
        $tenant = $this->requireTenant($params['slug'], allowLocked: true);
        $status = License::status($tenant);
        if ($status['is_usable']) {
            $this->redirect('/t/' . $tenant->slug . '/dashboard');
        }
        $supportEmail = License::settingStr('saas_support_email', 'soporte@kydesk.com');
        $billingEmail = License::settingStr('saas_billing_email', $supportEmail);
        $saasName     = License::settingStr('saas_name', 'Kydesk');

        $this->render('errors/license_locked', [
            'title'        => 'Licencia requerida',
            'license'      => $status,
            'tenant'       => $tenant,
            'supportEmail' => $supportEmail,
            'billingEmail' => $billingEmail,
            'saasName'     => $saasName,
        ], 'auth');
    }
}
