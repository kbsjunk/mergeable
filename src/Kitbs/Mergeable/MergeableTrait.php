<?php namespace Kitbs\Mergeable;

use Kitbs\Mergeable\MergeableScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassAssignmentException;

trait MergeableTrait {

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootMergeableTrait()
    {
        static::addGlobalScope(new MergeableScope);
    }



    public function mergedTo()
    {
        return $this->belongsTo(get_class($this), 'merged_to');
    }

    public function mergedFrom()
    {
        return $this->hasMany(get_class($this), 'merged_to')->withMerged();
    }

    public function getMergeableAttributes()
    {
        if (!isset($this->mergeableAttributes)) {

            $ignore = array_merge($this->getDates(), [
                $this->getMergedAtColumn(),
                $this->getMergedToColumn(),
                $this->getKeyName(),
                ]);

            if (method_exists($this, 'bootSoftDeletingTrait')) {
                $ignore[] = $this->getDeletedAtColumn();
            }

            $attributes = array_keys(array_except($this->attributesToArray(), $ignore));
        }
        else {
            $attributes = $this->mergeableAttributes;
        }

        return $attributes;
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    // public function getDates()
    // {
    //     $defaults = parent::getDates();

    //     return array_merge($this->dates, $defaults, [$this->getMergedAtColumn()]);
    // }

    /**
     * Perform the actual merge query on this model instance.
     *
     * @return void
     */
    protected function merge($to, $attributes = [])
    {

        if ($this->fireModelEvent('merging') === false)
        {
            return false;
        }

        $query = $this->newQuery()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getMergedAtColumn()} = $time = $this->freshTimestamp();
        $this->{$this->getMergedToColumn()} = Mergeable::getMergeToId($to);

        // $query->update(
        //     array(
        //         $this->getMergedAtColumn() => $this->fromDateTime($time),
        //         $this->getMergedToColumn() => $to
        //         )
        //     );

        try {
            $to->fill($attributes);
        }
        catch (MassAssignmentException $e) {

        }

        $to->save();

        $result = $this->save();

        $this->fireModelEvent('merged', false);

        return $result;
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function unmerge()
    {
        // If the unmerging event does not return false, we will proceed with this
        // unmerge operation. Otherwise, we bail out so the developer will stop
        // the unmerge totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('unmerging') === false)
        {
            return false;
        }

        $this->{$this->getMergedAtColumn()} = null;
        $this->{$this->getMergedToColumn()} = null;

        // Once we have saved the model, we will fire the "unmerged" event so this
        // developer will do anything they need to after a unmerge operation is
        // totally finished. Then we will return the result of the save call.
        $result = $this->save();

        $this->fireModelEvent('unmerged', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function isMerged()
    {
        return ! is_null($this->{$this->getMergedAtColumn()});
    }

    public function getMergedAttribute()
    {
        return $this->isMerged();
    }

    /**
     * Get a new query builder that includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function withMerged()
    {
        return with(new static)->newQueryWithoutScope(new MergeableScope);
    }

    /**
     * Get a new query builder that only includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function onlyMerged()
    {
        $instance = new static;

        $column = $instance->getQualifiedMergedAtColumn();

        return $instance->newQueryWithoutScope(new MergeableScope)->whereNotNull($column);
    }

    /**
     * Register a merging model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function merging($callback)
    {
        static::registerModelEvent('merging', $callback);
    }

    /**
     * Register a merged model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function merged($callback)
    {
        static::registerModelEvent('merged', $callback);
    }

    /**
     * Register a unmerging model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unmerging($callback)
    {
        static::registerModelEvent('unmerging', $callback);
    }

    /**
     * Register a unmerged model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unmerged($callback)
    {
        static::registerModelEvent('unmerged', $callback);
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getMergedAtColumn()
    {
        return defined('static::MERGED_AT') ? static::MERGED_AT : 'merged_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedMergedAtColumn()
    {
        return $this->getTable().'.'.$this->getMergedAtColumn();
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getMergedToColumn()
    {
        return defined('static::MERGED_TO') ? static::MERGED_TO : 'merged_to';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedMergedToColumn()
    {
        return $this->getTable().'.'.$this->getMergedToColumn();
    }

}