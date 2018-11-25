<?

$adminPath = ADMIN_PATH;
$basePath = BASE_PATH;
$appPath = APP_PATH;

return [
    'id' => 'console',
    'language' => 'ru-RU',
    'basePath' => $adminPath,
    'runtimePath' => $basePath . '/runtime',
    'vendorPath' => $basePath . '/vendor',
    'aliases' => 
    [
        '@basePath' => $basePath,
        '@app' => $appPath,
        '@' . APP_NAME => $appPath,
        '@admin' => $adminPath,        
        '@webroot' => $basePath . '/public_html',
    ],
    'bootstrap' => ['log', 'gii'],
    'controllerNamespace' => 'admin\commands',
    'controllerMap' => [
        'yml' => [
            'class' => 'admin\modules\yml\commands\YmlController'
        ],
    ],
    'modules' => [
        'admin' => [
            'class' => 'admin\AdminModule',
        ],
        'gii' => 'yii\gii\Module',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                    [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'i18n' => [
            'translations' => [
                'admin' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'sourceLanguage' => 'ru-RU',
                    'basePath' => '@admin/messages',
                    'fileMap' => [
                        'app' => 'admin.php',
                    ]
                ],
                '*' => [
                    'class' => 'yii\i18n\DbMessageSource',
                    'sourceMessageTable' => 'admin_translate_source_message',
                    'messageTable' => 'admin_translate_message',
                    'enableCaching' => true,
                ],
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'assignmentTable' => 'admin_auth_assignment',
            'itemChildTable' => 'admin_auth_item_child',
            'itemTable' => 'admin_auth_item',
            'ruleTable' => 'admin_auth_rule',
            'defaultRoles' => [
                'User',
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'admin/<controller:\w+>/<action:[\w-]+>/<id:\d+>' => 'admin/<controller>/<action>',
                'admin/<module:\w+>/<controller:\w+>/<action:[\w+]+>/<id:\d+>' => 'admin/<module>/<controller>/<action>'
            ],
        ],
    ],
    'params' => $params,
];
