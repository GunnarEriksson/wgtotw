<h1><?= $title ?></h1>
<h3><?= $question->title ?></h3>
<div id="question">
    <table>
        <tbody>
            <tr>
                <td class="vote-cell">
                    <div id="vote">
                        <p><a href='<?=$this->url->create('questions/up-vote/' . $question->id)?>'><i class="fa fa-caret-up fa-2x" aria-hidden="true"></i></a></p>
                        <p><span id='score'><?= $question->score ?></span></p>
                        <p><a href='<?=$this->url->create('questions/down-vote/' . $question->id)?>'><i class="fa fa-caret-down fa-2x" aria-hidden="true"></i></a></p>
                    </div>
                </td>
                <td id=content-cell>
                    <div>
                        <div id="content-text">
                            <?= $this->textFilter->doFilter(htmlentities($question->content, null, 'UTF-8'), 'shortcode, markdown') ?>
                        </div>
                        <div id="question-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <a class="post-tag" href='<?=$this->url->create('questions/tag-id/' . $tag->id)?>'><?= $tag->label ?></a>
                            <?php endforeach; ?>
                        </div>
                        <table id="question-requester">
                            <tbody>
                                <tr>
                                    <td id="post-menu">
                                        <div id="menu">
                                            <?php if ($this->LoggedIn->isLoggedin()) : ?>
                                                <a id="answer" href='<?=$this->url->create('answers/add/' . $question->id)?>'>Svara</a>
                                            <?php endif; ?>
                                            <?php if ($this->LoggedIn->isAllowed($question->userId)) : ?>
                                                <a id="edit" href='<?=$this->url->create('questions/update/' . $question->id)?>'>Uppdatera</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td id="post-signature">
                                        <div id="user-info">
                                            <div id="request-time">
                                                <span id='time'><?= $question->created ?></span>
                                            </div>
                                            <div id="user-gravatar">
                                                <img src='<?= htmlentities($question->gravatar, null, 'UTF-8') ?>?s=20' alt='Gravatar'>
                                            </div>
                                            <div id="user-details">
                                                <span id='author'><?= htmlentities($question->acronym, null, 'UTF-8') ?></span>
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
                        <a href='<?=$this->url->create('questions/add-comment/' . $question->id)?>'>Lägg till en kommentar</a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
