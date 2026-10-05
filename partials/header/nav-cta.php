<?php
$object = get_queried_object();
$nav_cta = get_field('nav_cta', "option") ?? null;
?>

<?php if ($nav_cta): ?>
    <div class="nav-cta">
        <div class="button-background-primary">
            <a href="<?= $nav_cta['url'] ?>" class="btn"><?= $nav_cta['title'] ?></a>
        </div>
    </div>
<?php endif; ?>