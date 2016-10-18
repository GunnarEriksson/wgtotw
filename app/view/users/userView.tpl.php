<h1><?= htmlentities($user->acronym, null, 'UTF-8') ?></h1>
<div id="user-profile">
    <div id='left'>
        <img src='<?= htmlentities($user->gravatar, null, 'UTF-8') ?>?s=90' alt='Gravatar'>
    </div>
    <div id="user-info">
        <table>
            <tr>
                <td>
                    <i class="fa fa-user" aria-hidden="true"></i>
                </td>
                <td>
                    <?= htmlentities($user->firstName, null, 'UTF-8') ?> <?= htmlentities($user->lastName, null, 'UTF-8') ?>
                </td>
            </tr>
            <tr>
                <td>
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                </td>
                <td>
                    <?= htmlentities($user->town, null, 'UTF-8') ?>
                </td>
            </tr>
            <tr>
                <td>
                    <i class="fa fa-envelope-o" aria-hidden="true"></i>
                </td>
                <td>
                    <a target="_top" href='mailto:<?= htmlentities($user->email, null, 'UTF-8') ?>'><?= htmlentities($user->email, null, 'UTF-8') ?></a>
                </td>
            </tr>
            <tr>
                <td>
                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                </td>
                <td>
                    <?= htmlentities($user->created, null, 'UTF-8') ?>
                </td>
            </tr>
        </table>
    </div>

    <div id="user-activity" class="small">
        <table>
            <thead>
                <tr>
                    <th>Aktivitet</th>
                    <th>Antal</th>
                    <th>Poäng</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Frågor</td>
                    <td><?= $activity['questions'] ?></td>
                    <td><?= $activity['questionScore'] ?></td>
                </tr>
                <tr>
                    <td>Svar</td>
                    <td><?= $activity['answers'] ?></td>
                    <td><?= $activity['answerScore'] ?></td>
                </tr>
                <tr>
                    <td>Kommentarer</td>
                    <td><?= $activity['comments'] ?></td>
                    <td><?= $activity['commentScore'] ?></td>
                </tr>
                <tr>
                    <td>Röstningar</td>
                    <td><?= $user->numVotes ?></td>
                    <td><?= $user->numVotes ?></td>
                </tr>
                <tr>
                    <td>Accepterande</td>
                    <td><?= $activity['accepts'] ?></td>
                    <td><?= $activity['acceptScore'] ?></td>
                </tr>
                <tr>
                    <td>Antal röster</td>
                    <td></td>
                    <td><?= $activity['rankPoints'] ?></td>
                </tr>
                <tr>
                    <td>RANKNING</td>
                    <td></td>
                    <td><?= $activity['sum'] ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if ($this->LoggedIn->getUserId() === $user->id) : ?>
        <div id="user-button">
            <ul id="edit-button">
                <li><a href='<?=$this->url->create('users/update/' . $user->id)?>'>Uppdatera</a></li>
                <li><a href='<?=$this->url->create('user-login/logout')?>'>Logga ut</a></li>
            </ul>
        </div>
    <?php endif; ?>
</div>
