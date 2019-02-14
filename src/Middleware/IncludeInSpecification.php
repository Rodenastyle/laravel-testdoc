<?php
/**
 * Created by PhpStorm.
 * User: sergio.rodenas
 * Date: 2019-01-30
 * Time: 08:13
 */

namespace Rodenastyle\TestDoc\Middleware;


use Closure;
use Illuminate\Http\Request;
use Rodenastyle\TestDoc\TestDocGenerator;

class IncludeInSpecification
{
	private $generator;

	public function __construct()
	{
		$this->generator = new TestDocGenerator;
	}

	public function handle(Request $request, Closure $next)
	{
		return $next($request);
	}

	public function terminate(Request $request, $response)
	{
		$this->generator->registerEndpoint($request, $response);
	}
}