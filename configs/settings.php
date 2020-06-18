<?php
return [
    'fields' => [
        [
            'type' => 'open_segment'
        ],
        [
            'type'    => 'textarea',
            'heading' => 'Secret Token',
            'name'    => 'wilokeservice_client[secret_token]',
            'id'      => 'secret_token',
            'default' => '',
            'desc'    => '<a target="_blank" href="'.wilokeServiceGetConfigFile('app')['howToUpdateDoc'].'">Where Is My Secret Token Code?</a>'
        ],
        [
            'type' => 'submit',
            'name' => 'Submit'
        ],
        [
            'type' => 'close_segment'
        ]
    ]
];
