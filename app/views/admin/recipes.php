<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <h1>Recettes de galettes</h1>
            <p>Chaque recette correspond a 1 galette. Le cout interne est derive des ingredients, tandis que le prix de vente reste fixe par l admin.</p>
        </div>

        <div class="recipes-admin-grid">
            <?php foreach ($recipes as $recipe): ?>
                <article class="recipe-admin-card">
                    <?php if (!empty($recipe['photo_path'])): ?>
                        <div class="media-preview media-preview-card">
                            <img src="<?= htmlspecialchars((string)$recipe['photo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($recipe['name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    <?php endif; ?>
                    <div class="recipe-admin-card__head">
                        <div>
                            <p class="eyebrow">Recette</p>
                            <h2><?= htmlspecialchars($recipe['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <span class="status-badge"><?= (int)$recipe['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                    </div>

                    <dl class="meta-list">
                        <div>
                            <dt>Categorie</dt>
                            <dd><?= $recipe['category'] === 'sale' ? 'Salé' : 'Sucré' ?></dd>
                        </div>
                        <div>
                            <dt>Prix de vente</dt>
                            <dd><?= number_format((float)$recipe['selling_price'], 2, ',', ' ') ?> EUR</dd>
                        </div>
                        <div>
                            <dt>Cout interne</dt>
                            <dd><?= number_format((float)$recipe['internal_cost'], 2, ',', ' ') ?> EUR</dd>
                        </div>
                        <div>
                            <dt>Marge brute</dt>
                            <dd><?= number_format((float)$recipe['selling_price'] - (float)$recipe['internal_cost'], 2, ',', ' ') ?> EUR</dd>
                        </div>
                    </dl>

                    <div class="recipe-admin-card__footer">
                        <p class="text-muted"><?= htmlspecialchars((string)($recipe['description'] ?: 'Aucune description pour le moment.'), ENT_QUOTES, 'UTF-8') ?></p>
                        <a class="btn btn-light" href="/admin/recipes/<?= (int)$recipe['id'] ?>/edit">Modifier</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Ajouter une recette</h2>
        </div>
        <form method="post" action="/admin/recipes" enctype="multipart/form-data" class="stack-lg">
            <?= Csrf::input() ?>
            <datalist id="ingredient-suggestions">
                <?php foreach ($ingredients as $ingredient): ?>
                    <option value="<?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?>">
                <?php endforeach; ?>
            </datalist>
            <div class="grid-form two-cols">
                <label>
                    <span>Nom</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Categorie</span>
                    <select name="category">
                        <option value="sale">Salé</option>
                        <option value="sucre">Sucré</option>
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
                <label class="full">
                    <span>Photo</span>
                    <input type="file" name="recipe_photo" accept="image/jpeg,image/png,image/webp">
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
                        <input type="text" name="ingredient_name[]" list="ingredient-suggestions" placeholder="Ingredient existant ou nouveau nom">
                        <input type="number" name="ingredient_quantity[]" step="0.01" min="0" placeholder="Quantite dans l unite de l ingredient">
                        <div class="ingredient-unit-hint">Si l ingredient n existe pas encore, il sera cree automatiquement puis a completer dans l admin ingredients.</div>
                    </div>
                <?php endfor; ?>
            </div>

            <button class="btn btn-primary" type="submit">Ajouter la recette</button>
        </form>
    </section>
</section>
