<?php namespace Kitbs\Mergeable;

use Kitbs\Mergeable\MergeableBlueprint;
use Kitbs\Mergeable\MergeableException;
use Illuminate\Database\Eloquent\Model;

class Mergeable {

    protected $from;
    protected $to;
    protected $fieldmap = [];
    
    public function blueprint()
    {
        return new MergeableBlueprint;
    }

    public function begin(Model $from, Model $to)
    {

        if ($fromClass = get_class($from) !== $toClass = get_class($to)) {
            throw with(new MergeableNotMergeableException)->setModel($fromClass, $toClass);
        }

        $inputs = ['to', 'from'];

        foreach ($inputs as $input) {
            if (!method_exists($$input, 'bootMergeableTrait')) {
                $class = get_class($$input);
                throw with(new MergeableNotMergeableException)->setModel($class, $input);

            }
        }

        $this->from = $from;
        $this->to = $to;

        echo 'merging ' . $this->from->firstName . ' ' . $this->from->lastName . ' to ' . $this->to->firstName . ' ' . $this->to->lastName;

        return $this;
    }

    public function compare()
    {

        $this->fieldmap = [];

        $attributes = $this->to->getMergeableAttributes();
        foreach ($attributes as $key) {
            $this->fieldmap[$key] = [
            'from' => $this->from->getAttribute($key),
            'to'   => $this->to->getAttribute($key),
            'keep' => 'to',
            'conflict' => strcmp($this->from->getAttribute($key), $this->to->getAttribute($key)) <> 0
            ];
        }

        return $this;

    }

    public function keep(array $fieldmap)
    {   
        foreach ($fieldmap as $key => $keep) {
            $this->fieldmap[$key]['keep'] = $keep;
        }
    }

    public function keepFrom(array $fieldmap)
    {   
        foreach ($fieldmap as $key) {
            $this->fieldmap[$key]['keep'] = 'from';
        }
    }

    public function keepTo(array $fieldmap)
    {   
        foreach ($fieldmap as $key) {
            $this->fieldmap[$key]['keep'] = 'to';
        }
    }

    public function getNewAttributes()
    {

        $fields = [];

        foreach ($this->fieldmap as $key => $map) {
            $keep = $map['keep'];
            $fields[$key] = $this->$keep->getAttribute($key);
        }

        return $fields;
    }

    public function preview()
    {
        $preview = clone $this->to;

        $newAttributes = $this->getNewAttributes();

        foreach ($newAttributes as $key => $value) {
             $preview->setAttribute($key, $value);
        }

        return $preview;
    }

    public function commit()
    {
        return $this->from->merge($this->to, $this->getNewAttributes());
    }

    public static function getMergeToId($model)
    {

        if ($model instanceof Model) {
            return $model->getKey();
        }

        return $model;
    }

}