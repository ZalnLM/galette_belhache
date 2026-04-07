<section class="panel">
    <div class="section-head">
        <h1>Mes demandes</h1>
        <p>Retrouve ici l historique de tes demandes de devis et les echanges associes.</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Evenement</th>
                    <th>Date</th>
                    <th>Convives</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['event_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($request['event_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$request['total_guests'] ?></td>
                        <td><?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="btn btn-light" href="/demande-devis/<?= (int)$request['id'] ?>">Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
