<?php
/**
 * Created by PhpStorm.
 * User: sergio.rodenas
 * Date: 2019-02-12
 * Time: 10:44
 */

namespace Rodenastyle\TestDoc;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TestDocGenerator
{
	private $specification = [];

	public function registerEndpoint(Request $request, Response $response)
	{
		$this->loadCurrentSpecification();
		$this->registerDefaults();

		$this->addOrUpdateEndpoint($request, $response);

		$this->saveSpecification();
	}

	private function loadCurrentSpecification()
	{
		$this->specification = json_decode(file_get_contents(storage_path('specification.json')), true ?: []);
	}

	private function registerDefaults()
	{
		$this->specification['openapi'] = '3.0.0';
		$this->specification['info'] = [
			'title' => 'API Specification', //CONFIG
			'version' => '1', //CONFIG
		];

		//CONFIG
		$this->specification['components']['securitySchemes']['bearerAuth'] = [
			'type' => 'http',
			'scheme' => 'bearer',
			'bearerFormat' => 'JWT'
		];
	}

	private function addOrUpdateEndpoint(Request $request, Response $response): void
	{
		$uri = parse_url($request->getRequestUri());

		if($this->endpointExists($uri['path'], strtolower($request->getMethod()))){
			//MERGE
		}

		$this->specification['paths'][$uri['path']][strtolower($request->getMethod())] = [
			'responses' => [
				$response->getStatusCode() => [
					'description' => ''
				]
			],
			'security' => $this->getOperationSecurity()
		];
	}

	private function endpointExists(String $uri, String $method) :bool {
		return isset($this->specification['paths'][$uri][$method]);
	}

	private function saveSpecification(){
		file_put_contents(storage_path('specification.json'), json_encode($this->specification));
	}


	private function getOperationSecurity(){
		return app('auth')->guest() ? [] : ['bearerAuth' => []];
	}

	private function getRequestParameters(Request $request){
		if($request->getMethod() === 'GET') return [];

		$uri = parse_url($request->getRequestUri());

		return collect($uri['query'])->map(function($query, $value){
			return [
				'name' => $query,
				'in' => 'query'
			];
		});
	}

	private function getRequestInput(Request $request){
		if( ! $request->getMethod() === 'GET') return [];

		return $input['content']['application/json']['schema']['example'] =
			collect($request->json()->all())->toJson();
	}

	/*
		 * $specification['paths'][$uri['path']][strtolower($request->getMethod())] = [
			'headers' => ['Accept' => "application/json"],
			'parameters' => $this->getRequestParameters($request),
			'requestBody' => $this->getRequestInput($request),
			'responses' => [
				$response->getStatusCode() => [
					'content' => [
						'application/json' => [
							'schema' => [
								'example' => $response->getContent()
							]
						]
					]
				]
			],
			'security' => $this->getOperationSecurity()
		];
		 */
}