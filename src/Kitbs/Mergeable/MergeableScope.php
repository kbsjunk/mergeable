<?php namespace Kitbs\Mergeable;

use Illuminate\Database\Eloquent\ScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Kitbs\Mergeable\Mergeable;

class MergeableScope implements ScopeInterface {

    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = ['Merge', 'Unmerge', 'WithMerged', 'OnlyMerged'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function apply(Builder $builder)
    {
        $model = $builder->getModel();

        $builder->whereNull($model->getQualifiedMergedAtColumn());

        $this->extend($builder);
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function remove(Builder $builder)
    {
        $column = $builder->getModel()->getQualifiedMergedAtColumn();

        $query = $builder->getQuery();

        foreach ((array) $query->wheres as $key => $where)
        {
            // If the where clause is a soft delete date constraint, we will remove it from
            // the query and reset the keys on the wheres. This allows this developer to
            // include deleted model in a relationship result set that is lazy loaded.
            if ($this->isMergeableConstraint($where, $column))
            {
                unset($query->wheres[$key]);

                $query->wheres = array_values($query->wheres);
            }
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension)
        {
            $this->{"add{$extension}"}($builder);
        }

        // $builder->onDelete(function(Builder $builder)
        // {
        //     $column = $builder->getModel()->getMergedAtColumn();

        //     return $builder->update(array(
        //         $column => $builder->getModel()->freshTimestampString()
        //     ));
        // });
    }

    /**
     * Add the unmerge extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addUnmerge(Builder $builder)
    {
        $builder->macro('unmerge', function(Builder $builder)
        {
            $builder->withMerged();

            return $builder->update(array(
                $builder->getModel()->getMergedAtColumn() => null,
                $builder->getModel()->getMergedToColumn() => null
                ));
        });
    }

    /**
     * Add the merge extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addMerge(Builder $builder)
    {
        $builder->macro('merge', function(Builder $builder, $to, $newAttributes)
        {
            $builder->withMerged();
            // $builder->where($this->getKeyName(), $this->getKey());

            return $builder->update(array(
                $builder->getModel()->getMergedAtColumn() => $to->freshTimestamp(),
                $builder->getModel()->getMergedToColumn() => Mergeable::getMergeToId($to)
                ));
        });
    }

    /**
     * Add the with-Merged extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithMerged(Builder $builder)
    {
        $builder->macro('withMerged', function(Builder $builder)
        {
            $this->remove($builder);

            return $builder;
        });
    }

    /**
     * Add the only-Merged extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyMerged(Builder $builder)
    {
        $builder->macro('onlyMerged', function(Builder $builder)
        {
            $this->remove($builder);

            $builder->getQuery()->whereNotNull($builder->getModel()->getQualifiedMergedAtColumn());

            return $builder;
        });
    }

    /**
     * Determine if the given where clause is a soft delete constraint.
     *
     * @param  array   $where
     * @param  string  $column
     * @return bool
     */
    protected function isMergeableConstraint(array $where, $column)
    {
        return $where['type'] == 'Null' && $where['column'] == $column;
    }

}