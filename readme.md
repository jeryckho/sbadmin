## SBAdmin ##
 
### Installation ###
 
Add Scafold to your composer.json file to require Scafold :
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
 
### Publish ###
 
The last required step is to launch artisan :
```
    php artisan sb:prepare
```
 
Congratulations, you have successfully installed SBAdmin !