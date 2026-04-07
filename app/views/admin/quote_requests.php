<section class="panel">
    <div class="section-head">
        <h1>Demandes de devis</h1>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
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
                        <td><?= htmlspecialchars(trim($request['first_name'] . ' ' . $request['last_name']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($request['event_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($request['event_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$request['total_guests'] ?></td>
                        <td><?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="btn btn-light" href="/admin/quote-requests/<?= (int)$request['id'] ?>">Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
