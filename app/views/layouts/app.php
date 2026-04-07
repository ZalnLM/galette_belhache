<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($pageTitle ?? APP_NAME) . ' | ' . APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="site-shell">
        <header class="topbar">
            <a href="/" class="brand"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></a>
            <nav class="topnav">
                <?php if ($currentUser): ?>
                    <a href="/">Formules</a>
                    <a href="/mes-demandes">Mes demandes</a>
                    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                        <a href="/admin">Admin</a>
                        <a href="/admin/users">Utilisateurs</a>
                        <a href="/admin/quote-requests">Demandes</a>
                        <a href="/admin/ingredients">Ingredients</a>
                        <a href="/admin/recipes">Recettes</a>
                        <a href="/admin/formulas">Formules</a>
                    <?php endif; ?>
                    <form method="post" action="/logout" class="logout-form">
                        <?= Csrf::input() ?>
                        <button type="submit" class="topnav-button">Deconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Connexion</a>
                    <a href="/register">Inscription</a>
                <?php endif; ?>
            </nav>
        </header>

        <main class="page-wrap">
            <?php if ($flash): ?>
                <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</body>
</html>
