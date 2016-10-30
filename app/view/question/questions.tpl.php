<?php if (is_array($questions)) : ?>
<div id='questions'>
<?php foreach ($questions as $question) : ?>
<div class='question-wrapper'>
    <div class="statistics">
        <p><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> <?= $question->score ?></p>
        <p><i class="fa fa-comments-o" aria-hidden="true"></i> <?= $question->answers ?></p>
    </div>
    <div class="question">
        <div class="heading">
            <h4><a href='<?=$this->url->create('questions/id/' . $question->id)?>'><?= htmlentities($question->title, null, 'UTF-8') ?></a></h4>
        </div>
        <div class="content">
            <?= $this->textFilter->doFilter(htmlentities($question->content, null, 'UTF-8'), 'shortcode, markdown') ?>
        </div>
        <div class="author-wrapper">
            <span class='author'><?= htmlentities($question->author, null, 'UTF-8') ?></span>
            <span class='time'> &#8226; <?= $question->created ?></span>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
