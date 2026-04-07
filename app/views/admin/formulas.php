<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <h1>Formules</h1>
            <p>Le prix par personne est saisi par l admin et peut etre affiche ou masque sur le catalogue utilisateur.</p>
        </div>

        <div class="recipes-admin-grid">
            <?php foreach ($formulas as $formula): ?>
                <article class="recipe-admin-card">
                    <?php if (!empty($formula['photo_path'])): ?>
                        <div class="media-preview media-preview-card">
                            <img src="<?= htmlspecialchars((string)$formula['photo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    <?php endif; ?>
                    <div class="recipe-admin-card__head">
                        <div>
                            <p class="eyebrow">Formule</p>
                            <h2><?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <span class="status-badge"><?= (int)$formula['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                    </div>

                    <dl class="meta-list">
                        <div>
                            <dt>Minimum</dt>
                            <dd><?= (int)$formula['minimum_guests'] ?> pers.</dd>
                        </div>
                        <div>
                            <dt>Prix / pers.</dt>
                            <dd><?= number_format((float)$formula['price_per_person'], 2, ',', ' ') ?> EUR</dd>
                        </div>
                        <div>
                            <dt>Prix visible</dt>
                            <dd><?= (int)$formula['is_price_visible'] === 1 ? 'Oui' : 'Non' ?></dd>
                        </div>
                        <div>
                            <dt>Composition</dt>
                            <dd><?= htmlspecialchars((string)($formula['recipe_summary'] ?: 'Aucune composition pour le moment.'), ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                    </dl>

                    <div class="recipe-admin-card__footer">
                        <p class="text-muted"><?= htmlspecialchars((string)($formula['description'] ?: 'Aucune description pour le moment.'), ENT_QUOTES, 'UTF-8') ?></p>
                        <a class="btn btn-light" href="/admin/formulas/<?= (int)$formula['id'] ?>/edit">Modifier</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Ajouter une formule</h2>
        </div>
        <form method="post" action="/admin/formulas" enctype="multipart/form-data" class="stack-lg">
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
                <label class="full">
                    <span>Photo</span>
                    <input type="file" name="formula_photo" accept="image/jpeg,image/png,image/webp">
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
