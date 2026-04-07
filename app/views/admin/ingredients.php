<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <h1>Ingredients</h1>
            <p>Chaque ingredient porte un prix d achat de reference, par exemple au kilo, au litre ou a l unite, afin de calculer le cout interne des recettes.</p>
        </div>
        <div class="admin-inline-note">
            Les ingredients a completer apparaissent en premier. Ils correspondent en general aux ingredients crees automatiquement depuis une recette.
        </div>
        <div class="ingredients-admin-grid">
            <?php foreach ($ingredients as $ingredient): ?>
                <article class="ingredient-admin-card <?= (int)$ingredient['needs_completion'] === 1 ? 'ingredient-admin-card--warning' : '' ?>">
                    <form method="post" action="/admin/ingredients/<?= (int)$ingredient['id'] ?>/update" class="stack-lg">
                        <?= Csrf::input() ?>
                        <div class="ingredient-admin-card__head">
                            <div>
                                <p class="eyebrow">Ingredient</p>
                                <h2><?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <div class="ingredient-admin-card__badges">
                                <?php if ((int)$ingredient['needs_completion'] === 1): ?>
                                    <span class="status-badge status-badge-warning">A completer</span>
                                <?php endif; ?>
                                <span class="status-badge"><?= (int)$ingredient['recipe_usage_count'] ?> recette(s)</span>
                            </div>
                        </div>

                        <div class="grid-form two-cols">
                            <label>
                                <span>Nom</span>
                                <input type="text" name="name" value="<?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                            <label>
                                <span>Prix d achat</span>
                                <input type="number" name="purchase_price" step="0.01" min="0" value="<?= htmlspecialchars((string)$ingredient['purchase_price'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                            <label>
                                <span>Quantite de reference</span>
                                <input type="number" name="purchase_quantity" step="0.01" min="0.01" value="<?= htmlspecialchars((string)$ingredient['purchase_quantity'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                            <label>
                                <span>Unite de reference</span>
                                <select name="purchase_unit_id" required>
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?= (int)$unit['id'] ?>" <?= (int)$unit['id'] === (int)$ingredient['purchase_unit_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>

                        <div class="ingredient-admin-card__footer">
                            <label class="check">
                                <input type="checkbox" name="is_active" <?= (int)$ingredient['is_active'] === 1 ? 'checked' : '' ?>>
                                <span>Ingredient actif</span>
                            </label>
                            <div class="actions-inline">
                                <button class="btn btn-light" type="submit">Mettre a jour</button>
                    </form>
                                <form method="post" action="/admin/ingredients/<?= (int)$ingredient['id'] ?>/delete" onsubmit="return confirm('Supprimer cet ingredient ? Il sera aussi retire des recettes qui l utilisent.');">
                                    <?= Csrf::input() ?>
                                    <button class="btn btn-danger" type="submit">Supprimer</button>
                                </form>
                            </div>
                        </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Ajouter un ingredient</h2>
        </div>

        <form method="post" action="/admin/ingredients" class="grid-form two-cols">
            <?= Csrf::input() ?>
            <label>
                <span>Nom</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Quantite de reference</span>
                <input type="number" name="purchase_quantity" step="0.01" min="0.01" placeholder="1" required>
            </label>
            <label>
                <span>Unite de reference</span>
                <select name="purchase_unit_id" required>
                    <option value="">Choisir une unite</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= (int)$unit['id'] ?>">
                            <?= htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')', ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Prix d achat</span>
                <input type="number" name="purchase_price" step="0.01" min="0" placeholder="0.00" required>
            </label>
            <label class="check">
                <input type="checkbox" name="is_active" checked>
                <span>Ingredient actif</span>
            </label>
            <div class="full">
                <button class="btn btn-primary" type="submit">Ajouter l ingredient</button>
            </div>
        </form>
    </section>
</section>
