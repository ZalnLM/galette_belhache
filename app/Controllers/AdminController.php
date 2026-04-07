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

    public function storeUser(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'utilisateur'], true) ? $_POST['role'] : 'utilisateur';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            Flash::set('danger', 'Tous les champs de creation sont obligatoires.');
            header('Location: /admin/users');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('danger', 'Adresse email invalide.');
            header('Location: /admin/users');
            exit;
        }

        if (mb_strlen($password) < 10) {
            Flash::set('danger', 'Le mot de passe doit contenir au moins 10 caracteres.');
            header('Location: /admin/users');
            exit;
        }

        $existing = $this->db->query('SELECT id FROM users WHERE email = ? LIMIT 1', [$email])->fetch();
        if ($existing) {
            Flash::set('danger', 'Un utilisateur existe deja avec cet email.');
            header('Location: /admin/users');
            exit;
        }

        $this->db->query(
            'INSERT INTO users (first_name, last_name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, ?)',
            [$firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT), $role, $isActive]
        );

        Flash::set('success', 'Compte cree.');
        header('Location: /admin/users');
        exit;
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

    public function ingredients(): void
    {
        Auth::requireAdmin();

        $ingredients = $this->db->query(
            'SELECT i.*, u.name AS purchase_unit_name, u.symbol AS purchase_unit_symbol,
                    (
                        SELECT COUNT(*)
                        FROM recipe_ingredients ri
                        WHERE ri.ingredient_id = i.id
                    ) AS recipe_usage_count,
                    CASE
                        WHEN i.purchase_price <= 0 OR i.is_active = 0 THEN 1
                        ELSE 0
                    END AS needs_completion
             FROM ingredients i
             JOIN units u ON u.id = i.purchase_unit_id
             ORDER BY needs_completion DESC, i.name ASC'
        )->fetchAll();

        $units = $this->db->query(
            'SELECT * FROM units WHERE is_active = 1 ORDER BY family ASC, sort_order ASC, name ASC'
        )->fetchAll();

        View::render('admin/ingredients', [
            'pageTitle' => 'Ingredients',
            'ingredients' => $ingredients,
            'units' => $units,
        ]);
    }

    public function storeIngredient(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $name = trim((string)($_POST['name'] ?? ''));
        $purchaseQuantity = (float)($_POST['purchase_quantity'] ?? 0);
        $purchaseUnitId = (int)($_POST['purchase_unit_id'] ?? 0);
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $purchaseQuantity <= 0 || $purchaseUnitId <= 0) {
            Flash::set('danger', 'Nom, quantite de reference et unite sont obligatoires.');
            header('Location: /admin/ingredients');
            exit;
        }

        $exists = $this->db->query(
            'SELECT id FROM ingredients WHERE name = ? LIMIT 1',
            [$name]
        )->fetch();

        if ($exists) {
            Flash::set('danger', 'Un ingredient avec ce nom existe deja.');
            header('Location: /admin/ingredients');
            exit;
        }

        $this->db->query(
            'INSERT INTO ingredients (name, purchase_quantity, purchase_unit_id, purchase_price, is_active) VALUES (?, ?, ?, ?, ?)',
            [$name, $purchaseQuantity, $purchaseUnitId, $purchasePrice, $isActive]
        );

        Flash::set('success', 'Ingredient ajoute.');
        header('Location: /admin/ingredients');
        exit;
    }

    public function updateIngredient(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $name = trim((string)($_POST['name'] ?? ''));
        $purchaseQuantity = (float)($_POST['purchase_quantity'] ?? 0);
        $purchaseUnitId = (int)($_POST['purchase_unit_id'] ?? 0);
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $purchaseQuantity <= 0 || $purchaseUnitId <= 0) {
            Flash::set('danger', 'Nom, quantite de reference et unite sont obligatoires.');
            header('Location: /admin/ingredients');
            exit;
        }

        $exists = $this->db->query(
            'SELECT id FROM ingredients WHERE name = ? AND id <> ? LIMIT 1',
            [$name, (int)$id]
        )->fetch();

        if ($exists) {
            Flash::set('danger', 'Un autre ingredient porte deja ce nom.');
            header('Location: /admin/ingredients');
            exit;
        }

        $this->db->query(
            'UPDATE ingredients
             SET name = ?, purchase_quantity = ?, purchase_unit_id = ?, purchase_price = ?, is_active = ?
             WHERE id = ?',
            [$name, $purchaseQuantity, $purchaseUnitId, $purchasePrice, $isActive, (int)$id]
        );

        Flash::set('success', 'Ingredient mis a jour.');
        header('Location: /admin/ingredients');
        exit;
    }

    public function deleteIngredient(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $usageCount = (int)$this->db->query(
            'SELECT COUNT(*) FROM recipe_ingredients WHERE ingredient_id = ?',
            [(int)$id]
        )->fetchColumn();

        if ($usageCount > 0) {
            Flash::set('danger', 'Impossible de supprimer cet ingredient car il est deja utilise dans une ou plusieurs recettes.');
            header('Location: /admin/ingredients');
            exit;
        }

        $this->db->query('DELETE FROM ingredients WHERE id = ?', [(int)$id]);
        Flash::set('success', 'Ingredient supprime.');
        header('Location: /admin/ingredients');
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

        $ingredients = $this->db->query('SELECT i.id, i.name, i.purchase_unit_id, u.symbol AS purchase_unit FROM ingredients i JOIN units u ON u.id = i.purchase_unit_id ORDER BY i.name ASC')->fetchAll();

        View::render('admin/recipes', [
            'pageTitle' => 'Recettes',
            'recipes' => $recipes,
            'ingredients' => $ingredients,
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
        $createdIngredients = $this->syncRecipeIngredients($recipeId, $_POST['ingredient_name'] ?? [], $_POST['ingredient_quantity'] ?? []);

        if ($createdIngredients !== []) {
            Flash::set('warning', 'Recette ajoutee. Pense a completer les informations des nouveaux ingredients crees : ' . implode(', ', $createdIngredients) . '.');
        } else {
            Flash::set('success', 'Recette ajoutee.');
        }
        header('Location: /admin/recipes');
        exit;
    }

    public function editRecipe(string $id): void
    {
        Auth::requireAdmin();

        $recipe = $this->db->query('SELECT * FROM recipes WHERE id = ?', [(int)$id])->fetch();
        if (!$recipe) {
            http_response_code(404);
            exit('Recette introuvable.');
        }

        $recipeIngredients = $this->db->query(
            'SELECT ri.*, i.name AS ingredient_name
             FROM recipe_ingredients ri
             JOIN ingredients i ON i.id = ri.ingredient_id
             WHERE ri.recipe_id = ?
             ORDER BY ri.id ASC',
            [(int)$id]
        )->fetchAll();

        $ingredients = $this->db->query('SELECT i.id, i.name, i.purchase_unit_id, u.symbol AS purchase_unit FROM ingredients i JOIN units u ON u.id = i.purchase_unit_id ORDER BY i.name ASC')->fetchAll();
        $formulaUsageCount = (int)$this->db->query('SELECT COUNT(*) FROM formula_items WHERE recipe_id = ?', [(int)$id])->fetchColumn();

        View::render('admin/recipe_edit', [
            'pageTitle' => 'Modifier recette',
            'recipe' => $recipe,
            'recipeIngredients' => $recipeIngredients,
            'ingredients' => $ingredients,
            'formulaUsageCount' => $formulaUsageCount,
        ]);
    }

    public function updateRecipe(string $id): void
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
            header('Location: /admin/recipes/' . (int)$id . '/edit');
            exit;
        }

        $this->db->query(
            'UPDATE recipes
             SET name = ?, category = ?, description = ?, selling_price = ?, display_order = ?, is_active = ?
             WHERE id = ?',
            [$name, $category, $description, $sellingPrice, $displayOrder, $isActive, (int)$id]
        );

        $createdIngredients = $this->syncRecipeIngredients((int)$id, $_POST['ingredient_name'] ?? [], $_POST['ingredient_quantity'] ?? []);

        if ($createdIngredients !== []) {
            Flash::set('warning', 'Recette mise a jour. Pense a completer les informations des nouveaux ingredients crees : ' . implode(', ', $createdIngredients) . '.');
        } else {
            Flash::set('success', 'Recette mise a jour.');
        }
        header('Location: /admin/recipes');
        exit;
    }

    public function deleteRecipe(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $formulaUsageCount = (int)$this->db->query('SELECT COUNT(*) FROM formula_items WHERE recipe_id = ?', [(int)$id])->fetchColumn();
        if ($formulaUsageCount > 0) {
            Flash::set('danger', 'Impossible de supprimer cette recette car elle est utilisee dans une ou plusieurs formules.');
            header('Location: /admin/recipes/' . (int)$id . '/edit');
            exit;
        }

        $this->db->query('DELETE FROM recipe_ingredients WHERE recipe_id = ?', [(int)$id]);
        $this->db->query('DELETE FROM recipes WHERE id = ?', [(int)$id]);

        Flash::set('success', 'Recette supprimee.');
        header('Location: /admin/recipes');
        exit;
    }

    private function syncRecipeIngredients(int $recipeId, array $ingredientNames, array $quantities): array
    {
        $this->db->query('DELETE FROM recipe_ingredients WHERE recipe_id = ?', [$recipeId]);
        $createdIngredients = [];

        foreach ($ingredientNames as $index => $ingredientName) {
            $ingredientName = trim((string)$ingredientName);
            $quantity = (float)($quantities[$index] ?? 0);
            if ($ingredientName !== '' && $quantity > 0) {
                [$ingredientId, $wasCreated] = $this->resolveOrCreateIngredient($ingredientName);
                if ($wasCreated) {
                    $createdIngredients[] = $ingredientName;
                }
                $unitId = (int)$this->db->query(
                    'SELECT purchase_unit_id FROM ingredients WHERE id = ?',
                    [$ingredientId]
                )->fetchColumn();
                if ($unitId <= 0) {
                    continue;
                }
                $this->db->query(
                    'INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)',
                    [$recipeId, $ingredientId, $quantity, $unitId]
                );
            }
        }

        return array_values(array_unique($createdIngredients));
    }

    private function resolveOrCreateIngredient(string $ingredientName): array
    {
        $existingId = (int)$this->db->query(
            'SELECT id FROM ingredients WHERE LOWER(name) = LOWER(?) LIMIT 1',
            [$ingredientName]
        )->fetchColumn();

        if ($existingId > 0) {
            return [$existingId, false];
        }

        $defaultUnitId = (int)$this->db->query(
            "SELECT id FROM units WHERE symbol = 'unite' LIMIT 1"
        )->fetchColumn();

        if ($defaultUnitId <= 0) {
            throw new RuntimeException('Unite par defaut introuvable pour la creation d ingredient.');
        }

        $this->db->query(
            'INSERT INTO ingredients (name, purchase_quantity, purchase_unit_id, purchase_price, is_active) VALUES (?, 1, ?, 0, 0)',
            [$ingredientName, $defaultUnitId]
        );

        return [$this->db->lastInsertId(), true];
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
        $this->syncFormulaItems($formulaId, $_POST['recipe_id'] ?? [], $_POST['recipe_quantity'] ?? []);

        Flash::set('success', 'Formule ajoutee.');
        header('Location: /admin/formulas');
        exit;
    }

    public function editFormula(string $id): void
    {
        Auth::requireAdmin();

        $formula = $this->db->query('SELECT * FROM formulas WHERE id = ?', [(int)$id])->fetch();
        if (!$formula) {
            http_response_code(404);
            exit('Formule introuvable.');
        }

        $formulaItems = $this->db->query(
            'SELECT fi.*, r.name AS recipe_name
             FROM formula_items fi
             JOIN recipes r ON r.id = fi.recipe_id
             WHERE fi.formula_id = ?
             ORDER BY fi.id ASC',
            [(int)$id]
        )->fetchAll();

        $recipes = $this->db->query('SELECT id, name, category, selling_price FROM recipes WHERE is_active = 1 ORDER BY display_order ASC, name ASC')->fetchAll();
        $quoteUsageCount = (int)$this->db->query('SELECT COUNT(*) FROM quote_request_formulas WHERE formula_id = ?', [(int)$id])->fetchColumn();

        View::render('admin/formula_edit', [
            'pageTitle' => 'Modifier formule',
            'formula' => $formula,
            'formulaItems' => $formulaItems,
            'recipes' => $recipes,
            'quoteUsageCount' => $quoteUsageCount,
        ]);
    }

    public function updateFormula(string $id): void
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
            header('Location: /admin/formulas/' . (int)$id . '/edit');
            exit;
        }

        $this->db->query(
            'UPDATE formulas
             SET name = ?, description = ?, price_per_person = ?, minimum_guests = ?, is_price_visible = ?, display_order = ?, is_active = ?
             WHERE id = ?',
            [$name, $description, $pricePerPerson, $minimumGuests, $isPriceVisible, $displayOrder, $isActive, (int)$id]
        );

        $this->syncFormulaItems((int)$id, $_POST['recipe_id'] ?? [], $_POST['recipe_quantity'] ?? []);

        Flash::set('success', 'Formule mise a jour.');
        header('Location: /admin/formulas');
        exit;
    }

    public function deleteFormula(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $quoteUsageCount = (int)$this->db->query('SELECT COUNT(*) FROM quote_request_formulas WHERE formula_id = ?', [(int)$id])->fetchColumn();
        if ($quoteUsageCount > 0) {
            Flash::set('danger', 'Impossible de supprimer cette formule car elle est deja utilisee dans une ou plusieurs demandes de devis.');
            header('Location: /admin/formulas/' . (int)$id . '/edit');
            exit;
        }

        $this->db->query('DELETE FROM formula_items WHERE formula_id = ?', [(int)$id]);
        $this->db->query('DELETE FROM formulas WHERE id = ?', [(int)$id]);

        Flash::set('success', 'Formule supprimee.');
        header('Location: /admin/formulas');
        exit;
    }

    private function syncFormulaItems(int $formulaId, array $recipeIds, array $quantities): void
    {
        $this->db->query('DELETE FROM formula_items WHERE formula_id = ?', [$formulaId]);

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
    }

    public function quoteRequests(): void
    {
        Auth::requireAdmin();
        $requests = $this->db->query(
            'SELECT qr.*, u.first_name, u.last_name, u.email,
                    q.id AS quote_id, q.quote_number, q.status AS quote_status, q.sale_total
             FROM quote_requests qr
             JOIN users u ON u.id = qr.user_id
             LEFT JOIN quotes q ON q.quote_request_id = qr.id
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

        $quote = $this->findQuoteByRequestId((int)$id);

        View::render('admin/quote_request_show', [
            'pageTitle' => 'Detail demande',
            'request' => $request,
            'formulas' => $formulas,
            'messages' => $messages,
            'quote' => $quote,
        ]);
    }

    public function createQuoteFromRequest(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $request = $this->db->query('SELECT * FROM quote_requests WHERE id = ?', [(int)$id])->fetch();
        if (!$request) {
            http_response_code(404);
            exit('Demande introuvable.');
        }

        $existingQuote = $this->findQuoteByRequestId((int)$id);
        if ($existingQuote) {
            header('Location: /admin/quotes/' . (int)$existingQuote['id']);
            exit;
        }

        $quoteNumber = $this->generateQuoteNumber();
        $validUntil = (new DateTimeImmutable('+30 days'))->format('Y-m-d');
        $eventDate = (string)$request['event_date'];

        $this->db->query(
            'INSERT INTO quotes
                (quote_request_id, quote_number, valid_until, event_date, internal_cost_total, sale_total, fixed_fees_total, deposit_amount, legal_notes, terms_conditions, status, created_by)
             VALUES (?, ?, ?, ?, 0, 0, 0, 0, ?, ?, ?, ?)',
            [
                (int)$id,
                $quoteNumber,
                $validUntil,
                $eventDate,
                "Prix net en euros. Cout interne non visible par le client.",
                "Acompte et conditions a confirmer avant envoi.",
                'draft',
                (int)Auth::user()['id'],
            ]
        );

        $quoteId = $this->db->lastInsertId();

        $formulaRows = $this->db->query(
            'SELECT qrf.*, f.name
             FROM quote_request_formulas qrf
             JOIN formulas f ON f.id = qrf.formula_id
             WHERE qrf.quote_request_id = ?
             ORDER BY qrf.id ASC',
            [(int)$id]
        )->fetchAll();

        foreach ($formulaRows as $row) {
            $quantity = (float)$row['guest_count'];
            $unitPrice = (float)$row['price_per_person_snapshot'];
            $this->db->query(
                'INSERT INTO quote_lines (quote_id, line_type, label, quantity, unit_price, total_price, formula_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $quoteId,
                    'formula',
                    (string)$row['name'],
                    $quantity,
                    $unitPrice,
                    $quantity * $unitPrice,
                    (int)$row['formula_id'],
                ]
            );
        }

        $this->recalculateQuoteTotals($quoteId);

        if ((string)$request['status'] === 'demande_recue') {
            $this->db->query('UPDATE quote_requests SET status = ? WHERE id = ?', ['en_cours_etude', (int)$id]);
        }

        Flash::set('success', 'Devis initialise a partir de la demande.');
        header('Location: /admin/quotes/' . $quoteId);
        exit;
    }

    public function quotes(): void
    {
        Auth::requireAdmin();

        $quotes = $this->db->query(
            'SELECT q.*, qr.event_name, qr.event_date, u.first_name, u.last_name
             FROM quotes q
             JOIN quote_requests qr ON qr.id = q.quote_request_id
             JOIN users u ON u.id = qr.user_id
             ORDER BY q.created_at DESC'
        )->fetchAll();

        View::render('admin/quotes', [
            'pageTitle' => 'Devis',
            'quotes' => $quotes,
        ]);
    }

    public function editQuote(string $id): void
    {
        Auth::requireAdmin();

        $quote = $this->db->query(
            'SELECT q.*, qr.event_name, qr.event_type, qr.event_date AS request_event_date, qr.total_guests, qr.address, qr.phone,
                    qr.status AS request_status, qr.id AS request_id, u.first_name, u.last_name, u.email
             FROM quotes q
             JOIN quote_requests qr ON qr.id = q.quote_request_id
             JOIN users u ON u.id = qr.user_id
             WHERE q.id = ?',
            [(int)$id]
        )->fetch();

        if (!$quote) {
            http_response_code(404);
            exit('Devis introuvable.');
        }

        $lines = $this->db->query(
            'SELECT ql.*, ff.name AS fixed_fee_name
             FROM quote_lines ql
             LEFT JOIN fixed_fees ff ON ff.id = ql.fixed_fee_id
             WHERE ql.quote_id = ?
             ORDER BY ql.id ASC',
            [(int)$id]
        )->fetchAll();

        $fixedFees = $this->db->query(
            'SELECT * FROM fixed_fees WHERE is_active = 1 ORDER BY name ASC'
        )->fetchAll();

        View::render('admin/quote_edit', [
            'pageTitle' => 'Modifier devis',
            'quote' => $quote,
            'formulaLines' => array_values(array_filter($lines, static fn (array $line): bool => $line['line_type'] === 'formula')),
            'fixedFeeLines' => array_values(array_filter($lines, static fn (array $line): bool => $line['line_type'] === 'fixed_fee')),
            'freeFeeLines' => array_values(array_filter($lines, static fn (array $line): bool => $line['line_type'] === 'free_fee')),
            'fixedFees' => $fixedFees,
        ]);
    }

    public function updateQuote(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $quote = $this->db->query('SELECT * FROM quotes WHERE id = ?', [(int)$id])->fetch();
        if (!$quote) {
            http_response_code(404);
            exit('Devis introuvable.');
        }

        $validUntil = trim((string)($_POST['valid_until'] ?? ''));
        $eventDate = trim((string)($_POST['event_date'] ?? ''));
        $depositAmount = (float)($_POST['deposit_amount'] ?? 0);
        $status = (string)($_POST['status'] ?? 'draft');
        $legalNotes = trim((string)($_POST['legal_notes'] ?? ''));
        $termsConditions = trim((string)($_POST['terms_conditions'] ?? ''));

        $allowedStatuses = ['draft', 'sent', 'accepted', 'refused', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            Flash::set('danger', 'Statut de devis invalide.');
            header('Location: /admin/quotes/' . (int)$id);
            exit;
        }

        $this->db->query(
            'UPDATE quotes
             SET valid_until = ?, event_date = ?, deposit_amount = ?, legal_notes = ?, terms_conditions = ?, status = ?
             WHERE id = ?',
            [$validUntil !== '' ? $validUntil : null, $eventDate !== '' ? $eventDate : null, $depositAmount, $legalNotes, $termsConditions, $status, (int)$id]
        );

        $this->syncQuoteLines(
            (int)$id,
            $_POST['formula_formula_id'] ?? [],
            $_POST['formula_label'] ?? [],
            $_POST['formula_quantity'] ?? [],
            $_POST['formula_unit_price'] ?? [],
            $_POST['fixed_fee_id'] ?? [],
            $_POST['fixed_fee_quantity'] ?? [],
            $_POST['fixed_fee_unit_price'] ?? [],
            $_POST['free_fee_label'] ?? [],
            $_POST['free_fee_quantity'] ?? [],
            $_POST['free_fee_unit_price'] ?? []
        );

        $this->recalculateQuoteTotals((int)$id);
        $this->syncQuoteRequestStatusFromQuote((int)$quote['quote_request_id'], $status);

        Flash::set('success', 'Devis mis a jour.');
        header('Location: /admin/quotes/' . (int)$id);
        exit;
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

    private function findQuoteByRequestId(int $requestId): ?array
    {
        $quote = $this->db->query(
            'SELECT * FROM quotes WHERE quote_request_id = ? ORDER BY id DESC LIMIT 1',
            [$requestId]
        )->fetch();

        return $quote ?: null;
    }

    private function generateQuoteNumber(): string
    {
        $year = (new DateTimeImmutable())->format('Y');
        $count = (int)$this->db->query(
            "SELECT COUNT(*) FROM quotes WHERE quote_number LIKE ?",
            ['LG-' . $year . '-%']
        )->fetchColumn();

        return sprintf('LG-%s-%04d', $year, $count + 1);
    }

    private function syncQuoteLines(
        int $quoteId,
        array $formulaIds,
        array $formulaLabels,
        array $formulaQuantities,
        array $formulaUnitPrices,
        array $fixedFeeIds,
        array $fixedFeeQuantities,
        array $fixedFeeUnitPrices,
        array $freeFeeLabels,
        array $freeFeeQuantities,
        array $freeFeeUnitPrices
    ): void {
        $this->db->query('DELETE FROM quote_lines WHERE quote_id = ?', [$quoteId]);

        foreach ($formulaIds as $index => $formulaId) {
            $formulaId = (int)$formulaId;
            $label = trim((string)($formulaLabels[$index] ?? ''));
            $quantity = (float)($formulaQuantities[$index] ?? 0);
            $unitPrice = (float)($formulaUnitPrices[$index] ?? 0);
            if ($formulaId <= 0 || $label === '' || $quantity <= 0) {
                continue;
            }

            $this->db->query(
                'INSERT INTO quote_lines (quote_id, line_type, label, quantity, unit_price, total_price, formula_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$quoteId, 'formula', $label, $quantity, $unitPrice, $quantity * $unitPrice, $formulaId]
            );
        }

        foreach ($fixedFeeIds as $index => $fixedFeeId) {
            $fixedFeeId = (int)$fixedFeeId;
            $quantity = (float)($fixedFeeQuantities[$index] ?? 0);
            $unitPrice = (float)($fixedFeeUnitPrices[$index] ?? 0);
            if ($fixedFeeId <= 0 || $quantity <= 0) {
                continue;
            }

            $label = (string)$this->db->query('SELECT name FROM fixed_fees WHERE id = ? LIMIT 1', [$fixedFeeId])->fetchColumn();
            if ($label === '') {
                continue;
            }

            $this->db->query(
                'INSERT INTO quote_lines (quote_id, line_type, label, quantity, unit_price, total_price, fixed_fee_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$quoteId, 'fixed_fee', $label, $quantity, $unitPrice, $quantity * $unitPrice, $fixedFeeId]
            );
        }

        foreach ($freeFeeLabels as $index => $label) {
            $label = trim((string)$label);
            $quantity = (float)($freeFeeQuantities[$index] ?? 0);
            $unitPrice = (float)($freeFeeUnitPrices[$index] ?? 0);
            if ($label === '' || $quantity <= 0) {
                continue;
            }

            $this->db->query(
                'INSERT INTO quote_lines (quote_id, line_type, label, quantity, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$quoteId, 'free_fee', $label, $quantity, $unitPrice, $quantity * $unitPrice]
            );
        }
    }

    private function recalculateQuoteTotals(int $quoteId): void
    {
        $saleTotal = (float)$this->db->query(
            'SELECT COALESCE(SUM(total_price), 0) FROM quote_lines WHERE quote_id = ?',
            [$quoteId]
        )->fetchColumn();

        $fixedFeesTotal = (float)$this->db->query(
            "SELECT COALESCE(SUM(total_price), 0) FROM quote_lines WHERE quote_id = ? AND line_type IN ('fixed_fee', 'free_fee')",
            [$quoteId]
        )->fetchColumn();

        $formulaLines = $this->db->query(
            "SELECT formula_id, quantity
             FROM quote_lines
             WHERE quote_id = ? AND line_type = 'formula' AND formula_id IS NOT NULL",
            [$quoteId]
        )->fetchAll();

        $internalCostTotal = 0.0;
        foreach ($formulaLines as $line) {
            $internalCostTotal += $this->estimateFormulaInternalCost((int)$line['formula_id'], (float)$line['quantity']);
        }

        $this->db->query(
            'UPDATE quotes
             SET internal_cost_total = ?, sale_total = ?, fixed_fees_total = ?
             WHERE id = ?',
            [$internalCostTotal, $saleTotal, $fixedFeesTotal, $quoteId]
        );
    }

    private function estimateFormulaInternalCost(int $formulaId, float $guestCount): float
    {
        $formula = $this->db->query(
            'SELECT minimum_guests FROM formulas WHERE id = ? LIMIT 1',
            [$formulaId]
        )->fetch();

        if (!$formula) {
            return 0.0;
        }

        $baseInternalCost = (float)$this->db->query(
            'SELECT COALESCE(SUM(recipe_cost.internal_cost * fi.quantity), 0)
             FROM formula_items fi
             JOIN (
                 SELECT r.id,
                        COALESCE(SUM(
                            CASE
                                WHEN u.family = iu.family THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                                WHEN u.family = "mass" AND iu.family = "mass" THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                                WHEN u.family = "volume" AND iu.family = "volume" THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                                WHEN u.family = "count" AND iu.family = "count" THEN ri.quantity / NULLIF(i.purchase_quantity, 0) * i.purchase_price
                                ELSE 0
                            END
                        ), 0) AS internal_cost
                 FROM recipes r
                 LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id
                 LEFT JOIN ingredients i ON i.id = ri.ingredient_id
                 LEFT JOIN units u ON u.id = ri.unit_id
                 LEFT JOIN units iu ON iu.id = i.purchase_unit_id
                 GROUP BY r.id
             ) AS recipe_cost ON recipe_cost.id = fi.recipe_id
             WHERE fi.formula_id = ?',
            [$formulaId]
        )->fetchColumn();

        $minimumGuests = max(1, (int)$formula['minimum_guests']);
        $multiplier = max(0.0, $guestCount) / $minimumGuests;

        return $baseInternalCost * $multiplier;
    }

    private function syncQuoteRequestStatusFromQuote(int $requestId, string $quoteStatus): void
    {
        $mapping = [
            'draft' => 'en_cours_etude',
            'sent' => 'devis_envoye',
            'accepted' => 'devis_accepte',
            'refused' => 'devis_refuse',
            'cancelled' => 'annule',
        ];

        $requestStatus = $mapping[$quoteStatus] ?? 'en_cours_etude';
        $this->db->query('UPDATE quote_requests SET status = ? WHERE id = ?', [$requestStatus, $requestId]);
    }
}
