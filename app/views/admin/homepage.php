<?php
$heroTitle = (string)($settings['home_hero_title'] ?? '');
$heroText = (string)($settings['home_hero_text'] ?? '');
$heroImage = (string)($settings['home_hero_image'] ?? '');
?>

<section class="stack-xl">
    <section class="panel stack-lg">
        <div class="section-head">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Accueil</h1>
            </div>
            <p class="text-muted">Personnalise le visuel principal et le texte de la page d accueil.</p>
        </div>

        <form method="post" action="/admin/homepage" enctype="multipart/form-data" class="stack-lg">
            <?= Csrf::input() ?>
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8') ?>">

            <div class="grid-form two-cols">
                <label class="full">
                    <span>Titre principal</span>
                    <input type="text" name="hero_title" value="<?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label class="full">
                    <span>Texte d introduction</span>
                    <textarea name="hero_text" rows="4"><?= htmlspecialchars($heroText, ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <label class="full">
                    <span>Image d accueil</span>
                    <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp">
                </label>
                <?php if ($heroImage !== ''): ?>
                    <label class="check">
                        <input type="checkbox" name="remove_image">
                        <span>Supprimer l image actuelle</span>
                    </label>
                <?php endif; ?>
            </div>

            <?php if ($heroImage !== ''): ?>
                <div class="media-preview">
                    <img src="<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8') ?>" alt="Apercu image d accueil">
                </div>
            <?php endif; ?>

            <button class="btn btn-primary" type="submit">Enregistrer l accueil</button>
        </form>
    </section>
</section>
