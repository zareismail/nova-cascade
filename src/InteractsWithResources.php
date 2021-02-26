<?php

namespace Zareismail\Fields; 
  
use Laravel\Nova\Nova;

trait InteractsWithResources 
{  
	/**
     * Returns cascade resources.
     * 
     * @return \Laravel\Nova\ResourceCollection
     */
    protected function relatedResources($resource)
    {  
    	return array_merge($this->parentResources($resource->resource), [$resource]);
    } 

    /**
     * Returns resource parents of the given model.
     * 
     * @param  Illuminate\Database\Eloquent\Model $model 
     * @return array        
     */
    public function parentResources($model)
    {
    	if($model instanceof Contracts\Cascade) {
    		$relatedQuery = $model->parent();

    		return (array) $this->relatedResources(Nova::newResourceFromModel(
    			$model->exists ? $relatedQuery->firstOrNew() : $relatedQuery->getModel()
    		)); 
    	}

    	return [];
    } 

    /**
     * Get the resource instance.
     * 
     * @return \Laravel\Nova\Resource
     */
    public function newResource(string $resource)
    {   
        $model = with(forward_static_call([$resource, 'newModel']), function($model) { 
            return $this->getResourceId() ? $model->findOrNew($this->getResourceId()) : $model;
        });

        return new $resource($model);
    }

    /**
     * Return id of the selected resource.
     * 
     * @return integer
     */
    public function getResourceId()
    {
        return $this->belongsToId;
    }

    /**
     * Retruns array of related resources.
     * 
     * @return array
     */
    public function resources()
    {
        return collect($this->relatedResources($this->newResource($this->resourceClass)))->map([
            $this, 'resourceInformation'
        ])->toArray(); 
    }

    /**
     * Return informations of the given resource.
     * 
     * @param  \Laravel\Nova\Resource $resource 
     * @return array           
     */
    public function resourceInformation($resource)
    {
        return [
            'key'   => $resource::uriKey(),
            'value' => $resource->id,
            'label' => $resource::singularLabel(),
        ];
    }

    /**
     * Returns available  resources for serialization.
     * 
     * @return array
     */
    public function availableResources()
    {
        return request()->input('editing') ? $this->resources() : [];
    }
}
