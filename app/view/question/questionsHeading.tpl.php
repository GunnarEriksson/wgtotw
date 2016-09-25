<h1><?= $title ?></h1>
<div id="questions-heading">
<h3>Frågor</h3>
<?php if ($this->LoggedIn->isLoggedin()) : ?>
<span id='create-question'><a href='<?=$this->url->create('questions/add/')?>'>+Ny fråga</a></span>
<?php endif; ?>
</div>
