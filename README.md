DoYouBuzz API Helper
===========

If you want to use DoYouBuzz in your PHP project this project can help you.

Install
=======

You can install this library by using [Packagist](https://packagist.org/packages/doyoubuzz/api-helper). 

Add the following to your
composer.json file.  Composer will handle the autoloading.

```json
{
    "require": {
        "doyoubuzz/api-helper": "dev-master"
    }
}
```

Usage
=======

```php
<?php
// include your autoloader then :
$apiKey = 'XXX';
$apiSecret = 'YYY';
$dyb = new \DoYouBuzz\ApiHelper\DoYouBuzzAPI($apiKey, $apiSecret);
$dyb->connect(true);
$user = $dyb->getUser();
if ($user) {
    echo 'Utilisateur : ' .  $user->firstname . ' ' . $user->lastname . '<br>';

    $mainCv = $dyb->getMainCv();
    if ($mainCv) {
        echo 'Cv : ' . $mainCv->title . '<br>';
    }
}
```
