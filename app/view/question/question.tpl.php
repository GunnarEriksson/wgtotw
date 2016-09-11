<h1><?= $title ?></h1>
<h3><?= $question->title ?></h3>
<div id="question">
    <table>
        <tbody>
            <tr>
                <td class="vote-cell">
                    <div id="vote">
                        <p><i class="fa fa-caret-up fa-2x" aria-hidden="true"></i></p>
                        <p><span id='score'><?= $question->score ?></span></p>
                        <p><i class="fa fa-caret-down fa-2x" aria-hidden="true"></i></p>
                    </div>
                </td>
                <td id=content-cell>
                    <div>
                        <div id="content-text">
                            <?= $this->di->textFilter->doFilter($question->content, 'shortcode, markdown') ?>
                        </div>
                        <div id="question-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <a id="post-tag" href='<?=$this->url->create('questions/tag-id/' . $tag->id)?>'><?= $tag->label ?></a>
                            <?php endforeach; ?>
                        </div>
                        <table id="question-requester">
                            <tbody>
                                <tr>
                                    <td id="post-menu">
                                        <div id="menu">
                                            <a id="answer" href='#'>Svara</a>
                                            <a id="edit" href='#'>Uppdatera</a>
                                        </div>
                                    </td>
                                    <td id="post-signature">
                                        <div id="user-info">
                                            <div id="request-time">
                                                <span id='time'><?= $question->created ?></span>
                                            </div>
                                            <div id="user-gravatar">
                                                <img src='<?= isset($user) ? $user->gravatar : null ?>?s=20' alt='Gravatar'>
                                            </div>
                                            <div id="user-details">
                                                <span id='author'><?= $question->author ?></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="vote-cell"></td>
                <td>
                    <a id="comment" href='#'>Lägg till en kommentar</a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
