<div id="home-tags">
    <div class="triptych-heading">
        <h3><?= isset($title) ? $title : null ?></h3>
    </div>
    <div id="popular-tags">
        <?php foreach ($tags as $tag) : ?>
            <span class="tag small"><a href='<?=$this->url->create('questions/tag-id/' . $tag->id)?>'><?= $tag->label ?> x <?= $tag->numQuestions ?></a></span>
        <?php endforeach; ?>
    </div>
</div>
