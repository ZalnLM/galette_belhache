<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <h1>Formules</h1>
            <p>Le prix par personne est saisi par l admin et peut etre affiche ou masque sur le catalogue utilisateur.</p>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Minimum</th>
                        <th>Prix / pers.</th>
                        <th>Prix visible</th>
                        <th>Composition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formulas as $formula): ?>
                        <tr>
                            <td><?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)$formula['minimum_guests'] ?></td>
                            <td><?= number_format((float)$formula['price_per_person'], 2, ',', ' ') ?> EUR</td>
                            <td><?= (int)$formula['is_price_visible'] === 1 ? 'Oui' : 'Non' ?></td>
                            <td><?= htmlspecialchars((string)$formula['recipe_summary'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Ajouter une formule</h2>
        </div>
        <form method="post" action="/admin/formulas" class="stack-lg">
            <?= Csrf::input() ?>
            <div class="grid-form two-cols">
                <label>
                    <span>Nom</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Prix par personne</span>
                    <input type="number" name="price_per_person" step="0.01" min="0" required>
                </label>
                <label>
                    <span>Minimum de convives</span>
                    <input type="number" name="minimum_guests" min="1" value="1" required>
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
                    <input type="checkbox" name="is_price_visible" checked>
                    <span>Afficher le prix cote utilisateur</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="is_active" checked>
                    <span>Formule active</span>
                </label>
            </div>

            <div class="stack-md">
                <h3>Galettes incluses</h3>
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="ingredient-row">
                        <select name="recipe_id[]">
                            <option value="">Recette</option>
                            <?php foreach ($recipes as $recipe): ?>
                                <option value="<?= (int)$recipe['id'] ?>"><?= htmlspecialchars($recipe['name'] . ' - ' . $recipe['category'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="recipe_quantity[]" min="0" placeholder="Quantite">
                    </div>
                <?php endfor; ?>
            </div>

            <button class="btn btn-primary" type="submit">Ajouter la formule</button>
        </form>
    </section>
</section>
