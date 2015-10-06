## SBAdmin ##
 
### Installation ###

Launch composer :
```
    composer require jeryckho/sbadmin:dev-master --dev
```

OR

Add SBAdmin to your composer.json file to require SBAdmin :
```
    require-dev : {
        "jeryckho/sbadmin": "dev-master"
    }
```
 
Update Composer :
```
    composer update
```
 
In both case, the next required step is to add the service provider to config/app.php :
```
    'jeryckho\sbadmin\SbadminServiceProvider',
```
 
### Use ###
 
The last required step is to launch artisan :
```
    php artisan sb:prepare
```

OR

```
    php artisan sb:simpleadd MyClass
```
 
Congratulations, you have successfully installed SBAdmin !