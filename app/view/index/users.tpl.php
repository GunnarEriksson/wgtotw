<div class="home-users">
    <div class="triptych-heading">
        <h3><?= isset($title) ? $title : null ?></h3>
    </div>
    <div id="active-users">
        <?php foreach ($users as $user) : ?>
            <p><a href='<?=$this->url->create('users/id/' . $user->id)?>'><?= $user->acronym ?></a></p>
        <?php endforeach; ?>
    </div>
</div>
