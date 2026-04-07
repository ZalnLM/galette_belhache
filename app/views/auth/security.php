<section class="stack-xl">
    <section class="panel stack-lg">
        <div class="section-head">
            <div>
                <p class="eyebrow">Compte</p>
                <h1>Securite</h1>
            </div>
        </div>

        <div class="details-grid">
            <article class="subpanel">
                <h2>Email</h2>
                <p><strong>Adresse :</strong> <?= htmlspecialchars((string)$account['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Validation :</strong> <?= !empty($account['email_verified_at']) ? 'Validee' : 'En attente' ?></p>
            </article>

            <article class="subpanel">
                <h2>Verification en deux etapes</h2>
                <p><strong>Etat :</strong> <?= (int)($account['two_factor_enabled'] ?? 0) === 1 ? 'Activee' : 'Desactivee' ?></p>
                <p class="text-muted">La verification 2FA fonctionne avec une application d authentification compatible TOTP.</p>
            </article>
        </div>
    </section>

    <?php if ((int)($account['two_factor_enabled'] ?? 0) !== 1): ?>
        <section class="panel stack-lg">
            <div class="section-head">
                <div>
                    <p class="eyebrow">2FA</p>
                    <h2>Activer la verification en deux etapes</h2>
                </div>
            </div>

            <?php if ($pendingSecret === ''): ?>
                <form method="post" action="/security/two-factor/prepare">
                    <?= Csrf::input() ?>
                    <button class="btn btn-primary" type="submit">Preparer la configuration 2FA</button>
                </form>
            <?php else: ?>
                <div class="subpanel">
                    <p><strong>Cle secrete :</strong> <code><?= htmlspecialchars($pendingSecret, ENT_QUOTES, 'UTF-8') ?></code></p>
                    <p class="text-muted">Ajoute cette cle dans ton application d authentification, puis saisis un code pour valider l activation.</p>
                    <p class="text-muted"><strong>Lien OTPAuth :</strong><br><code><?= htmlspecialchars($otpAuthUri, ENT_QUOTES, 'UTF-8') ?></code></p>
                </div>

                <form method="post" action="/security/two-factor/enable" class="stack-md">
                    <?= Csrf::input() ?>
                    <label>
                        <span>Code 2FA</span>
                        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Activer le 2FA</button>
                </form>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="panel stack-lg">
            <div class="section-head">
                <div>
                    <p class="eyebrow">2FA</p>
                    <h2>Desactiver la verification en deux etapes</h2>
                </div>
            </div>

            <form method="post" action="/security/two-factor/disable" class="stack-md">
                <?= Csrf::input() ?>
                <label>
                    <span>Code 2FA actuel</span>
                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required>
                </label>
                <button class="btn btn-danger" type="submit">Desactiver le 2FA</button>
            </form>
        </section>
    <?php endif; ?>
</section>
