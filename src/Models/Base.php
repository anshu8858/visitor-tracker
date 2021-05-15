<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Symfony\Component\Console\Application;

class Base extends Eloquent
{
    protected $hidden = ['config'];
    private $config;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection($this->getConfig()->get('connection'));
    }

    public function getConfig()
    {
        if ($this->config) {
            return $this->config;
        } elseif (isset($GLOBALS['app']) && $GLOBALS['app'] instanceof Application) {
            return $GLOBALS['app']['tracker.config'];
        }

        return app()->make('tracker.config');
    }

    public function save(array $options = [])
    {
        parent::save($options);
        app('tracker.cache')->makeKeyAndPut($this, $this->getKeyName());
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function scopePeriod($query, $minutes, $alias = '')
    {
        $alias = $alias ? "$alias." : '';

        return $query
            ->where($alias.'updated_at', '>=', $minutes->getStart() ? $minutes->getStart() : 1)
            ->where($alias.'updated_at', '<=', $minutes->getEnd() ? $minutes->getEnd() : 1);
    }



    public function where($key, $operation, $value = null)
    {
        $this->builder = $this->builder ?: $this->newQuery();
        $this->builder = $this->builder->where($key, $operation, $value = null);

        return $this;
    }

    public function first()
    {
        $this->result = $this->builder->first();

        return $this->result ? $this : null;
    }

    public function find($id)
    {
        list($model, $cacheKey) = $this->cache->findCached($id, null, $this->className);

        if (! $model) {
            $model = $this->newQuery();

            if ($this->relations) {
                $model->with($this->relations);
            }

            if ($model = $model->find($id)) {
                $this->cache->cachePut($cacheKey, $model);
            }
        }

        $this->model = $model;
        $this->result = $model;

        return $model;
    }

    public function create($attributes, $model = null)
    {
        $model = $model && ! $model->exists() ? $model : $this->newModel($model);

        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, $model->getFillable())) {
                $model->{$attribute} = $value;
            }
        }

        $model->save();

        return $model;
    }

    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * @param string $attribute
     */
    public function getAttribute($attribute)
    {
        return $this->result ? $this->result->{$attribute} : null;
    }

    public function setAttribute($attribute, $value)
    {
        return $this->result->{$attribute} = $value;
    }

    public function save()
    {
        return $this->result->save();
    }

    /**
     * @param string[] $keys
     */
    public function findOrCreate($attributes, $keys = null, &$created = false, $otherModel = null)
    {
        list($model, $cacheKey) = $this->cache->findCached($attributes, $keys, $this->className);

        if (! $model) {
            $model = $this->newQuery($otherModel);

            $keys = $keys ?: array_keys($attributes);

            foreach ($keys as $key) {
                $model = $model->where($key, $attributes[$key]);
            }

            if (! $model = $model->first()) {
                $model = $this->create($attributes, $otherModel);

                $created = true;
            }

            $this->cache->cachePut($cacheKey, $model);
        }

        $this->model = $model;

        return $model->id;
    }


    public function newQuery($model = null)
    {
        $className = $this->className;

        if ($model) {
            $className = get_class($model);
        }

        $this->builder = new $className();

        if ($this->connection) {
            $this->builder = $this->builder->on($this->connection);
        }

        return $this->builder->newQuery();
    }
}
