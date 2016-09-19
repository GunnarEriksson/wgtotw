<?php if (isset($title)) : ?>
    <h1><?= $title ?></h1>
<?php endif; ?>

<?php if (isset($subtitle)) : ?>
    <h2><?= $subtitle ?></h2>
<?php endif; ?>

<?php if (isset($message)) : ?>
    <p><?= $message ?></p>
<?php endif; ?>

<?php if (isset($url)) : ?>
    <ul class="button">
        <li><a href='<?=$this->url->create($url)?>'><?= $buttonName = isset($buttonName) ? $buttonName : "Ok" ?></a></li>
    </ul>
<?php endif; ?>
