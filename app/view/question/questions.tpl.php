<h1><?= $title ?></h1>
<div id="questions-heading">
<h3>Frågor</h3>
<?php if ($this->di->session->has('user')) : ?>
<span id='create-question'><a href='<?=$this->url->create('questions/add/')?>'>+Ny fråga</a></span>
<?php endif; ?>
</div>

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
            <h4><a href='<?=$this->url->create('questions/id/' . $question->id)?>'><?= $question->title ?></a></h4>
        </div>
        <div id="content">
            <?= $this->di->textFilter->doFilter($question->content, 'shortcode, markdown') ?>
        </div>
        <div id="author-wrapper">
            <span id='author'><?= $question->author ?></span>
            <span id='time'> &#0149; <?= $question->created ?></span>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>