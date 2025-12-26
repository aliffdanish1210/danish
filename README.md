
php artisan make:migration add_role_to_users_table --table=users
php artisan make:middleware RoleMiddleware
php artisan make:model Event -m
php artisan migrate
php artisan make:controller EventController --resource
php artisan make:policy EventPolicy --model=Event
php artisan make:controller ProfileController

//for after change need to clear all things first before refresh the web
php artisan route:clear
php artisan view:clear 
php artisan config:cache                                               
php artisan cache:clear

//for the all change to clear all things (just one command)
php artisan optimize:clear

//for the role permission
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate

//for secure https
public function boot()
{
    if (app()->environment('local')) {
        URL::forceScheme('https');
    }
}

for publish the github
git add .
git commit -m ""
git push