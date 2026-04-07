<section class="auth-card">
    <div>
        <p class="eyebrow">Creation de compte</p>
        <h1>Inscription</h1>
        <p>Le compte est active automatiquement. Les informations complementaires seront demandees au moment de la demande de devis.</p>
    </div>

    <form method="post" class="grid-form two-cols">
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
            <span>Confirmation</span>
            <input type="password" name="password_confirm" required>
        </label>
        <div class="full">
            <button type="submit" class="btn btn-primary">Creer mon compte</button>
        </div>
    </form>
</section>
