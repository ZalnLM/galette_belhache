<section class="hero hero-media <?= $heroImage !== '' ? 'hero-media--with-image' : '' ?>">
    <?php if ($heroImage !== ''): ?>
        <div class="hero-media__visual">
            <img src="<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8') ?>" alt="Illustration d accueil">
        </div>
    <?php endif; ?>
    <div>
        <p class="eyebrow">Catalogue prive</p>
        <h1><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= nl2br(htmlspecialchars($heroText, ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
</section>

<form method="post" action="/demande-devis" class="stack-xl">
    <?= Csrf::input() ?>

    <section class="panel">
        <div class="section-head">
            <h2>Formules disponibles</h2>
            <p>Saisis un nombre de convives uniquement sur les formules a inclure dans la demande.</p>
        </div>
        <div class="cards-grid">
            <?php foreach ($formulas as $formula): ?>
                <article class="formula-card">
                    <div class="formula-card__body">
                        <div class="formula-card__top">
                            <div>
                                <h3><?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= nl2br(htmlspecialchars((string)$formula['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                            <label class="check">
                                <input type="checkbox" name="selected_formulas[]" value="<?= (int)$formula['id'] ?>">
                                <span>Selectionner</span>
                            </label>
                        </div>
                        <dl class="meta-list">
                            <div>
                                <dt>Minimum</dt>
                                <dd><?= (int)$formula['minimum_guests'] ?> pers.</dd>
                            </div>
                            <div>
                                <dt>Composition</dt>
                                <dd><?= htmlspecialchars((string)($formula['recipe_summary'] ?: 'Composition a definir'), ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                            <?php if ((int)$formula['is_price_visible'] === 1): ?>
                                <div>
                                    <dt>Prix</dt>
                                    <dd><?= number_format((float)$formula['price_per_person'], 2, ',', ' ') ?> EUR / pers.</dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                        <label>
                            <span>Nombre de convives pour cette formule</span>
                            <input type="number" name="formula_guest_count[<?= (int)$formula['id'] ?>]" min="<?= (int)$formula['minimum_guests'] ?>" step="1">
                        </label>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Informations de l evenement</h2>
            <p>Ces informations seront rattachees a ta demande puis visibles par l equipe admin.</p>
        </div>
        <div class="grid-form two-cols">
            <label>
                <span>Nom de l evenement</span>
                <input type="text" name="event_name" required>
            </label>
            <label>
                <span>Type d evenement</span>
                <input type="text" name="event_type" placeholder="Mariage, anniversaire, brunch...">
            </label>
            <label>
                <span>Date de l evenement</span>
                <input type="date" name="event_date" required>
            </label>
            <label>
                <span>Telephone</span>
                <input type="text" name="phone" required>
            </label>
            <label class="full">
                <span>Adresse</span>
                <input type="text" name="address" required>
            </label>
            <label class="full">
                <span>Commentaire general</span>
                <textarea name="comment" rows="4" placeholder="Details utiles pour la demande"></textarea>
            </label>
        </div>
        <div class="actions">
            <button type="submit" class="btn btn-primary">Envoyer ma demande de devis</button>
        </div>
    </section>
</form>
