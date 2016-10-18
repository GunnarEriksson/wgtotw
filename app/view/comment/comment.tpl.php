
<div class='item-wrapper'>
    <div class="item-statistics">
        <p><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> <?= $comment->score ?></p>
    </div>
    <div class="item">
        <div class="item-heading">
            <h4><a href='<?=$this->url->create('questions/id/' . $questionId)?>'><?= htmlentities($title, null, 'UTF-8') ?></a></h4>
        </div>
        <div class="item-content">
            <?= $this->textFilter->doFilter(htmlentities($comment->content, null, 'UTF-8'), 'shortcode, markdown') ?>
        </div>
        <div class="item-author-wrapper">
            <span class='item-author'><?= htmlentities($comment->acronym, null, 'UTF-8') ?></span>
            <span class='item-time'> &#0149; <?= $comment->created ?></span>
        </div>
    </div>
</div>
