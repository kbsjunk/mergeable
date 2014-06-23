<?php namespace Kitbs\Mergeable;

use Exception;

class MergeableException extends \RuntimeException {

	/**
	 * Name of the affected Eloquent model.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Set the affected Eloquent model.
	 *
	 * @param  string   $model
	 * @return ModelNotFoundException
	 */
	public function setModel($model, $model2 = null)
	{
		$this->model = $model;

		$this->message = "An error was encountered while attempting to merge the model [{$model}].";

		return $this;
	}

	/**
	 * Get the affected Eloquent model.
	 *
	 * @return string
	 */
	public function getModel()
	{
		return $this->model;
	}

}