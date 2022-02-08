# Config manager
Basic Config usage, creating and sync rows across of ID

## Usage
```php
<?php

// Autoload files using the Composer autoloader.
require_once __DIR__ . '/../../vendor/autoload.php';

use Config\ConfigContr;

$dbhost = '127.0.0.1';
$dbuser = 'root';
$dbpass = '';
$dbname = 'test';
$port   = 3306;

# items to create
$botConfigRows = [
    "bot_name"          => "This is my name",
    "exchange"          => "bitfinex",
];

# Table format data + rows to create
$botConfigTabData = [
    'tableName'         => 'bot_configs', 
    'itemName'          => 'botId', 
    'defaultConfigRows' => $botConfigRows
];


# create Object
$cfg = new ConfigContr($botConfigTabData);

# create new config
$newConfigId = $cfg->createConfig();

# load config
$cfg->loadConfig($newConfigId);


print_r($cfg->getAllConfigRows());
print_r($cfg->getItemId());






```