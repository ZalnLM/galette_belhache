<?php
$quoteStatusLabels = [
    'draft' => 'Brouillon',
    'sent' => 'Envoye',
    'accepted' => 'Accepte',
    'refused' => 'Refuse',
    'cancelled' => 'Annule',
];

$fixedFeeRows = $fixedFeeLines;
while (count($fixedFeeRows) < 3) {
    $fixedFeeRows[] = ['fixed_fee_id' => '', 'quantity' => '1', 'unit_price' => '0'];
}

$freeFeeRows = $freeFeeLines;
while (count($freeFeeRows) < 3) {
    $freeFeeRows[] = ['label' => '', 'quantity' => '1', 'unit_price' => '0'];
}
?>

<section class="stack-xl">
    <section class="panel stack-lg">
        <div class="section-head">
            <div>
                <p class="eyebrow"><?= htmlspecialchars($quote['quote_number'], ENT_QUOTES, 'UTF-8') ?></p>
                <h1>Devis pour <?= htmlspecialchars($quote['event_name'], ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <a class="btn btn-light" href="/admin/quote-requests/<?= (int)$quote['request_id'] ?>">Retour a la demande</a>
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
                <h2>Totaux</h2>
                <p><strong>Vente :</strong> <?= number_format((float)$quote['sale_total'], 2, ',', ' ') ?> EUR</p>
                <p><strong>Frais :</strong> <?= number_format((float)$quote['fixed_fees_total'], 2, ',', ' ') ?> EUR</p>
                <p><strong>Cout interne :</strong> <?= number_format((float)$quote['internal_cost_total'], 2, ',', ' ') ?> EUR</p>
                <p><strong>Acompte :</strong> <?= number_format((float)$quote['deposit_amount'], 2, ',', ' ') ?> EUR</p>
            </article>
        </div>
    </section>

    <section class="panel">
        <form method="post" action="/admin/quotes/<?= (int)$quote['id'] ?>/update" class="stack-lg">
            <?= Csrf::input() ?>

            <div class="grid-form two-cols">
                <label>
                    <span>Validite du devis</span>
                    <input type="date" name="valid_until" value="<?= htmlspecialchars((string)$quote['valid_until'], ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    <span>Date de l evenement</span>
                    <input type="date" name="event_date" value="<?= htmlspecialchars((string)$quote['event_date'], ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    <span>Statut</span>
                    <select name="status">
                        <?php foreach ($quoteStatusLabels as $status => $label): ?>
                            <option value="<?= $status ?>" <?= $quote['status'] === $status ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Acompte</span>
                    <input type="number" name="deposit_amount" step="0.01" min="0" value="<?= htmlspecialchars((string)$quote['deposit_amount'], ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label class="full">
                    <span>Mentions legales</span>
                    <textarea name="legal_notes" rows="3"><?= htmlspecialchars((string)$quote['legal_notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <label class="full">
                    <span>Conditions</span>
                    <textarea name="terms_conditions" rows="4"><?= htmlspecialchars((string)$quote['terms_conditions'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
            </div>

            <div class="stack-md">
                <h2>Lignes formule</h2>
                <?php foreach ($formulaLines as $line): ?>
                    <div class="quote-line-row">
                        <input type="hidden" name="formula_formula_id[]" value="<?= (int)$line['formula_id'] ?>">
                        <input type="text" name="formula_label[]" value="<?= htmlspecialchars($line['label'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Libelle">
                        <input type="number" name="formula_quantity[]" step="1" min="0" value="<?= htmlspecialchars((string)$line['quantity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Quantite">
                        <input type="number" name="formula_unit_price[]" step="0.01" min="0" value="<?= htmlspecialchars((string)$line['unit_price'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Prix unitaire">
                        <div class="ingredient-unit-hint">Total ligne : <?= number_format((float)$line['total_price'], 2, ',', ' ') ?> EUR</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="stack-md">
                <h2>Frais predefinis</h2>
                <?php foreach ($fixedFeeRows as $row): ?>
                    <div class="quote-line-row">
                        <select name="fixed_fee_id[]">
                            <option value="">Frais predefini</option>
                            <?php foreach ($fixedFees as $fixedFee): ?>
                                <option value="<?= (int)$fixedFee['id'] ?>" <?= (int)$fixedFee['id'] === (int)$row['fixed_fee_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($fixedFee['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="fixed_fee_quantity[]" step="1" min="0" value="<?= htmlspecialchars((string)$row['quantity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Quantite">
                        <input type="number" name="fixed_fee_unit_price[]" step="0.01" min="0" value="<?= htmlspecialchars((string)$row['unit_price'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Montant">
                        <div class="ingredient-unit-hint">Frais configure par l admin.</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="stack-md">
                <h2>Frais libres</h2>
                <?php foreach ($freeFeeRows as $row): ?>
                    <div class="quote-line-row">
                        <input type="text" name="free_fee_label[]" value="<?= htmlspecialchars((string)$row['label'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Libelle du frais">
                        <input type="number" name="free_fee_quantity[]" step="1" min="0" value="<?= htmlspecialchars((string)$row['quantity'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Quantite">
                        <input type="number" name="free_fee_unit_price[]" step="0.01" min="0" value="<?= htmlspecialchars((string)$row['unit_price'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Montant">
                        <div class="ingredient-unit-hint">Exemple : livraison, deplacement, mise en place.</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions-inline">
                <button class="btn btn-primary" type="submit">Enregistrer le devis</button>
            </div>
        </form>
    </section>
</section>
