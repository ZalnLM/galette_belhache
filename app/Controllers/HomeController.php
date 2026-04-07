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
        ]);
    }
}
