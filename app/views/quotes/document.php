<?php
$quoteStatusLabels = [
    'draft' => 'Brouillon',
    'sent' => 'Envoye',
    'accepted' => 'Accepte',
    'refused' => 'Refuse',
    'cancelled' => 'Annule',
];

$formulaTotal = 0.0;
$feesTotal = 0.0;
foreach ($quoteLines as $line) {
    if (($line['line_type'] ?? '') === 'formula') {
        $formulaTotal += (float)$line['total_price'];
    } else {
        $feesTotal += (float)$line['total_price'];
    }
}

$grandTotal = (float)$quote['sale_total'];
$depositAmount = (float)$quote['deposit_amount'];
$remainingBalance = max(0, $grandTotal - $depositAmount);
?>

<section class="stack-xl">
    <section class="panel quote-document">
        <div class="section-head">
            <div>
                <p class="eyebrow"><?= htmlspecialchars($quote['quote_number'], ENT_QUOTES, 'UTF-8') ?></p>
                <h1>Devis final</h1>
            </div>
            <div class="actions-inline">
                <?php if (!empty($backLink)): ?>
                    <a class="btn btn-light" href="<?= htmlspecialchars((string)$backLink, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$backLabel, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
                <button class="btn btn-primary" type="button" onclick="window.print()">Imprimer</button>
            </div>
        </div>

        <div class="details-grid">
            <article class="subpanel">
                <h2>Client</h2>
                <p><strong>Nom :</strong> <?= htmlspecialchars(trim($quote['first_name'] . ' ' . $quote['last_name']), ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($quote['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Telephone :</strong> <?= htmlspecialchars($quote['phone'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Adresse :</strong> <?= htmlspecialchars($quote['address'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>

            <article class="subpanel">
                <h2>Evenement</h2>
                <p><strong>Nom :</strong> <?= htmlspecialchars($quote['event_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Type :</strong> <?= htmlspecialchars((string)$quote['event_type'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Date :</strong> <?= htmlspecialchars((string)$quote['event_date'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Convives :</strong> <?= (int)$quote['total_guests'] ?></p>
                <p><strong>Validite :</strong> <?= htmlspecialchars((string)$quote['valid_until'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Statut :</strong> <?= htmlspecialchars($quoteStatusLabels[$quote['status']] ?? (string)$quote['status'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        </div>

        <article class="subpanel">
            <h2>Detail du devis</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Libelle</th>
                            <th>Quantite</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quoteLines as $line): ?>
                            <tr>
                                <td><?= htmlspecialchars($line['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float)$line['quantity'], 2, ',', ' ') ?></td>
                                <td><?= number_format((float)$line['unit_price'], 2, ',', ' ') ?> EUR</td>
                                <td><?= number_format((float)$line['total_price'], 2, ',', ' ') ?> EUR</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <div class="quote-totals-grid">
            <article class="subpanel">
                <h2>Recapitulatif</h2>
                <p><strong>Sous-total formules :</strong> <?= number_format($formulaTotal, 2, ',', ' ') ?> EUR</p>
                <p><strong>Total frais :</strong> <?= number_format($feesTotal, 2, ',', ' ') ?> EUR</p>
                <p class="quote-total-line"><strong>Total devis :</strong> <?= number_format($grandTotal, 2, ',', ' ') ?> EUR</p>
                <p><strong>Acompte :</strong> <?= number_format($depositAmount, 2, ',', ' ') ?> EUR</p>
                <p><strong>Solde restant :</strong> <?= number_format($remainingBalance, 2, ',', ' ') ?> EUR</p>
                <?php if (!empty($showInternalCost)): ?>
                    <p><strong>Cout interne :</strong> <?= number_format((float)$quote['internal_cost_total'], 2, ',', ' ') ?> EUR</p>
                <?php endif; ?>
            </article>

            <article class="subpanel">
                <h2>Conditions</h2>
                <p><strong>Mentions :</strong><br><?= nl2br(htmlspecialchars((string)$quote['legal_notes'], ENT_QUOTES, 'UTF-8')) ?></p>
                <p><strong>Conditions :</strong><br><?= nl2br(htmlspecialchars((string)$quote['terms_conditions'], ENT_QUOTES, 'UTF-8')) ?></p>
            </article>
        </div>
    </section>
</section>
