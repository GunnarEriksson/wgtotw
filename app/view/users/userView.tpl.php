<h1><?= $title ?></h1>
<h2><?= $user->acronym ?></h2>
<div id="user-profile">
    <div id='left'>
        <img src='<?= $user->gravatar ?>?s=90' alt='Gravatar'>
    </div>
    <div id="user-info">
        <table>
            <tr>
                <td>
                    <i class="fa fa-user" aria-hidden="true"></i>
                </td>
                <td>
                    <?= $user->name ?>
                </td>
            </tr>
            <tr>
                <td>
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                </td>
                <td>
                    <?= $user->town ?>
                </td>
            </tr>
            <tr>
                <td>
                    <i class="fa fa-envelope-o" aria-hidden="true"></i>
                </td>
                <td>
                    <a target="_top" href='mailto:<?= $user->email ?>'><?= $user->email ?></a>
                </td>
            </tr>
            <tr>
                <td>
                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                </td>
                <td>
                    <?= $user->created ?>
                </td>
            </tr>
        </table>
    </div>

    <?php if ($this->di->session->has('user') && $this->di->session->get('user')['id'] === $user->id) : ?>
        <div id="user-button">
            <ul id="edit-button">
                <li><a href='<?=$this->url->create('users/update/' . $user->id)?>'>Uppdatera</a></li>
                <li><a href='<?=$this->url->create('user-login/logout')?>'>Logga ut</a></li>
            </ul>
        </div>
    <?php endif; ?>
</div>
