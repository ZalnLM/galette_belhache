<?php
declare(strict_types=1);

class HomeController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $settingsRows = $this->db->query(
            'SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN (?, ?, ?)',
            ['home_hero_title', 'home_hero_text', 'home_hero_image']
        )->fetchAll();
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[(string)$row['setting_key']] = $row['setting_value'];
        }

        $formulas = $this->db->query(
            "SELECT f.*,
                    GROUP_CONCAT(CONCAT(r.name, ' x', fi.quantity) ORDER BY r.display_order, r.name SEPARATOR ' • ') AS recipe_summary
             FROM formulas f
             LEFT JOIN formula_items fi ON fi.formula_id = f.id
             LEFT JOIN recipes r ON r.id = fi.recipe_id
             WHERE f.is_active = 1
             GROUP BY f.id
             ORDER BY f.display_order ASC, f.name ASC"
        )->fetchAll();

        View::render('home/index', [
            'pageTitle' => 'Formules',
            'formulas' => $formulas,
            'heroTitle' => (string)($settings['home_hero_title'] ?? 'Choisis tes formules et envoie une demande de devis'),
            'heroText' => (string)($settings['home_hero_text'] ?? 'Chaque formule affiche son contenu, son minimum de convives et, si l admin le souhaite, son prix par personne.'),
            'heroImage' => (string)($settings['home_hero_image'] ?? ''),
        ]);
    }
}
