<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';

$db = Database::getInstance();

function findId(Database $db, string $table, string $column, string $value): ?int
{
    $row = $db->query("SELECT id FROM {$table} WHERE {$column} = ? LIMIT 1", [$value])->fetch();
    return $row ? (int)$row['id'] : null;
}

function ensureUser(Database $db, array $data): int
{
    $existing = $db->query('SELECT id FROM users WHERE email = ? LIMIT 1', [$data['email']])->fetch();
    if ($existing) {
        $db->query(
            'UPDATE users SET first_name = ?, last_name = ?, password_hash = ?, role = ?, is_active = ? WHERE id = ?',
            [$data['first_name'], $data['last_name'], $data['password_hash'], $data['role'], $data['is_active'], (int)$existing['id']]
        );
        return (int)$existing['id'];
    }

    $db->query(
        'INSERT INTO users (first_name, last_name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, ?)',
        [$data['first_name'], $data['last_name'], $data['email'], $data['password_hash'], $data['role'], $data['is_active']]
    );
    return $db->lastInsertId();
}

function ensureIngredient(Database $db, array $data): int
{
    $existing = $db->query('SELECT id FROM ingredients WHERE name = ? LIMIT 1', [$data['name']])->fetch();
    if ($existing) {
        $db->query(
            'UPDATE ingredients SET purchase_quantity = ?, purchase_unit_id = ?, purchase_price = ?, is_active = 1 WHERE id = ?',
            [$data['purchase_quantity'], $data['purchase_unit_id'], $data['purchase_price'], (int)$existing['id']]
        );
        return (int)$existing['id'];
    }

    $db->query(
        'INSERT INTO ingredients (name, purchase_quantity, purchase_unit_id, purchase_price, is_active) VALUES (?, ?, ?, ?, 1)',
        [$data['name'], $data['purchase_quantity'], $data['purchase_unit_id'], $data['purchase_price']]
    );
    return $db->lastInsertId();
}

function ensureRecipe(Database $db, array $recipe, array $items): int
{
    $existing = $db->query('SELECT id FROM recipes WHERE name = ? LIMIT 1', [$recipe['name']])->fetch();
    if ($existing) {
        $recipeId = (int)$existing['id'];
        $db->query(
            'UPDATE recipes SET category = ?, description = ?, selling_price = ?, is_active = ?, display_order = ? WHERE id = ?',
            [$recipe['category'], $recipe['description'], $recipe['selling_price'], $recipe['is_active'], $recipe['display_order'], $recipeId]
        );
        $db->query('DELETE FROM recipe_ingredients WHERE recipe_id = ?', [$recipeId]);
    } else {
        $db->query(
            'INSERT INTO recipes (name, category, description, selling_price, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?)',
            [$recipe['name'], $recipe['category'], $recipe['description'], $recipe['selling_price'], $recipe['is_active'], $recipe['display_order']]
        );
        $recipeId = $db->lastInsertId();
    }

    foreach ($items as $item) {
        $db->query(
            'INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)',
            [$recipeId, $item['ingredient_id'], $item['quantity'], $item['unit_id']]
        );
    }

    return $recipeId;
}

function ensureFormula(Database $db, array $formula, array $items): int
{
    $existing = $db->query('SELECT id FROM formulas WHERE name = ? LIMIT 1', [$formula['name']])->fetch();
    if ($existing) {
        $formulaId = (int)$existing['id'];
        $db->query(
            'UPDATE formulas SET description = ?, price_per_person = ?, minimum_guests = ?, is_price_visible = ?, is_active = ?, display_order = ? WHERE id = ?',
            [$formula['description'], $formula['price_per_person'], $formula['minimum_guests'], $formula['is_price_visible'], $formula['is_active'], $formula['display_order'], $formulaId]
        );
        $db->query('DELETE FROM formula_items WHERE formula_id = ?', [$formulaId]);
    } else {
        $db->query(
            'INSERT INTO formulas (name, description, price_per_person, minimum_guests, is_price_visible, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$formula['name'], $formula['description'], $formula['price_per_person'], $formula['minimum_guests'], $formula['is_price_visible'], $formula['is_active'], $formula['display_order']]
        );
        $formulaId = $db->lastInsertId();
    }

    foreach ($items as $item) {
        $db->query(
            'INSERT INTO formula_items (formula_id, recipe_id, quantity) VALUES (?, ?, ?)',
            [$formulaId, $item['recipe_id'], $item['quantity']]
        );
    }

    return $formulaId;
}

