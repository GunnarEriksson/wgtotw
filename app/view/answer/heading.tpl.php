<div id="answers-header">
    <div id="answers-subheader">
        <h3><?= $numOfAnswers ?> Svar</h3>
        <div>
            <div id="tabs">
                <a <?= isset($latest) ? null : 'class="selected"' ?> href='<?=$this->url->create('questions/id/' . $questionId)?>'>Röster</a>
                <a <?= isset($latest) ? 'class="selected"' : null  ?> href='<?=$this->url->create('questions/id/' . $questionId . '/latest')?>'>Senaste</a>
            </div>
        </div>
    </div>
</div>
