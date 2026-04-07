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

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actif</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$user['is_active'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td>
                            <form method="post" action="/admin/users/<?= (int)$user['id'] ?>/update" class="inline-form">
                                <?= Csrf::input() ?>
                                <select name="role">
                                    <option value="utilisateur" <?= $user['role'] === 'utilisateur' ? 'selected' : '' ?>>Utilisateur</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <label class="check">
                                    <input type="checkbox" name="is_active" <?= (int)$user['is_active'] === 1 ? 'checked' : '' ?>>
                                    <span>Actif</span>
                                </label>
                                <button class="btn btn-light" type="submit">Mettre a jour</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
