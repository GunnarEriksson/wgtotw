<div id="home-users">
    <div class="triptych-heading">
        <h3><?= isset($title) ? $title : null ?></h3>
    </div>
    <div id="active-users">
        <table>
            <?php foreach ($users as $user) : ?>
                <tr>
                    <td>
                        <img src='<?= $user->gravatar ?>?s=20' alt='Gravatar'>
                    </td>
                    <td>
                        <?php if ($this->LoggedIn->isLoggedin()) : ?>
                            <a href='<?=$this->url->create('users/id/' . $user->id)?>'><?= $user->acronym ?></a>
                        <?php else : ?>
                            <?= $user->acronym ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
