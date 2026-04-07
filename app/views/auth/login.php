<section class="auth-card">
    <div>
        <p class="eyebrow">Espace prive</p>
        <h1>Connexion</h1>
        <p>Le site est reserve aux utilisateurs connectes. Connecte-toi pour consulter les formules et suivre tes demandes de devis.</p>
    </div>

    <form method="post" class="stack-md">
        <?= Csrf::input() ?>
        <label>
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Mot de passe</span>
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
</section>
