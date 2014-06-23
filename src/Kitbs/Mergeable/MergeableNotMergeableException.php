<?php namespace Kitbs\Mergeable;

class MergeableNotMergeableException extends MergeableException {

	/**
	 * Set the affected Eloquent model.
	 *
	 * @param  string   $model
	 * @return ModelNotFoundException
	 */
	public function setModel($model, $model2 = null)
	{
		$this->model = $model;

		$this->message = "The \${$model2} model [{$model}] does not use the MergeableTrait trait.";

		return $this;
	}

}