<section class="auth-card">
    <div>
        <p class="eyebrow">Assistance</p>
        <h1>Mot de passe oublie</h1>
        <p>Entre ton email pour recevoir un lien de reinitialisation valable pendant 1 heure.</p>
    </div>

    <form method="post" class="stack-md">
        <?= Csrf::input() ?>
        <label>
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <button type="submit" class="btn btn-primary">Envoyer le lien</button>
    </form>

    <p class="auth-helper">
        <a href="/login">Retour a la connexion</a>
    </p>
</section>
