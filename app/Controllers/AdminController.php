<?php
declare(strict_types=1);

class AdminController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function dashboard(): void
    {
        Auth::requireAdmin();

        $stats = [
            'users' => (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'recipes' => (int)$this->db->query('SELECT COUNT(*) FROM recipes')->fetchColumn(),
            'formulas' => (int)$this->db->query('SELECT COUNT(*) FROM formulas')->fetchColumn(),
            'quote_requests' => (int)$this->db->query('SELECT COUNT(*) FROM quote_requests')->fetchColumn(),
        ];

        $latestRequests = $this->db->query(
            'SELECT qr.id, qr.event_name, qr.event_date, qr.status, u.first_name, u.last_name
             FROM quote_requests qr
             JOIN users u ON u.id = qr.user_id
             ORDER BY qr.created_at DESC
             LIMIT 8'
        )->fetchAll();

        View::render('admin/dashboard', [
            'pageTitle' => 'Administration',
            'stats' => $stats,
            'latestRequests' => $latestRequests,
        ]);
    }

    public function users(): void
    {
        Auth::requireAdmin();
        $query = trim((string)($_GET['q'] ?? ''));
        $params = [];
        $sql = 'SELECT * FROM users';
        if ($query !== '') {
            $sql .= ' WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?';
            $params = ['%' . $query . '%', '%' . $query . '%', '%' . $query . '%'];
        }
        $sql .= ' ORDER BY created_at DESC';
        $users = $this->db->query($sql, $params)->fetchAll();

        View::render('admin/users', [
            'pageTitle' => 'Utilisateurs',
            'users' => $users,
            'query' => $query,
        ]);
    }

    public function updateUser(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $role = in_array($_POST['role'] ?? '', ['admin', 'utilisateur'], true) ? $_POST['role'] : 'utilisateur';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $this->db->query(
            'UPDATE users SET role = ?, is_active = ? WHERE id = ?',
            [$role, $isActive, (int)$id]
        );

        Flash::set('success', 'Utilisateur mis a jour.');
        header('Location: /admin/users');
        exit;
    }

    public function recipes(): void
    {
        Auth::requireAdmin();
        $recipes = $this->db->query(
            'SELECT r.*, 
                    (SELECT COALESCE(SUM(
                        CASE
                            WHEN u.family = iu.family THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                            WHEN u.family = "mass" AND iu.family = "mass" THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                            WHEN u.family = "volume" AND iu.family = "volume" THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                            WHEN u.family = "count" AND iu.family = "count" THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                            ELSE 0
                        END
                    ), 0)
                     FROM recipe_ingredients ri
                     JOIN ingredients i ON i.id = ri.ingredient_id
                     JOIN units u ON u.id = ri.unit_id
                     JOIN units iu ON iu.id = i.purchase_unit_id
                     WHERE ri.recipe_id = r.id) AS internal_cost
             FROM recipes r
             ORDER BY r.display_order ASC, r.name ASC'
        )->fetchAll();

        $ingredients = $this->db->query('SELECT i.id, i.name, u.symbol AS purchase_unit FROM ingredients i JOIN units u ON u.id = i.purchase_unit_id ORDER BY i.name ASC')->fetchAll();
        $units = $this->db->query('SELECT * FROM units ORDER BY family ASC, sort_order ASC, name ASC')->fetchAll();

        View::render('admin/recipes', [
            'pageTitle' => 'Recettes',
            'recipes' => $recipes,
            'ingredients' => $ingredients,
            'units' => $units,
        ]);
    }

    public function storeRecipe(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $name = trim((string)($_POST['name'] ?? ''));
        $category = in_array($_POST['category'] ?? '', ['sale', 'sucre'], true) ? $_POST['category'] : 'sale';
        $description = trim((string)($_POST['description'] ?? ''));
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            Flash::set('danger', 'Le nom de la recette est obligatoire.');
            header('Location: /admin/recipes');
            exit;
        }

        $this->db->query(
            'INSERT INTO recipes (name, category, description, selling_price, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $category, $description, $sellingPrice, $displayOrder, $isActive]
        );

        $recipeId = $this->db->lastInsertId();
        $ingredientIds = $_POST['ingredient_id'] ?? [];
        $quantities = $_POST['ingredient_quantity'] ?? [];
        $unitIds = $_POST['ingredient_unit_id'] ?? [];

        foreach ($ingredientIds as $index => $ingredientId) {
            $ingredientId = (int)$ingredientId;
            $quantity = (float)($quantities[$index] ?? 0);
            $unitId = (int)($unitIds[$index] ?? 0);
            if ($ingredientId > 0 && $quantity > 0 && $unitId > 0) {
                $this->db->query(
                    'INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)',
                    [$recipeId, $ingredientId, $quantity, $unitId]
                );
            }
        }

        Flash::set('success', 'Recette ajoutee.');
        header('Location: /admin/recipes');
        exit;
    }

    public function formulas(): void
    {
        Auth::requireAdmin();
        $formulas = $this->db->query(
            "SELECT f.*,
                    GROUP_CONCAT(CONCAT(r.name, ' x', fi.quantity) ORDER BY r.display_order, r.name SEPARATOR ' • ') AS recipe_summary
             FROM formulas f
             LEFT JOIN formula_items fi ON fi.formula_id = f.id
             LEFT JOIN recipes r ON r.id = fi.recipe_id
             GROUP BY f.id
             ORDER BY f.display_order ASC, f.name ASC"
        )->fetchAll();

        $recipes = $this->db->query('SELECT id, name, category, selling_price FROM recipes WHERE is_active = 1 ORDER BY display_order ASC, name ASC')->fetchAll();

        View::render('admin/formulas', [
            'pageTitle' => 'Formules',
            'formulas' => $formulas,
            'recipes' => $recipes,
        ]);
    }

    public function storeFormula(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $pricePerPerson = (float)($_POST['price_per_person'] ?? 0);
        $minimumGuests = (int)($_POST['minimum_guests'] ?? 1);
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isPriceVisible = isset($_POST['is_price_visible']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            Flash::set('danger', 'Le nom de la formule est obligatoire.');
            header('Location: /admin/formulas');
            exit;
        }

        $this->db->query(
            'INSERT INTO formulas (name, description, price_per_person, minimum_guests, is_price_visible, display_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$name, $description, $pricePerPerson, $minimumGuests, $isPriceVisible, $displayOrder, $isActive]
        );

        $formulaId = $this->db->lastInsertId();
        $recipeIds = $_POST['recipe_id'] ?? [];
        $quantities = $_POST['recipe_quantity'] ?? [];
        foreach ($recipeIds as $index => $recipeId) {
            $recipeId = (int)$recipeId;
            $quantity = (int)($quantities[$index] ?? 0);
            if ($recipeId > 0 && $quantity > 0) {
                $this->db->query(
                    'INSERT INTO formula_items (formula_id, recipe_id, quantity) VALUES (?, ?, ?)',
                    [$formulaId, $recipeId, $quantity]
                );
            }
        }

        Flash::set('success', 'Formule ajoutee.');
        header('Location: /admin/formulas');
        exit;
    }

    public function quoteRequests(): void
    {
        Auth::requireAdmin();
        $requests = $this->db->query(
            'SELECT qr.*, u.first_name, u.last_name, u.email
             FROM quote_requests qr
             JOIN users u ON u.id = qr.user_id
             ORDER BY qr.created_at DESC'
        )->fetchAll();

        View::render('admin/quote_requests', [
            'pageTitle' => 'Demandes de devis',
            'requests' => $requests,
        ]);
    }

    public function showQuoteRequest(string $id): void
    {
        Auth::requireAdmin();
        $request = $this->db->query(
            'SELECT qr.*, u.first_name, u.last_name, u.email
             FROM quote_requests qr
             JOIN users u ON u.id = qr.user_id
             WHERE qr.id = ?',
            [(int)$id]
        )->fetch();

        if (!$request) {
            http_response_code(404);
            exit('Demande introuvable.');
        }

        $formulas = $this->db->query(
            'SELECT qrf.*, f.name
             FROM quote_request_formulas qrf
             JOIN formulas f ON f.id = qrf.formula_id
             WHERE qrf.quote_request_id = ?',
            [(int)$id]
        )->fetchAll();

        $messages = $this->db->query(
            'SELECT qm.*, u.first_name, u.last_name
             FROM quote_messages qm
             JOIN users u ON u.id = qm.sender_user_id
             WHERE qm.quote_request_id = ?
             ORDER BY qm.created_at ASC',
            [(int)$id]
        )->fetchAll();

        View::render('admin/quote_request_show', [
            'pageTitle' => 'Detail demande',
            'request' => $request,
            'formulas' => $formulas,
            'messages' => $messages,
        ]);
    }

    public function updateQuoteRequestStatus(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $status = (string)($_POST['status'] ?? '');
        $allowed = ['demande_recue', 'en_cours_etude', 'devis_envoye', 'devis_accepte', 'devis_refuse', 'annule'];
        if (!in_array($status, $allowed, true)) {
            Flash::set('danger', 'Statut invalide.');
            header('Location: /admin/quote-requests/' . (int)$id);
            exit;
        }

        $this->db->query('UPDATE quote_requests SET status = ? WHERE id = ?', [$status, (int)$id]);
        Flash::set('success', 'Statut mis a jour.');
        header('Location: /admin/quote-requests/' . (int)$id);
        exit;
    }

    public function storeQuoteRequestMessage(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '') {
            Flash::set('danger', 'Le message est vide.');
            header('Location: /admin/quote-requests/' . (int)$id);
            exit;
        }

        $this->db->query(
            'INSERT INTO quote_messages (quote_request_id, sender_user_id, is_admin, body) VALUES (?, ?, 1, ?)',
            [(int)$id, (int)Auth::user()['id'], $body]
        );

        Flash::set('success', 'Message admin envoye.');
        header('Location: /admin/quote-requests/' . (int)$id);
        exit;
    }
}
