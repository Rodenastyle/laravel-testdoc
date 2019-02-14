<?php
/**
 * Created by PhpStorm.
 * User: sergio.rodenas
 * Date: 2019-02-12
 * Time: 10:44
 */

namespace Rodenastyle\TestDoc;

use Illuminate\Http\Request;

class TestDocGenerator
{
	private $specification = [];

	public function registerEndpoint(Request $request, $response) : void
	{
		$this->loadCurrentSpecification();
		$this->registerDefaults();

		$this->addOrUpdateEndpoint($request, $response);
	}

	private function loadCurrentSpecification() : void
	{
		$this->specification = json_decode(
			@file_get_contents(base_path('public/docs/specification.json')) ?: "",
			true
		);
	}

	private function registerDefaults() : void
	{
		$this->specification['openapi'] = '3.0.0';
		$this->specification['components']['securitySchemes']['bearerAuth'] = [
			'type' => 'http',
			'scheme' => 'bearer',
			'bearerFormat' => 'JWT'
		];
	}

	private function addOrUpdateEndpoint(Request $request, $response): void
	{
		if($this->endpointExistsOnSpecification($request)){
			$this->updateSpecificationEndpoint($request, $response);
		} else {
			$this->addSpecificationEndpoint($request, $response);
		}
	}

	private function endpointExistsOnSpecification(Request $request):bool{
		return isset($this->specification['paths']
			[$this->getInternalRouteDeclaredFormat($request)]
			[$this->getRouteMethodForSpecification($request)]);
	}

	private function addSpecificationEndpoint(Request $request, $response){
		//TODO: Config
		if(str_contains($this->getInternalRouteDeclaredFormat($request), 'internal')) return;

		$this->specification['paths']
		[$this->getInternalRouteDeclaredFormat($request)]
		[$this->getRouteMethodForSpecification($request)] = [
			'responses' => [
				'200' => [
					'description' => ''
				]
			],
			'security' => $this->getOperationSecurity($request),
			'tags' => [
				ucfirst($this->getRouteSpecificationGroup($request))
			]
		];

		$this->updateSpecificationEndpoint($request, $response);
	}

	private function updateSpecificationEndpoint(Request $request, $response){
		$this->mergeResponseToEndpoint($request, $response);

		if(in_array($this->getRouteMethodForSpecification($request), ['post', 'patch', 'delete'])){
			$this->mergeRequestBodiesToEndpoint($request, $response);
		} else {
			$this->mergeParamsToEndpoint($request, $response);
		}

		$this->saveSpecification();
	}

	private function mergeResponseToEndpoint(Request $request, $response){
		$this->specification['paths']
		[$this->getInternalRouteDeclaredFormat($request)]
		[$this->getRouteMethodForSpecification($request)]
		['responses']
		[$response->getStatusCode()] = [
			'description' => '',
			'content' => [
				'application/json' => [
					'schema' => [
						'type' => 'string',
						'example' => $response->getContent()
					]
				]
			]
		];
	}

	private function mergeParamsToEndpoint(Request $request, $response){
		collect($request->query->all())->keys()->each(function($param)use($request){
			$this->specification['paths']
			[$this->getInternalRouteDeclaredFormat($request)]
			[$this->getRouteMethodForSpecification($request)]
			['parameters'][] = [
				'in' => 'query',
				'name' => $param,
				'schema' => ['type' => 'string']
			];
		});
	}

	private function mergeRequestBodiesToEndpoint(Request $request, $response){
		if( ! isset($this->specification['paths']
			[$this->getInternalRouteDeclaredFormat($request)]
			[$this->getRouteMethodForSpecification($request)]
			['requestBody'])){

			$this->specification['paths']
			[$this->getInternalRouteDeclaredFormat($request)]
			[$this->getRouteMethodForSpecification($request)]
			['requestBody']['content']['application/json'] = [
				'schema' => [
					'type' => 'object',
					'properties' => []
				],
				'required' => false
			];
		}

		collect($request->json()->all())->keys()->each(function($bodyKey)use($request){
			$this->specification['paths']
			[$this->getInternalRouteDeclaredFormat($request)]
			[$this->getRouteMethodForSpecification($request)]
			['requestBody']['content']['application/json']['schema']['properties'][$bodyKey] = [
				'type' => 'string'
			];
		});
	}

	private function getRouteMethodForSpecification(Request $request){
		return strtolower($request->getMethod());
	}

	private function getInternalRoute(Request $request){
		if( ! isset($request->route()[1]['as'])) return null;
		return route($request->route()[1]['as']);
	}

	private function getInternalRouteDeclaredFormat(Request $request){
		$uri = parse_url($this->getInternalRoute($request) ?: $request->getRequestUri());
		return $uri['path'];
	}

	private function getRouteSpecificationGroup(Request $request){
		return $this->getRouteSpecificationGroupByRouteName($request) ?:
			$this->getRouteSpecificationGroupByRequestUri($request);
	}

	private function getRouteSpecificationGroupByRouteName(Request $request){
		if( ! isset($request->route()[1]['as'])) return null;

		$internalRoute = $request->route()[1]['as'];
		$tag = explode('.', $internalRoute);

		return $tag[count($tag) - 2];
	}

	private function getRouteSpecificationGroupByRequestUri(Request $request){
		return "other";
	}

	private function saveSpecification() : void{
		file_put_contents(base_path('public/docs/specification.json'), json_encode($this->specification));
	}

	private function getOperationSecurity(Request $request) : array{
		return (
			isset($request->route()[1]['middleware']) &&
			in_array('auth', $request->route()[1]['middleware'], true)
		) ? ['bearerAuth' => []] : [];
	}
}