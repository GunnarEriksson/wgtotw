<?php
/**
 * Config-file for navigation bar for the website "Allt om
 * Landskapsfotografering".
 */
return [

    // Use for styling the menu
    'class' => 'navbar',

    // The menu strcture
    'items' => [

        // The home menu item.
        'home'  => [
            'text'  => 'Hem',
            'url'   => $this->di->get('url')->create(''),
            'title' => 'Första sida i Allt om landskapsfotografering'
        ],

        // The question menu item.
        'questions'  => [
            'text'  => 'Frågor',
            'url'   => $this->di->get('url')->create('questions'),
            'title' => 'Frågor'
        ],

        // The tag menu item.
        'tags'  => [
            'text'  => 'Taggar',
            'url'   => $this->di->get('url')->create('tags'),
            'title' => 'Taggar'
        ],

        // The user menu item.
        'users'  => [
            'text'  => 'Användare',
            'url'   => $this->di->get('url')->create('users'),
            'title' => 'Taggar'
        ],

        // The about us menu item.
        'about'  => [
            'text'  => 'Om Oss',
            'url'   => $this->di->get('url')->create('about'),
            'title' => 'Om oss'
        ],

        // The log in or profile menu item.
        // When a user has logged in, the menu item changes from
        // log in to profile. When a user logs out, it changes from
        // profile to log in.
        'userAdmin'  => [
            'text'  => ($this->di->LoggedIn->isLoggedin() ? 'Profil' : 'Login'),
            'url'   => ($this->di->LoggedIn->isLoggedin() ? $this->di->get('url')->create('profile') : $this->di->get('url')->create('login')),
            'title' => ($this->di->LoggedIn->isLoggedin() ? 'Profil' : 'Login')
        ],

        // The create new account menu item.
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
     */
    'is_parent' => function ($parent) {
        $route = $this->di->get('request')->getRoute();
        return !substr_compare($parent, $route, 0, strlen($parent));
    },
];
