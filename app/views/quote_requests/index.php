<?php
$statusLabels = [
    'demande_recue' => 'Demande recue',
    'en_cours_etude' => 'En cours d etude',
    'devis_envoye' => 'Devis envoye',
    'devis_accepte' => 'Devis accepte',
    'devis_refuse' => 'Devis refuse',
    'annule' => 'Annule',
];
?>

<section class="panel stack-lg">
    <div class="section-head">
        <div>
            <p class="eyebrow">Suivi</p>
            <h1>Mes demandes</h1>
        </div>
        <p class="text-muted">Retrouve ici l historique de tes demandes de devis et les echanges associes.</p>
    </div>

    <div class="admin-summary-grid">
        <?php foreach ($requests as $request): ?>
            <article class="admin-summary-card">
                <div class="admin-summary-card__head">
                    <div>
                        <p class="eyebrow">Dossier #<?= (int)$request['id'] ?></p>
                        <h2><?= htmlspecialchars($request['event_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <span class="status-badge"><?= htmlspecialchars($statusLabels[$request['status']] ?? $request['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <dl class="meta-list">
                    <div>
                        <dt>Date</dt>
                        <dd><?= htmlspecialchars($request['event_date'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Convives</dt>
                        <dd><?= (int)$request['total_guests'] ?></dd>
                    </div>
                    <div>
                        <dt>Statut</dt>
                        <dd><?= htmlspecialchars($statusLabels[$request['status']] ?? $request['status'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>

                <div class="actions-inline">
                    <a class="btn btn-light" href="/demande-devis/<?= (int)$request['id'] ?>">Ouvrir</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