$unitIds = [
    'g' => findId($db, 'units', 'symbol', 'g'),
    'kg' => findId($db, 'units', 'symbol', 'kg'),
    'ml' => findId($db, 'units', 'symbol', 'ml'),
    'L' => findId($db, 'units', 'symbol', 'L'),
    'unite' => findId($db, 'units', 'symbol', 'unite'),
    'tranche' => findId($db, 'units', 'symbol', 'tranche'),
];

foreach ($unitIds as $symbol => $id) {
    if ($id === null) {
        throw new RuntimeException('Unite manquante dans la base : ' . $symbol);
    }
}

$adminId = ensureUser($db, [
    'first_name' => 'Adrien',
    'last_name' => 'Belhache',
    'email' => 'enligne@belhache.net',
    'password_hash' => password_hash('oM@Eb3#To9QcG^', PASSWORD_DEFAULT),
    'role' => 'admin',
    'is_active' => 1,
]);

$demoUserId = ensureUser($db, [
    'first_name' => 'Camille',
    'last_name' => 'Martin',
    'email' => 'client.demo@belhache.net',
    'password_hash' => password_hash('DemoGalette123!', PASSWORD_DEFAULT),
    'role' => 'utilisateur',
    'is_active' => 1,
]);

$ingredients = [];
$ingredients['Pate a galette'] = ensureIngredient($db, [
    'name' => 'Pate a galette',
    'purchase_quantity' => 5,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 14.50,
]);
$ingredients['Jambon blanc'] = ensureIngredient($db, [
    'name' => 'Jambon blanc',
    'purchase_quantity' => 20,
    'purchase_unit_id' => $unitIds['tranche'],
    'purchase_price' => 18.00,
]);
$ingredients['Emmental rape'] = ensureIngredient($db, [
    'name' => 'Emmental rape',
    'purchase_quantity' => 1,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 11.90,
]);
$ingredients['Creme fraiche'] = ensureIngredient($db, [
    'name' => 'Creme fraiche',
    'purchase_quantity' => 1,
    'purchase_unit_id' => $unitIds['L'],
    'purchase_price' => 5.40,
]);
$ingredients['Pommes'] = ensureIngredient($db, [
    'name' => 'Pommes',
    'purchase_quantity' => 1,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 3.60,
]);
$ingredients['Camembert'] = ensureIngredient($db, [
    'name' => 'Camembert',
    'purchase_quantity' => 10,
    'purchase_unit_id' => $unitIds['tranche'],
    'purchase_price' => 8.90,
]);
$ingredients['Lardons fumes'] = ensureIngredient($db, [
    'name' => 'Lardons fumes',
    'purchase_quantity' => 1,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 12.40,
]);
$ingredients['Pommes de terre'] = ensureIngredient($db, [
    'name' => 'Pommes de terre',
    'purchase_quantity' => 5,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 10.50,
]);
$ingredients['Oignons'] = ensureIngredient($db, [
    'name' => 'Oignons',
    'purchase_quantity' => 5,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 7.80,
]);
$ingredients['Saumon fume'] = ensureIngredient($db, [
    'name' => 'Saumon fume',
    'purchase_quantity' => 1,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 39.00,
]);
$ingredients['Chocolat noir'] = ensureIngredient($db, [
    'name' => 'Chocolat noir',
    'purchase_quantity' => 1,
    'purchase_unit_id' => $unitIds['kg'],
    'purchase_price' => 15.50,
]);

