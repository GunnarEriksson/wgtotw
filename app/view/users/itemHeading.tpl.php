<div id="answers-header">
    <div id="answers-subheader">
        <h3><?= $numOfAnswers ?> <?= $item ?></h3>
        <div>
            <div id="tabs">
                <a <?= strcmp($type, "question") === 0 ? 'class="selected"' : null ?> href='<?=$this->url->create('users/id/' . $userId)?>'>Frågor</a>
                <a <?= strcmp($type, "answer") === 0 ? 'class="selected"' : null  ?> href='<?=$this->url->create('users/id/' . $userId . '/answer')?>'>Svar</a>
                <a <?= strcmp($type, "comment") === 0 ? 'class="selected"' : null  ?> href='<?=$this->url->create('users/id/' . $userId . '/comment')?>'>Kommentarer</a>
            </div>
        </div>
    </div>
</div>
