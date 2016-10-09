<?php
/**
 * This is a Anax-MVC front controller for the web site
 * "Allt om naturfotografering".
 */
require __DIR__ . '/config_with_app.php';

$app->url->setUrlType(\Anax\Url\CUrl::URL_CLEAN);

$app->navbar->configure(ANAX_APP_PATH . 'config/navbar_me.php');
$app->theme->configure(ANAX_APP_PATH . 'config/theme_me.php');

/**
 * The home page for the website.
 *
 * Views the presentation of the website and redirects to different
 * controllers to show latest questions, the most popular tags and
 * the most active users.
 */
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

/**
 * List all questions.
 *
 * Redirects to the Question controller to list all questions.
 */
$app->router->add('questions', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'questions',
        'action'     => 'list',
    ]);
});

/**
 * List all tags.
 *
 * Redirects to the Tags controller to list all tags.
 */
$app->router->add('tags', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'tags',
        'action'     => 'list',
    ]);
});

/**
 * List all users.
 *
 * Redirects to the Users controller to list all users.
 */
$app->router->add('users', function () use ($app) {
    $app->dispatcher->forward([
        'controller' => 'users',
        'action'     => 'list',
    ]);
});

/**
 * Show information about the page and the creator.
 *
 * Views information about the website and a byline with information of
 * the creator of the website.
 */
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

/**
 * Show form to log in.
 *
 * Redirects to the UserLogin controller to view a form to log in.
 */
$app->router->add('login', function () use ($app) {
    $app->theme->setTitle("Logga in");

    $app->dispatcher->forward([
        'controller' => 'user-login',
        'action'     => 'login',
    ]);
});

/**
 * Show the user profile of a user.
 *
 * Views information about the user and the user related activities.
 */
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

/**
 * Show form to create an account to be able to sign in.
 *
 * Shows information about the benefits to be member and a form to create
 * an account.
 */
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
