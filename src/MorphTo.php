<?php

namespace Zareismail\Fields; 

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\MorphTo as Field;  
use Laravel\Nova\Nova; 

class MorphTo extends Field
{  
	use InteractsWithResources;
	
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'cascade-morph-to-field';   

    /**
     * Build an morphable query for the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $relatedResource
     * @param  bool  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildMorphableQuery(NovaRequest $request, $relatedResource, $withTrashed = false)
    {
        $relatedResource = Nova::resourceForKey($request->get('queryResource')) ?? $relatedResource;

        return parent::buildMorphableQuery($request, $relatedResource, $withTrashed)->tap(function($query) {
            $query->when(intval(request('relatedResourceId')), function($query) {
                $query->whereHas('parent', function($query) {
                    $query->whereKey(request('relatedResourceId'));
                });
            });
        });
    }

    /**
     * Retruns array of related resources.
     * 
     * @return array
     */
    public function resources()
    {
        return collect($this->morphToTypes)->keyBy('value')->map(function($data) { 
            $resources = $this->relatedResources($this->newResource($data['type']));

            return collect($resources)->map([$this, 'resourceInformation']);
        })->toArray(); 
    }

    /**
     * Return id of the selected resource.
     * 
     * @return integer
     */
    public function getResourceId()
    {
        return $this->morphToId;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'resources' => $this->resources()
        ]);
    }
 
}
