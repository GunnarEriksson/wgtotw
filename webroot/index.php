<?php
/**
 * This is a Anax-MVC front controller for the me page.
 *
 * Contains a short presentation of the author of this page.
 */
require __DIR__ . '/config_with_app.php';

$app->url->setUrlType(\Anax\Url\CUrl::URL_CLEAN);

$app->navbar->configure(ANAX_APP_PATH . 'config/navbar_me.php');
$app->theme->configure(ANAX_APP_PATH . 'config/theme_me.php');

$app->router->add('', function () use ($app) {
    $app->theme->setTitle("Allt om landskapsfotografering");

    $content = $app->fileContent->get('index.md');
    $content = $app->textFilter->doFilter($content, 'shortcode, markdown');

    $app->views->add('me/page', [
        'content' => $content
    ], 'main-wide');

    $app->dispatcher->forward([
        'controller' => 'questions',
        'action'     => 'list-latest',
        'params'     => [4],
    ]);

    $app->dispatcher->forward([
        'controller' => 'tags',
        'action'     => 'list-popular',
        'params'     => [6],
    ]);

    $app->dispatcher->forward([
        'controller' => 'users',
        'action'     => 'list-active',
        'params'     => [4],
    ]);
});

$app->router->add('questions', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'questions',
        'action'     => 'list',
    ]);
});

$app->router->add('tags', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'tags',
        'action'     => 'list',
    ]);
});

$app->router->add('users', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'users',
        'action'     => 'list',
    ]);
});

$app->router->add('about', function () use ($app) {
    $app->theme->setTitle("Om Oss");

    $content = $app->fileContent->get('about.md');
    $content = $app->textFilter->doFilter($content, 'shortcode, markdown');

    $app->views->add('me/page', [
        'content' => $content
    ], 'main-wide');

    $bylineContent = $app->fileContent->get('about/byline.md');
    $bylineContent = $app->textFilter->doFilter($bylineContent, 'shortcode, markdown');

    $app->views->add('me/byline', [
        'content' => $bylineContent
    ], 'main-wide');

});

$app->router->add('login', function () use ($app) {
    $app->theme->setTitle("Logga in");

    $app->dispatcher->forward([
        'controller' => 'user-login',
        'action'     => 'login',
    ]);
});

$app->router->add('profile', function () use ($app) {

    $userId = $app->LoggedIn->getUserId();
    $userId = $userId ? $userId : null;

    $app->theme->setTitle("Profil");
    $app->dispatcher->forward([
        'controller' => 'users',
        'action'     => 'id',
        'params'     => [$userId],
    ]);
});

$app->router->add('registration', function () use ($app) {
    $app->theme->setTitle("Skapa Konto");

    $app->dispatcher->forward([
        'controller' => 'users',
        'action'     => 'add',
    ]);
});

$app->router->handle();
$app->theme->render();
//echo $app->logger->renderLog();
//
