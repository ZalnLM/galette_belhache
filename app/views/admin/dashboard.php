<section class="stack-xl">
    <div class="hero hero-tight">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Pilotage du site</h1>
            <p>Cette premiere version couvre deja l authentification, les formules, les demandes de devis et le suivi des utilisateurs.</p>
        </div>
    </div>

    <div class="stats-grid">
        <article class="stat-card"><span>Utilisateurs</span><strong><?= (int)$stats['users'] ?></strong></article>
        <article class="stat-card"><span>Recettes</span><strong><?= (int)$stats['recipes'] ?></strong></article>
        <article class="stat-card"><span>Formules</span><strong><?= (int)$stats['formulas'] ?></strong></article>
        <article class="stat-card"><span>Demandes</span><strong><?= (int)$stats['quote_requests'] ?></strong></article>
    </div>

    <section class="panel">
        <div class="section-head">
            <h2>Acces rapides</h2>
        </div>
        <div class="actions">
            <a class="btn btn-primary" href="/admin/users">Utilisateurs</a>
            <a class="btn btn-primary" href="/admin/recipes">Recettes</a>
            <a class="btn btn-primary" href="/admin/formulas">Formules</a>
            <a class="btn btn-primary" href="/admin/quote-requests">Demandes de devis</a>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <h2>Dernieres demandes</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Evenement</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestRequests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars(trim($request['first_name'] . ' ' . $request['last_name']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($request['event_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($request['event_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a class="btn btn-light" href="/admin/quote-requests/<?= (int)$request['id'] ?>">Ouvrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
