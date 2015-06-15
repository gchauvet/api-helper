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

Methods
=======

List of methods you can use :
- getUser : Return a \stdClass with the user's data (and CV with their titles)
- getMainCv : Return the main CV as \stdClass
- getCv(:id) : Give an id and  get the CV as \stdClass
- getStatistics : Return user's statistics
- getEmploymentPreferences : Return user's employment preferences
- getDisplayOptions(:id, :type) : Return the display option for the given type (web, mobile, print)
- getAccessToken : Return the access token
- setAccessToken(:token, :secret): You can set the access token
- clearAll : remove all data in storage (Session by default)
- connect(:redirect = false, :callbackUrl) : Will connect your user and get the access token

If you want to know the properties available on \stdClass see json format on our [doc](http://doc.doyoubuzz.com/).
