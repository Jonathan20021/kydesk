<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\DemoSeeder;

class DemoController extends Controller
{
    public function picker(): void
    {
        $plans = DemoSeeder::PLANS;
        $ttl = DemoSeeder::TTL_HOURS;
        $this->render('demo/picker', compact('plans', 'ttl'), 'public');
    }

    public function start(array $params): void
    {
        $this->validateCsrf();
        $plan = $params['plan'] ?? 'pro';
        if (!isset(DemoSeeder::PLANS[$plan])) $plan = 'pro';

        $seeder = new DemoSeeder($this->db);
        try {
            $seeder->cleanup();
            $info = $seeder->create($plan);
        } catch (\Throwable $e) {
            $this->session->flash('error', 'No se pudo crear el demo: ' . $e->getMessage());
            $this->redirect('/demo');
            return;
        }

        $this->auth->login($info['user_id']);
        $this->session->flash('success', '¡Bienvenido al demo ' . DemoSeeder::PLANS[$plan]['label'] . '! Tu workspace expira en 24 h.');
        $this->session->put('demo_credentials', ['email' => $info['email'], 'password' => $info['password']]);
        $this->redirect('/t/' . $info['slug'] . '/dashboard');
    }

    public function cleanup(): void
    {
        $seeder = new DemoSeeder($this->db);
        $count = $seeder->cleanup();
        $this->json(['ok' => true, 'cleaned' => $count]);
    }
}
