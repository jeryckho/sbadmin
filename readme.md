## SBAdmin ##
 
### Installation ###

Launch composer :
```
    composer require jeryckho/sbadmin:dev-master --dev
```

OR

Add SBAdmin to your composer.json file to require SBAdmin :
```
    require : {
        "laravel/framework": "5.1.*",
        "jeryckho/sbadmin": "dev-master"
    }
```
 
Update Composer :
```
    composer update
```
 
The next required step is to add the service provider to config/app.php :
```
    'jeryckho\sbadmin\SbadminServiceProvider',
```
 
### Use ###
 
The last required step is to launch artisan :
```
    php artisan sb:prepare
```
 
Congratulations, you have successfully installed SBAdmin !