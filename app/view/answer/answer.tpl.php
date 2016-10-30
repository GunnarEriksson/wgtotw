<div class="answer">
    <table>
        <tbody>
            <tr>
                <td class="vote-cell">
                    <div class="vote">
                        <p><a href='<?=$this->url->create('answers/up-vote/' . $answer->id)?>'><i class="fa fa-caret-up fa-2x" aria-hidden="true"></i></a></p>
                        <p><span class='score'><?= $answer->score ?></span></p>
                        <p><a href='<?=$this->url->create('answers/down-vote/' . $answer->id)?>'><i class="fa fa-caret-down fa-2x" aria-hidden="true"></i></a></p>
                        <?= $accepted = ($answer->accepted == 1) ? '<p><i class="fa fa-check fa-2x" aria-hidden="true"></i></p>' : null ?>
                    </div>
                </td>
                <td class=content-cell>
                    <div>
                        <div class="content-text">
                            <?= $this->textFilter->doFilter(htmlentities($answer->content, null, 'UTF-8'), 'shortcode, markdown') ?>
                        </div>
                        <table class="answer-requester">
                            <tbody>
                                <tr>
                                    <td class="post-menu">
                                        <div class="menu">
                                            <?php if ($this->LoggedIn->isAllowed($questionUserId)) : ?>
                                                <a class="accept" href='<?=$this->url->create('answers/accept/' . $answer->id)?>'>Acceptera</a>
                                            <?php endif; ?>
                                            <?php if ($this->LoggedIn->isAllowed($answer->answerUserId)) : ?>
                                                <a class="edit" href='<?=$this->url->create('answers/update/' . $answer->id)?>'>Uppdatera</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="post-signature">
                                        <div class="user-info">
                                            <div class="request-time">
                                                <span class='time'><?= $answer->created ?></span>
                                            </div>
                                            <div class="user-gravatar">
                                                <img src='<?= htmlentities($answer->gravatar, null, 'UTF-8') ?>?s=20' alt='Gravatar'>
                                            </div>
                                            <div class="user-details">
                                                <span class='author'><?= htmlentities($answer->acronym, null, 'UTF-8') ?></span>
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
                    <div class="comment">
                        <table>
                            <tbody>
                                <?php foreach ($comments as $comment) : ?>
                                    <tr class="comment-row">
                                        <td class="comment-vote-cell">
                                            <div class="comment-vote">
                                                <p><a href='<?=$this->url->create('comments/up-vote/' . $comment->id)?>'><i class="fa fa-caret-up" aria-hidden="true"></i></a></p>
                                                <p><span class='comment-score'><?= $comment->score ?></span></p>
                                                <p><a href='<?=$this->url->create('comments/down-vote/' . $comment->id)?>'><i class="fa fa-caret-down" aria-hidden="true"></i></a></p>
                                            </div>
                                        </td>
                                        <td class="comment-cell">
                                            <div class="comment-text">
                                                <?= $this->textFilter->doFilter(htmlentities($comment->content, null, 'UTF-8'), 'shortcode, markdown') ?>
                                                <?php if ($this->LoggedIn->isAllowed($comment->userId)) : ?>
                                                    <p><a id="edit" href='<?=$this->url->create('comments/update/' . $comment->id)?>'>Uppdatera</a></p>
                                                <?php endif; ?>
                                                <span class='comment-author'> - <?= htmlentities($comment->acronym, null, 'UTF-8') ?></span>
                                                <span class='comment-time'> &#8226; <?= $comment->created ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="comment-add">
                        <a href='<?=$this->url->create('answers/add-comment/' . $answer->id)?>'>Lägg till en kommentar</a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
