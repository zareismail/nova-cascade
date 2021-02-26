<?php

namespace Zareismail\Fields; 

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo as Field;  
use Laravel\Nova\Nova; 

class BelongsTo extends Field
{  
	use InteractsWithResources;
	
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'cascade-belongs-to-field';  

    /**
     * Build an associatable query for the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  bool  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildAssociatableQuery(NovaRequest $request, $withTrashed = false)
    {
        if($resourceClass = Nova::resourceForKey($request->get('queryResource'))) { 
            $this->resourceClass = $resourceClass;
        }

        return parent::buildAssociatableQuery($request, $withTrashed)->tap(function($query) use ($request) {
            $query->when(intval($request->get('relatedResourceId')), function($query) {
                $query->whereHas('parent', function($query) {
                    $query->whereKey(request('relatedResourceId'));
                });
            });
        });
    }

    /**
     * Format the given associatable resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  mixed  $resource
     * @return array
     */
    public function formatAssociatableResource(NovaRequest $request, $resource)
    {
        $queryResource = Nova::resourceForKey($request->get('queryResource')) ?? $resource;

        return parent::formatAssociatableResource($request, new $queryResource($resource->resource));
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
    	return array_merge(parent::jsonSerialize(), [
    		'resources' => $this->availableResources()
    	]);
    }
}
