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
use Illuminate\Http\Response;
use OpenApi\Analysis;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\RequestBody;
use OpenApi\Processors\{MergeIntoOpenApi,MergeJsonContent,BuildPaths};

class IncludeInSpecification
{
	public function handle(Request $request, Closure $next)
	{
		return $next($request);
	}

	public function terminate(Request $request, Response $response)
	{
		$operationClass = '\\OpenApi\\Annotations\\'.ucfirst(strtolower($request->getMethod()));
		$specification = new $operationClass([
			'path' => $request->getRequestUri(),
			 'parameters' => [
				//new Parameter(['in' => 'query'])
			],
			//'requestBody' => new RequestBody([]),
		]);

		$specification->responses = [
			(function()use($response){
				return new \OpenApi\Annotations\Response([
					'response' => $response->getStatusCode(),
					//'content' => $response->getContent()
				]);
			})()
		];

		$analysis = new Analysis([$specification]);
		$analysis->process([
			new MergeIntoOpenApi(),
			new BuildPaths(),
			new MergeJsonContent(),
		]);

		$oldRules = json_decode(file_get_contents(storage_path('specification.json')), true);
		$generatedRules = json_decode($analysis->openapi->toJson(), true);

		file_put_contents(storage_path('specification.json'), json_encode(array_merge_recursive($generatedRules, $oldRules)));
	}
}