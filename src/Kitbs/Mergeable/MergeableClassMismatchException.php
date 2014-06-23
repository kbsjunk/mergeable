<?php namespace Kitbs\Mergeable;

class MergeableClassMismatchException extends MergeableException {

	/**
	 * Set the affected Eloquent model.
	 *
	 * @param  string   $model
	 * @return ModelNotFoundException
	 */
	public function setModel($model, $model2 = null)
	{
		$this->model = $model;

		$this->message = "The \$from model [{$model}] \$to model [{$model2}] and are not of the same class.";
		
		return $this;
	}

}