<section class="stack-xl">
    <section class="panel">
        <div class="section-head">
            <div>
                <p class="eyebrow">Demande #<?= (int)$request['id'] ?></p>
                <h1><?= htmlspecialchars($request['event_name'], ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <span class="status-badge"><?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="details-grid">
            <article class="subpanel">
                <h2>Client</h2>
                <p><strong>Nom :</strong> <?= htmlspecialchars(trim($request['first_name'] . ' ' . $request['last_name']), ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($request['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Telephone :</strong> <?= htmlspecialchars($request['phone'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Adresse :</strong> <?= htmlspecialchars($request['address'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>

            <article class="subpanel">
                <h2>Evenement</h2>
                <p><strong>Type :</strong> <?= htmlspecialchars((string)$request['event_type'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Date :</strong> <?= htmlspecialchars($request['event_date'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Convives :</strong> <?= (int)$request['total_guests'] ?></p>
                <p><strong>Commentaire :</strong> <?= nl2br(htmlspecialchars((string)$request['comment'], ENT_QUOTES, 'UTF-8')) ?></p>
            </article>
        </div>

        <article class="subpanel">
            <h2>Formules demandees</h2>
            <ul class="plain-list">
                <?php foreach ($formulas as $formula): ?>
                    <li><?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?> - <?= (int)$formula['guest_count'] ?> pers. - <?= number_format((float)$formula['price_per_person_snapshot'], 2, ',', ' ') ?> EUR / pers.</li>
                <?php endforeach; ?>
            </ul>
        </article>

        <form method="post" action="/admin/quote-requests/<?= (int)$request['id'] ?>/status" class="inline-form">
            <?= Csrf::input() ?>
            <select name="status">
                <?php foreach (['demande_recue', 'en_cours_etude', 'devis_envoye', 'devis_accepte', 'devis_refuse', 'annule'] as $status): ?>
                    <option value="<?= $status ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Mettre a jour le statut</button>
        </form>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Messagerie</h2>
            <p>Le PDF, l email de devis et les pieces jointes admin restent a brancher dans l iteration suivante.</p>
        </div>

        <div class="message-thread">
            <?php foreach ($messages as $message): ?>
                <div class="message <?= (int)$message['is_admin'] === 1 ? 'message-admin' : 'message-user' ?>">
                    <div class="message-meta">
                        <?= htmlspecialchars(trim($message['first_name'] . ' ' . $message['last_name']), ENT_QUOTES, 'UTF-8') ?>
                        <span><?= htmlspecialchars($message['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p><?= nl2br(htmlspecialchars($message['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" action="/admin/quote-requests/<?= (int)$request['id'] ?>/message" class="stack-md">
            <?= Csrf::input() ?>
            <label>
                <span>Reponse admin</span>
                <textarea name="body" rows="4" required></textarea>
            </label>
            <button class="btn btn-primary" type="submit">Envoyer le message</button>
        </form>
    </section>
</section>
