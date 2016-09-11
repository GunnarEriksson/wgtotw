<?php if (isset($title)) : ?>
    <h1><?= $title ?></h1>
<?php endif; ?>

<?php if (isset($subtitle)) : ?>
    <h2><?= $subtitle ?></h2>
<?php endif; ?>

<?php if (isset($message)) : ?>
    <p><?= $message ?></p>
<?php endif; ?>

<ul class="button">
    <li><a href='<?=$this->url->create($_SERVER['HTTP_REFERER'])?>'>Tillbaka</a></li>
</ul>
