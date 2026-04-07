<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <h1>Recettes de galettes</h1>
            <p>Chaque recette correspond a 1 galette. Le cout interne est derive des ingredients, tandis que le prix de vente reste fixe par l admin.</p>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Categorie</th>
                        <th>Prix vente</th>
                        <th>Cout interne</th>
                        <th>Actif</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipes as $recipe): ?>
                        <tr>
                            <td><?= htmlspecialchars($recipe['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($recipe['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$recipe['selling_price'], 2, ',', ' ') ?> EUR</td>
                            <td><?= number_format((float)$recipe['internal_cost'], 2, ',', ' ') ?> EUR</td>
                            <td><?= (int)$recipe['is_active'] === 1 ? 'Oui' : 'Non' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Ajouter une recette</h2>
        </div>
        <form method="post" action="/admin/recipes" class="stack-lg">
            <?= Csrf::input() ?>
            <div class="grid-form two-cols">
                <label>
                    <span>Nom</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Categorie</span>
                    <select name="category">
                        <option value="sale">Sale</option>
                        <option value="sucre">Sucre</option>
                    </select>
                </label>
                <label>
                    <span>Prix de vente</span>
                    <input type="number" name="selling_price" step="0.01" min="0">
                </label>
                <label>
                    <span>Ordre d affichage</span>
                    <input type="number" name="display_order" min="0" value="0">
                </label>
                <label class="full">
                    <span>Description</span>
                    <textarea name="description" rows="3"></textarea>
                </label>
                <label class="check">
                    <input type="checkbox" name="is_active" checked>
                    <span>Recette active</span>
                </label>
            </div>

            <div class="stack-md">
                <h3>Ingredients</h3>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="ingredient-row">
                        <select name="ingredient_id[]">
                            <option value="">Ingredient</option>
                            <?php foreach ($ingredients as $ingredient): ?>
                                <option value="<?= (int)$ingredient['id'] ?>"><?= htmlspecialchars($ingredient['name'] . ' (' . $ingredient['purchase_unit'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="ingredient_quantity[]" step="0.01" min="0" placeholder="Quantite">
                        <select name="ingredient_unit_id[]">
                            <option value="">Unite</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?= (int)$unit['id'] ?>"><?= htmlspecialchars($unit['symbol'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endfor; ?>
            </div>

            <button class="btn btn-primary" type="submit">Ajouter la recette</button>
        </form>
    </section>
</section>
