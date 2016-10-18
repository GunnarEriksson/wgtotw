<?php if (is_array($questions)) : ?>
<div id='questions'>
<?php foreach ($questions as $question) : ?>
<div id='question-wrapper'>
    <div id="statistics">
        <p><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> <?= $question->score ?></p>
        <p><i class="fa fa-comments-o" aria-hidden="true"></i> <?= $question->answers ?></p>
    </div>
    <div id="question">
        <div id="heading">
            <h4><a href='<?=$this->url->create('questions/id/' . $question->id)?>'><?= htmlentities($question->title, null, 'UTF-8') ?></a></h4>
        </div>
        <div id="content">
            <?= $this->textFilter->doFilter(htmlentities($question->content, null, 'UTF-8'), 'shortcode, markdown') ?>
        </div>
        <div id="author-wrapper">
            <span id='author'><?= htmlentities($question->author, null, 'UTF-8') ?></span>
            <span id='time'> &#0149; <?= $question->created ?></span>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
