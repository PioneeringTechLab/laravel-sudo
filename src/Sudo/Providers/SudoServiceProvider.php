<?php

namespace CSUNMetaLab\Sudo\Providers;

use Illuminate\Support\ServiceProvider;

class SudoServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		// publish configuration
		$this->publishes([
        	__DIR__.'/../config/sudo.php' => config_path('sudo.php'),
    	], 'config');

    	// publish language files
    	$this->publishes([
    		__DIR__.'/../lang' => base_path('resources/lang/en')
    	], 'lang');

    	// publish views and make them available as well
    	$this->loadViewsFrom(__DIR__.'/../views', 'sudo');
	    $this->publishes([
	        __DIR__.'/../views' => base_path('resources/views/vendor/sudo'),
	    ], 'views');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array();
	}

}