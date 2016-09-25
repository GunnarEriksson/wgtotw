<?php
/**
 * Config-file for navigation bar.
 *
 */
return [

    // Use for styling the menu
    'class' => 'navbar',

    // Here comes the menu strcture
    'items' => [

        // This is a menu item
        'home'  => [
            'text'  => 'Hem',
            'url'   => $this->di->get('url')->create(''),
            'title' => 'Första sida i Allt om landskapsfotografering'
        ],

        // This is a menu item
        'questions'  => [
            'text'  => 'Frågor',
            'url'   => $this->di->get('url')->create('questions'),
            'title' => 'Frågor'
        ],

        // This is a menu item
        'tags'  => [
            'text'  => 'Taggar',
            'url'   => $this->di->get('url')->create('tags'),
            'title' => 'Taggar'
        ],

        // This is a menu item
        'users'  => [
            'text'  => 'Användare',
            'url'   => $this->di->get('url')->create('users'),
            'title' => 'Taggar'
        ],

        // This is a menu item
        'about'  => [
            'text'  => 'Om Oss',
            'url'   => $this->di->get('url')->create('about'),
            'title' => 'Om oss'
        ],

        // This is a menu item
        'userAdmin'  => [
            'text'  => ($this->di->LoggedIn->isLoggedin() ? 'Profil' : 'Login'),
            'url'   => ($this->di->LoggedIn->isLoggedin() ? $this->di->get('url')->create('profile') : $this->di->get('url')->create('login')),
            'title' => ($this->di->LoggedIn->isLoggedin() ? 'Profil' : 'Login')
        ],

        // This is a menu item
        'registration'  => [
            'text'  => 'Skapa konto',
            'url'   => $this->di->get('url')->create('registration'),
            'title' => 'Skapa konto'
        ],
    ],



    /**
     * Callback tracing the current selected menu item base on scriptname
     *
     */
    'callback' => function ($url) {
        if ($url == $this->di->get('request')->getCurrentUrl(false)) {
            return true;
        }
    },



    /**
     * Callback to check if current page is a decendant of the menuitem, this check applies for those
     * menuitems that has the setting 'mark-if-parent' set to true.
     *
     */
    'is_parent' => function ($parent) {
        $route = $this->di->get('request')->getRoute();
        return !substr_compare($parent, $route, 0, strlen($parent));
    },



   /**
     * Callback to create the url, if needed, else comment out.
     *
     */
   /*
    'create_url' => function ($url) {
        return $this->di->get('url')->create($url);
    },
    */
];
