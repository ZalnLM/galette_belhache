<section class="auth-card">
    <div>
        <p class="eyebrow">Validation</p>
        <h1>Renvoyer l email de validation</h1>
        <p>Entre ton adresse email pour recevoir un nouveau lien de validation de compte.</p>
    </div>

    <form method="post" class="stack-md">
        <?= Csrf::input() ?>
        <label>
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <button type="submit" class="btn btn-primary">Renvoyer le lien</button>
    </form>

    <p class="auth-helper">
        <a href="/login">Retour a la connexion</a>
    </p>
</section>