$recipes = [];
$recipes['La Sarthoise'] = ensureRecipe($db, [
    'name' => 'La Sarthoise',
    'category' => 'sale',
    'description' => 'Galette complete avec jambon, fromage et base de pate artisanale.',
    'selling_price' => 8.90,
    'is_active' => 1,
    'display_order' => 10,
], [
    ['ingredient_id' => $ingredients['Pate a galette'], 'quantity' => 120, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Jambon blanc'], 'quantity' => 1, 'unit_id' => $unitIds['tranche']],
    ['ingredient_id' => $ingredients['Emmental rape'], 'quantity' => 40, 'unit_id' => $unitIds['g']],
]);

$recipes['La Normande'] = ensureRecipe($db, [
    'name' => 'La Normande',
    'category' => 'sale',
    'description' => 'Pommes, camembert et creme pour une galette genereuse.',
    'selling_price' => 9.80,
    'is_active' => 1,
    'display_order' => 20,
], [
    ['ingredient_id' => $ingredients['Pate a galette'], 'quantity' => 120, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Pommes'], 'quantity' => 90, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Camembert'], 'quantity' => 2, 'unit_id' => $unitIds['tranche']],
    ['ingredient_id' => $ingredients['Creme fraiche'], 'quantity' => 30, 'unit_id' => $unitIds['ml']],
]);

$recipes['La Savoyarde'] = ensureRecipe($db, [
    'name' => 'La Savoyarde',
    'category' => 'sale',
    'description' => 'Version montagnarde avec pommes de terre, oignons et lardons.',
    'selling_price' => 10.50,
    'is_active' => 1,
    'display_order' => 30,
], [
    ['ingredient_id' => $ingredients['Pate a galette'], 'quantity' => 120, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Pommes de terre'], 'quantity' => 100, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Lardons fumes'], 'quantity' => 45, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Oignons'], 'quantity' => 20, 'unit_id' => $unitIds['g']],
]);

$recipes['La Marine'] = ensureRecipe($db, [
    'name' => 'La Marine',
    'category' => 'sale',
    'description' => 'Galette saumon fume et creme, utile pour tester les formules avec poisson.',
    'selling_price' => 11.90,
    'is_active' => 1,
    'display_order' => 40,
], [
    ['ingredient_id' => $ingredients['Pate a galette'], 'quantity' => 120, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Saumon fume'], 'quantity' => 45, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Creme fraiche'], 'quantity' => 25, 'unit_id' => $unitIds['ml']],
]);

$recipes['La Gourmande'] = ensureRecipe($db, [
    'name' => 'La Gourmande',
    'category' => 'sucre',
    'description' => 'Galette sucree au chocolat pour illustrer la categorie dessert.',
    'selling_price' => 7.20,
    'is_active' => 1,
    'display_order' => 50,
], [
    ['ingredient_id' => $ingredients['Pate a galette'], 'quantity' => 120, 'unit_id' => $unitIds['g']],
    ['ingredient_id' => $ingredients['Chocolat noir'], 'quantity' => 30, 'unit_id' => $unitIds['g']],
]);

$formulaDecouverteId = ensureFormula($db, [
    'name' => 'Decouverte terroir',
    'description' => 'Une formule vitrine avec trois recettes emblemiques pour demarrer.',
    'price_per_person' => 18.50,
    'minimum_guests' => 5,
    'is_price_visible' => 1,
    'is_active' => 1,
    'display_order' => 10,
], [
    ['recipe_id' => $recipes['La Sarthoise'], 'quantity' => 2],
    ['recipe_id' => $recipes['La Normande'], 'quantity' => 2],
    ['recipe_id' => $recipes['La Savoyarde'], 'quantity' => 2],
]);

