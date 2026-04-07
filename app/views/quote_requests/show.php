<section class="panel stack-lg">
    <div class="section-head">
        <div>
            <p class="eyebrow">Dossier #<?= (int)$request['id'] ?></p>
            <h1><?= htmlspecialchars($request['event_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <span class="status-badge"><?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="details-grid">
        <article class="subpanel">
            <h2>Evenement</h2>
            <p><strong>Date :</strong> <?= htmlspecialchars($request['event_date'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Type :</strong> <?= htmlspecialchars((string)$request['event_type'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Convives :</strong> <?= (int)$request['total_guests'] ?></p>
            <p><strong>Adresse :</strong> <?= htmlspecialchars($request['address'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Telephone :</strong> <?= htmlspecialchars($request['phone'], ENT_QUOTES, 'UTF-8') ?></p>
        </article>

        <article class="subpanel">
            <h2>Formules</h2>
            <ul class="plain-list">
                <?php foreach ($formulas as $formula): ?>
                    <li><?= htmlspecialchars($formula['name'], ENT_QUOTES, 'UTF-8') ?> - <?= (int)$formula['guest_count'] ?> pers.</li>
                <?php endforeach; ?>
            </ul>
        </article>
    </div>

    <article class="subpanel">
        <h2>Messagerie</h2>
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

        <form method="post" action="/demande-devis/<?= (int)$request['id'] ?>/message" class="stack-md">
            <?= Csrf::input() ?>
            <label>
                <span>Nouveau message</span>
                <textarea name="body" rows="4" required></textarea>
            </label>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </form>
    </article>
</section>
