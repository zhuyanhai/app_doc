<?php

namespace App\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use App\Libraries\Utils;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        //$this->mapApiRoutes();

        //$this->mapWebRoutes();


        //临时上传
        Route::group(['namespace' => $this->namespace . '\Upload', 'prefix' => 'upload'], function ()
        {
            Route::any('/personFrontPic/process', 'PersonFrontPicController@process');
        });

        //首页
        Route::get('/', function () {
            if (Utils\IsMobileVisit::has()) {
                return view('welcome_h5');
            } else {//pc
                return view('welcome');
            }
        });

        //天气
        Route::get('/weather', function () {
            return view('weather');
        });


        //Route::any('/upload/personFrontPic/process', 'Upload\PersonFrontPicController@process');


        //自定义API路由 allen
        Route::group(['namespace' => $this->namespace, 'prefix' => 'api'], function ()
        {
            $pathInfo  = Request::path();
            $pathArray = explode('/', $pathInfo);
            if (count($pathArray) == 3) {
                Route::any($pathArray[1].'/'.$pathArray[2], ucfirst($pathArray[1]).'\\Api\\'.ucfirst($pathArray[2]).'Controller@run');
            }
        });
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::group([
            'middleware' => 'web',
            'namespace' => $this->namespace,
        ], function ($router) {
            require base_path('routes/web.php');
        });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::group([
            'middleware' => 'api',
            'namespace' => $this->namespace,
            'prefix' => 'api',
        ], function ($router) {
            require base_path('routes/api.php');
        });
    }
}
