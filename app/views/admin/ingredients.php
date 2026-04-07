<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <h1>Ingredients</h1>
            <p>Chaque ingredient porte un prix d achat de reference, par exemple au kilo, au litre ou a l unite, afin de calculer le cout interne des recettes.</p>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Reference achat</th>
                        <th>Prix achat</th>
                        <th>Utilise dans recettes</th>
                        <th>Actif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ingredients as $ingredient): ?>
                        <tr>
                            <td><?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= number_format((float)$ingredient['purchase_quantity'], 2, ',', ' ') ?>
                                <?= htmlspecialchars($ingredient['purchase_unit_symbol'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= number_format((float)$ingredient['purchase_price'], 2, ',', ' ') ?> EUR</td>
                            <td><?= (int)$ingredient['recipe_usage_count'] ?></td>
                            <td><?= (int)$ingredient['is_active'] === 1 ? 'Oui' : 'Non' ?></td>
                            <td>
                                <?php if ((int)$ingredient['recipe_usage_count'] === 0): ?>
                                    <form method="post" action="/admin/ingredients/<?= (int)$ingredient['id'] ?>/delete" onsubmit="return confirm('Supprimer cet ingredient ?');">
                                        <?= Csrf::input() ?>
                                        <button class="btn btn-light" type="submit">Supprimer</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Suppression bloquee</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
