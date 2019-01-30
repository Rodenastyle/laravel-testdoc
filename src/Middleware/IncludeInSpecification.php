<?php
/**
 * Created by PhpStorm.
 * User: sergio.rodenas
 * Date: 2019-01-30
 * Time: 08:13
 */

namespace Rodenastyle\TestDoc\Middleware;


use Closure;

class IncludeInSpecification
{
	public function handle($request, Closure $next)
	{
		return $next($request);
	}

	public function terminate($request, $response)
	{
		dd([$request, $response]);
	}
}