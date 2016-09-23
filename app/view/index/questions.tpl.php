<div id="home-questions">
    <div class="triptych-heading">
        <h3><?= isset($title) ? $title : null ?></h3>
    </div>
    <div id="latest-questions">
        <?php foreach ($questions as $question) : ?>
            <p><a href='<?=$this->url->create('questions/id/' . $question->id)?>'><?= $question->title ?></a></p>
        <?php endforeach; ?>
    </div>
</div>
