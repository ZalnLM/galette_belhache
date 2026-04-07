<?php
$roleLabels = [
    'admin' => 'Admin',
    'utilisateur' => 'Utilisateur',
];
?>

<section class="stack-xl">
<section class="panel stack-lg">
    <div class="section-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Utilisateurs</h1>
        </div>
        <form method="get" class="inline-form">
            <input type="text" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher un utilisateur">
            <button class="btn btn-light" type="submit">Chercher</button>
        </form>
    </div>

    <div class="admin-summary-grid">
        <?php foreach ($users as $user): ?>
            <article class="admin-summary-card">
                <div class="admin-summary-card__head">
                    <div>
                        <p class="eyebrow">Compte</p>
                        <h2><?= htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']), ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <span class="status-badge <?= (int)$user['is_active'] === 1 ? '' : 'status-badge-muted' ?>"><?= (int)$user['is_active'] === 1 ? 'Actif' : 'Inactif' ?></span>
                </div>

                <dl class="meta-list">
                    <div>
                        <dt>Email</dt>
                        <dd><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Role</dt>
                        <dd><?= htmlspecialchars($roleLabels[$user['role']] ?? $user['role'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Creation</dt>
                        <dd><?= htmlspecialchars((string)$user['created_at'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>

                <form method="post" action="/admin/users/<?= (int)$user['id'] ?>/update" class="stack-md">
                    <?= Csrf::input() ?>
                    <div class="grid-form two-cols">
                        <label>
                            <span>Role</span>
                            <select name="role">
                                <option value="utilisateur" <?= $user['role'] === 'utilisateur' ? 'selected' : '' ?>>Utilisateur</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </label>
                        <label class="check check-panel">
                            <input type="checkbox" name="is_active" <?= (int)$user['is_active'] === 1 ? 'checked' : '' ?>>
                            <span>Compte actif</span>
                        </label>
                    </div>
                    <button class="btn btn-light" type="submit">Mettre a jour</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel stack-lg">
    <div class="section-head">
        <div>
            <p class="eyebrow">Creation</p>
            <h2>Ajouter un compte</h2>
        </div>
    </div>

    <form method="post" action="/admin/users" class="grid-form two-cols">
        <?= Csrf::input() ?>
        <label>
            <span>Prenom</span>
            <input type="text" name="first_name" required>
        </label>
        <label>
            <span>Nom</span>
            <input type="text" name="last_name" required>
        </label>
        <label class="full">
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Mot de passe</span>
            <input type="password" name="password" required>
        </label>
        <label>
            <span>Role</span>
            <select name="role">
                <option value="utilisateur">Utilisateur</option>
                <option value="admin">Admin</option>
            </select>
        </label>
        <label class="check">
            <input type="checkbox" name="is_active" checked>
            <span>Compte actif</span>
        </label>
        <div class="full">
            <button class="btn btn-primary" type="submit">Creer le compte</button>
        </div>
    </form>
</section>
</section>
