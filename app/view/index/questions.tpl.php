<div id="home-questions">
    <div class="triptych-heading">
        <h3><?= isset($title) ? $title : null ?></h3>
    </div>
    <?php foreach ($questions as $question) : ?>
    <div class='question-wrapper'>
        <div class="question">
            <div class="heading">
                <a href='<?=$this->url->create('questions/id/' . $question->id)?>'><?= $question->title ?></a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
