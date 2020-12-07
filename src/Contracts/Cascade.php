<?php 

namespace Zareismail\Fields\Contracts;


interface Cascade
{	
	/**
	 * Query the parent resource.
	 * 
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function parent(); 	
}