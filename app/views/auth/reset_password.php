<section class="auth-card">
    <div>
        <p class="eyebrow">Securite</p>
        <h1>Nouveau mot de passe</h1>
        <p>Choisis un mot de passe d au moins 10 caracteres pour ton compte.</p>
    </div>

    <form method="post" class="stack-md">
        <?= Csrf::input() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <label>
            <span>Nouveau mot de passe</span>
            <input type="password" name="password" required>
        </label>
        <label>
            <span>Confirmation</span>
            <input type="password" name="password_confirm" required>
        </label>
        <button type="submit" class="btn btn-primary">Mettre a jour le mot de passe</button>
    </form>
</section>
