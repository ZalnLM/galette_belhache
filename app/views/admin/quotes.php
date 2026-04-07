<?php
$statusLabels = [
    'draft' => 'Brouillon',
    'sent' => 'Envoye',
    'accepted' => 'Accepte',
    'refused' => 'Refuse',
    'cancelled' => 'Annule',
];
?>

<section class="panel stack-lg">
    <div class="section-head">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Devis</h1>
        </div>
        <p class="text-muted">Suivi commercial de tous les devis prepares depuis les demandes clients.</p>
    </div>

    <div class="admin-summary-grid">
        <?php foreach ($quotes as $quote): ?>
            <article class="admin-summary-card">
                <div class="admin-summary-card__head">
                    <div>
                        <p class="eyebrow"><?= htmlspecialchars($quote['quote_number'], ENT_QUOTES, 'UTF-8') ?></p>
                        <h2><?= htmlspecialchars($quote['event_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <span class="status-badge"><?= htmlspecialchars($statusLabels[$quote['status']] ?? $quote['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <dl class="meta-list">
                    <div>
                        <dt>Client</dt>
                        <dd><?= htmlspecialchars(trim($quote['first_name'] . ' ' . $quote['last_name']), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Date evenement</dt>
                        <dd><?= htmlspecialchars((string)$quote['event_date'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Total vente</dt>
                        <dd><?= number_format((float)$quote['sale_total'], 2, ',', ' ') ?> EUR</dd>
                    </div>
                </dl>

                <div class="actions-inline">
                    <a class="btn btn-light" href="/admin/quotes/<?= (int)$quote['id'] ?>">Ouvrir le devis</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
