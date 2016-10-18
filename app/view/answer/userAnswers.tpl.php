<?php if (is_array($answers)) : ?>
<div id='home-user-items'>
<?php foreach ($answers as $answer) : ?>
<div class='item-wrapper'>
    <div class="item-statistics">
        <p><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> <?= $answer->score ?></p>
        <?= $accepted = ($answer->accepted === 1) ? '<p><i class="fa fa-check fa-2x" aria-hidden="true"></i></p>' : null ?>
    </div>
    <div class="item">
        <div class="item-heading">
            <h4><a href='<?=$this->url->create('questions/id/' . $answer->questionId)?>'>Fråga: <?= $answer->questionTitle ?></a></h4>
        </div>
        <div class="item-content">
            <?= $this->textFilter->doFilter(htmlentities($answer->content, null, 'UTF-8'), 'shortcode, markdown') ?>
        </div>
        <div class="item-author-wrapper">
            <span class='item-author'><?= htmlentities($answer->acronym, null, 'UTF-8') ?></span>
            <span class='item-time'> &#0149; <?= $answer->created ?></span>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