$formulaSansPoissonId = ensureFormula($db, [
    'name' => 'Sans poisson',
    'description' => 'Selection de formules sans poisson, pratique pour les demandes classiques.',
    'price_per_person' => 16.90,
    'minimum_guests' => 8,
    'is_price_visible' => 0,
    'is_active' => 1,
    'display_order' => 20,
], [
    ['recipe_id' => $recipes['La Sarthoise'], 'quantity' => 3],
    ['recipe_id' => $recipes['La Normande'], 'quantity' => 3],
    ['recipe_id' => $recipes['La Savoyarde'], 'quantity' => 3],
    ['recipe_id' => $recipes['La Gourmande'], 'quantity' => 2],
]);

$formulaCompleteId = ensureFormula($db, [
    'name' => 'Complete evenement',
    'description' => 'Formule de demonstration avec l ensemble du catalogue actuel.',
    'price_per_person' => 22.00,
    'minimum_guests' => 15,
    'is_price_visible' => 1,
    'is_active' => 1,
    'display_order' => 30,
], [
    ['recipe_id' => $recipes['La Sarthoise'], 'quantity' => 3],
    ['recipe_id' => $recipes['La Normande'], 'quantity' => 3],
    ['recipe_id' => $recipes['La Savoyarde'], 'quantity' => 3],
    ['recipe_id' => $recipes['La Marine'], 'quantity' => 2],
    ['recipe_id' => $recipes['La Gourmande'], 'quantity' => 2],
]);

$db->query('INSERT INTO fixed_fees (name, default_amount, is_active)
            SELECT ?, ?, 1
            WHERE NOT EXISTS (SELECT 1 FROM fixed_fees WHERE name = ?)', ['Livraison', 25.00, 'Livraison']);
$db->query('INSERT INTO fixed_fees (name, default_amount, is_active)
            SELECT ?, ?, 1
            WHERE NOT EXISTS (SELECT 1 FROM fixed_fees WHERE name = ?)', ['Mise en place', 40.00, 'Mise en place']);

$existingRequest = $db->query(
    'SELECT id FROM quote_requests WHERE user_id = ? AND event_name = ? LIMIT 1',
    [$demoUserId, 'Anniversaire de demonstration']
)->fetch();

if (!$existingRequest) {
    $db->query(
        'INSERT INTO quote_requests (user_id, event_name, event_type, event_date, total_guests, address, phone, comment, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$demoUserId, 'Anniversaire de demonstration', 'Anniversaire', date('Y-m-d', strtotime('+45 days')), 30, '12 rue des Demoiselles, 72000 Le Mans', '0611223344', 'Exemple de demande generee automatiquement.', 'en_cours_etude']
    );
    $requestId = $db->lastInsertId();

    $db->query(
        'INSERT INTO quote_request_formulas (quote_request_id, formula_id, guest_count, price_per_person_snapshot) VALUES (?, ?, ?, ?)',
        [$requestId, $formulaDecouverteId, 12, 18.50]
    );
    $db->query(
        'INSERT INTO quote_request_formulas (quote_request_id, formula_id, guest_count, price_per_person_snapshot) VALUES (?, ?, ?, ?)',
        [$requestId, $formulaSansPoissonId, 18, 16.90]
    );

    $db->query(
        'INSERT INTO quote_messages (quote_request_id, sender_user_id, is_admin, body) VALUES (?, ?, 0, ?)',
        [$requestId, $demoUserId, 'Bonjour, je souhaite un retour sur les quantites et la disponibilite pour cette date.']
    );
    $db->query(
        'INSERT INTO quote_messages (quote_request_id, sender_user_id, is_admin, body) VALUES (?, ?, 1, ?)',
        [$requestId, $adminId, 'Bonjour, votre demande a bien ete recue. Nous revenons vers vous avec un devis detaille.']
    );
}

echo "Seed termine.\n";
echo "Admin : enligne@belhache.net / oM@Eb3#To9QcG^\n";
echo "Utilisateur demo : client.demo@belhache.net / DemoGalette123!\n";
