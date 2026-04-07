<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <div>
                <p class="eyebrow">Recette</p>
                <h1>Modifier <?= htmlspecialchars($recipe['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <a class="btn btn-light" href="/admin/recipes">Retour aux recettes</a>
        </div>

        <form method="post" action="/admin/recipes/<?= (int)$recipe['id'] ?>/update" class="stack-lg">
            <?= Csrf::input() ?>
            <div class="grid-form two-cols">
                <label>
                    <span>Nom</span>
                    <input type="text" name="name" value="<?= htmlspecialchars($recipe['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>
                    <span>Categorie</span>
                    <select name="category">
                        <option value="sale" <?= $recipe['category'] === 'sale' ? 'selected' : '' ?>>Sale</option>
                        <option value="sucre" <?= $recipe['category'] === 'sucre' ? 'selected' : '' ?>>Sucre</option>
                    </select>
                </label>
                <label>
                    <span>Prix de vente</span>
                    <input type="number" name="selling_price" step="0.01" min="0" value="<?= htmlspecialchars((string)$recipe['selling_price'], ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    <span>Ordre d affichage</span>
                    <input type="number" name="display_order" min="0" value="<?= (int)$recipe['display_order'] ?>">
                </label>
                <label class="full">
                    <span>Description</span>
                    <textarea name="description" rows="4"><?= htmlspecialchars((string)$recipe['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <label class="check">
                    <input type="checkbox" name="is_active" <?= (int)$recipe['is_active'] === 1 ? 'checked' : '' ?>>
                    <span>Recette active</span>
                </label>
            </div>

            <div class="stack-md">
                <h2>Ingredients de la recette</h2>
                <?php
                $rows = $recipeIngredients;
                while (count($rows) < 6) {
                    $rows[] = ['ingredient_id' => '', 'quantity' => '', 'unit_id' => ''];
                }
                ?>
                <?php foreach ($rows as $row): ?>
                    <div class="ingredient-row">
                        <select name="ingredient_id[]">
                            <option value="">Ingredient</option>
                            <?php foreach ($ingredients as $ingredient): ?>
                                <option value="<?= (int)$ingredient['id'] ?>" <?= (int)$ingredient['id'] === (int)$row['ingredient_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ingredient['name'] . ' (' . $ingredient['purchase_unit'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="ingredient_quantity[]" step="0.01" min="0" value="<?= htmlspecialchars((string)$row['quantity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Quantite dans l unite de l ingredient">
                        <div class="ingredient-unit-hint">Unite definie dans la fiche ingredient</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Enregistrer les modifications</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Suppression</h2>
        </div>
        <?php if ($formulaUsageCount > 0): ?>
            <p class="text-muted">Cette recette est utilisee dans <?= $formulaUsageCount ?> formule(s). Sa suppression est bloquee tant qu elle est referencee.</p>
        <?php else: ?>
            <form method="post" action="/admin/recipes/<?= (int)$recipe['id'] ?>/delete" onsubmit="return confirm('Supprimer cette recette ?');">
                <?= Csrf::input() ?>
                <button class="btn btn-light" type="submit">Supprimer la recette</button>
            </form>
        <?php endif; ?>
    </section>
</section>
