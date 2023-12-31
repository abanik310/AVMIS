<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'asia/dhaka',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
        |--------------------------------------------------------------------------
        | Item per page in pagination
        |--------------------------------------------------------------------------
        */
    'item_per_page' => 20,
    'title_lg_HRM' => ["title" => 'Human Resource Management'],
    'title_lg_SD' => ["title" => 'Salary Disbursement'],
    'title_lg_AVURP' => ["title" => 'Ansar VDP Unit Reform Project'],
    'title_lg_recruitment' => ['training' => ["title" => 'Training module(Recruitment)'], "title" => "Ansar Recruitment"],
    'title_lg_' => ["title" => 'Ansar & VDP ERP'],
    'title_mini_HRM' => 'HRM',
    'title_mini_SD' => 'SD',
    'title_mini_AVURP' => 'AVURP',
    //    'title_mini_recruitment'=>'AR',
    'title_mini_' => 'ERP',
    'modules' => [
        ['name' => 'HRM', 'route' => 'HRM'],
        ['name' => 'PRSD', 'route' => 'SD'],
        ['name' => 'ADAPS', 'route' => '#'],
        ['name' => 'AVURP', 'route' => 'AVURP'],
        ['name' => strtoupper('recruitment'), 'route' => 'recruitment'],
    ],
    //  'offer'=>[
    //  42,18,42,66,67,68,69,62,71,70,72,74,75,23,25,
    // ],

    'offer' => [
        //42,18,42,66,67,68,69,65,71,70,72,74,75,2,7,8,9,11,12,16,26,31,48,55,102,105
        11, 18, 42, 65, 66, 67, 68, 69, 70, 71, 72, 74, 75
    ],
    'exclude_district' => [
        9 => [70, 71, 9, 72],
        70 => [70, 71, 9, 72],
        71 => [70, 71, 9, 72],
        72 => [70, 71, 9, 72],
        66 => [66, 67, 68, 69, 13, 65, 74, 75],
        67 => [66, 67, 68, 69, 13, 65, 74, 75],
        68 => [66, 67, 68, 69, 13, 65, 74, 75],
        69 => [66, 67, 68, 69, 13, 65, 74, 75],
        65 => [66, 67, 68, 69, 13, 65, 74, 75],
        74 => [66, 67, 68, 69, 13, 65, 74, 75],
        75 => [66, 67, 68, 69, 13, 65, 74, 75],
    ],
    'DG' => [66, 67, 68, 69, 65, 74, 75],
    'CG' => [70, 71, 72],
    'file_export_path' => storage_path('export_file'),

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\RepositoryProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
        'Form' => Collective\Html\FormFacade::class,
    ])->toArray(),

];
