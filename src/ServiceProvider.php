<?php
/**
 * Created by PhpStorm.
 * User: sergio.rodenas
 * Date: 2019-01-20
 * Time: 15:09
 */

namespace Rodenastyle\TestDoc;


use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Rodenastyle\TestDoc\Middleware\IncludeInSpecification;

class ServiceProvider extends BaseServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot(){
		if($this->app->runningUnitTests()){
			if($this->app instanceof \Illuminate\Foundation\Application){
				//Laravel middleware loading
				$this->app[Kernel::class]->pushMiddleware(IncludeInSpecification::class);
			} else {
				//Lumen middleware loading
				$this->app->middleware([IncludeInSpecification::class]);
			}
		}
	}
}