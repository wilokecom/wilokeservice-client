<?php
return apply_filters('wilokeservice/filter/configs/app', [
    'renewSupportURL'     => 'https://themeforest.net/wiloke',
    'baseURL'             => 'https://wiloke.com/wp-json/wilokeservice/v1/',
    'serviceURL'          => 'https://wiloke.com/',
    'howToUpdateDoc'      => 'https://wiloke.com/documentation',
    'defaultChangeLogUrl' => 'https://wiloke.net/themes/changelog/8',
    'author'              => 'wiloke',
    'updateSlug'          => 'wilokeservice',
    'unreadOption'        => 'wiloke_service_unread_notifications',
    'menu'                => [
        'title' => 'Wiloke Service',
        'roles' => 'administrator',
        'slug'  => 'wilokeservice' // must the same updateSlug
    ]
]);
