<section class="stack-xl">
    <section class="panel stack-lg">
        <div class="section-head">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Frais permanents</h1>
            </div>
            <p class="text-muted">Ces frais sont reutilisables dans tous les devis. Les frais occasionnels restent saisis directement dans chaque devis.</p>
        </div>

        <div class="admin-summary-grid">
            <?php foreach ($fixedFees as $fixedFee): ?>
                <article class="admin-summary-card">
                    <div class="admin-summary-card__head">
                        <div>
                            <p class="eyebrow">Frais permanent</p>
                            <h2><?= htmlspecialchars($fixedFee['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <span class="status-badge <?= (int)$fixedFee['is_active'] === 1 ? '' : 'status-badge-muted' ?>"><?= (int)$fixedFee['is_active'] === 1 ? 'Actif' : 'Inactif' ?></span>
                    </div>

                    <dl class="meta-list">
                        <div>
                            <dt>Montant par defaut</dt>
                            <dd><?= number_format((float)$fixedFee['default_amount'], 2, ',', ' ') ?> EUR</dd>
                        </div>
                        <div>
                            <dt>Utilisations</dt>
                            <dd><?= (int)$fixedFee['usage_count'] ?></dd>
                        </div>
                    </dl>

                    <form method="post" action="/admin/fixed-fees/<?= (int)$fixedFee['id'] ?>/update" class="stack-md">
                        <?= Csrf::input() ?>
                        <div class="grid-form two-cols">
                            <label>
                                <span>Nom</span>
                                <input type="text" name="name" value="<?= htmlspecialchars($fixedFee['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                            <label>
                                <span>Montant par defaut</span>
                                <input type="number" name="default_amount" step="0.01" min="0" value="<?= htmlspecialchars((string)$fixedFee['default_amount'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                            <label class="check check-panel">
                                <input type="checkbox" name="is_active" <?= (int)$fixedFee['is_active'] === 1 ? 'checked' : '' ?>>
                                <span>Frais actif</span>
                            </label>
                        </div>
                        <div class="actions-inline">
                            <button class="btn btn-light" type="submit">Mettre a jour</button>
                        </div>
                    </form>

                    <form method="post" action="/admin/fixed-fees/<?= (int)$fixedFee['id'] ?>/delete" onsubmit="return confirm('Supprimer ce frais permanent ?');">
                        <?= Csrf::input() ?>
                        <button class="btn btn-danger" type="submit">Supprimer</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel stack-lg">
        <div class="section-head">
            <div>
                <p class="eyebrow">Creation</p>
                <h2>Ajouter un frais permanent</h2>
            </div>
        </div>

        <form method="post" action="/admin/fixed-fees" class="grid-form two-cols">
            <?= Csrf::input() ?>
            <label>
                <span>Nom</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Montant par defaut</span>
                <input type="number" name="default_amount" step="0.01" min="0" required>
            </label>
            <label class="check">
                <input type="checkbox" name="is_active" checked>
                <span>Frais actif</span>
            </label>
            <div class="full">
                <button class="btn btn-primary" type="submit">Ajouter le frais permanent</button>
            </div>
        </form>
    </section>
</section>
