<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <div>
                <p class="eyebrow">Formule</p>
                <h1>Modifier <?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <a class="btn btn-light" href="/admin/formulas">Retour aux formules</a>
        </div>

        <form method="post" action="/admin/formulas/<?= (int)$formula['id'] ?>/update" class="stack-lg">
            <?= Csrf::input() ?>
            <div class="grid-form two-cols">
                <label>
                    <span>Nom</span>
                    <input type="text" name="name" value="<?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>
                    <span>Prix par personne</span>
                    <input type="number" name="price_per_person" step="0.01" min="0" value="<?= htmlspecialchars((string)$formula['price_per_person'], ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>
                    <span>Minimum de convives</span>
                    <input type="number" name="minimum_guests" min="1" value="<?= (int)$formula['minimum_guests'] ?>" required>
                </label>
                <label>
                    <span>Ordre d affichage</span>
                    <input type="number" name="display_order" min="0" value="<?= (int)$formula['display_order'] ?>">
                </label>
                <label class="full">
                    <span>Description</span>
                    <textarea name="description" rows="4"><?= htmlspecialchars((string)$formula['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <label class="check">
                    <input type="checkbox" name="is_price_visible" <?= (int)$formula['is_price_visible'] === 1 ? 'checked' : '' ?>>
                    <span>Afficher le prix cote utilisateur</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="is_active" <?= (int)$formula['is_active'] === 1 ? 'checked' : '' ?>>
                    <span>Formule active</span>
                </label>
            </div>

            <div class="stack-md">
                <h2>Galettes incluses</h2>
                <?php
                $rows = $formulaItems;
                while (count($rows) < 8) {
                    $rows[] = ['recipe_id' => '', 'quantity' => ''];
                }
                ?>
                <?php foreach ($rows as $row): ?>
                    <div class="ingredient-row">
                        <select name="recipe_id[]">
                            <option value="">Recette</option>
                            <?php foreach ($recipes as $recipe): ?>
                                <option value="<?= (int)$recipe['id'] ?>" <?= (int)$recipe['id'] === (int)$row['recipe_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($recipe['name'] . ' - ' . $recipe['category'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="recipe_quantity[]" min="0" value="<?= htmlspecialchars((string)$row['quantity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Quantite">
                        <div class="ingredient-unit-hint">Nombre de galettes prevu dans la formule</div>
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
        <?php if ($quoteUsageCount > 0): ?>
            <p class="text-muted">Cette formule est deja presente dans <?= $quoteUsageCount ?> demande(s) de devis. Sa suppression est bloquee.</p>
        <?php else: ?>
            <form method="post" action="/admin/formulas/<?= (int)$formula['id'] ?>/delete" onsubmit="return confirm('Supprimer cette formule ?');">
                <?= Csrf::input() ?>
                <button class="btn btn-light" type="submit">Supprimer la formule</button>
            </form>
        <?php endif; ?>
    </section>
</section>
