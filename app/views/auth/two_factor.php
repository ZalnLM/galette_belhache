<section class="auth-card">
    <div>
        <p class="eyebrow">Verification 2FA</p>
        <h1>Entre ton code</h1>
        <p>Renseigne le code a 6 chiffres genere par ton application d authentification.</p>
    </div>

    <form method="post" class="stack-md">
        <?= Csrf::input() ?>
        <label>
            <span>Code 2FA</span>
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required>
        </label>
        <button type="submit" class="btn btn-primary">Verifier</button>
    </form>

    <p class="auth-helper">
        <a href="/login">Annuler et revenir a la connexion</a>
    </p>
</section>
