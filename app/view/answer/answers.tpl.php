<div id="answers">
<span id='answer-counter'><h3><?= $numOfAnswers ?> Svar</h3></span>
<?php foreach ($answers as $answer) : ?>
<div class="answer">
    <table>
        <tbody>
            <tr>
                <td class="vote-cell">
                    <div class="vote">
                        <p><i class="fa fa-caret-up fa-2x" aria-hidden="true"></i></p>
                        <p><span class='score'><?= $answer->score ?></span></p>
                        <p><i class="fa fa-caret-down fa-2x" aria-hidden="true"></i></p>
                    </div>
                </td>
                <td class=content-cell>
                    <div>
                        <div class="content-text">
                            <?= $this->textFilter->doFilter($answer->content, 'shortcode, markdown') ?>
                        </div>
                        <table class="answer-requester">
                            <tbody>
                                <tr>
                                    <td class="post-menu">
                                        <div class="menu">
                                            <a class="edit" href='<?=$this->url->create('answers/update/' . $answer->id)?>'>Uppdatera</a>
                                        </div>
                                    </td>
                                    <td class="post-signature">
                                        <div class="user-info">
                                            <div class="request-time">
                                                <span class='time'><?= $answer->created ?></span>
                                            </div>
                                            <div class="user-gravatar">
                                                <img src='<?= $answer->gravatar ?>?s=20' alt='Gravatar'>
                                            </div>
                                            <div class="user-details">
                                                <span class='author'><?= $answer->acronym ?></span>
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
                <td class="comment-cell">
                    <a class="comment" href='<?=$this->url->create('answers/add-comment/' . $answer->id)?>'>Lägg till en kommentar</a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php endforeach; ?>
</div>
