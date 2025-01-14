<?php

namespace Wsmallnews\Support\Features;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wsmallnews\Support\Exceptions\SupportException;

class Tree
{
    protected $model = null;

    protected $is_cache = false;

    protected $cache_store = null;

    protected $cache_ttl = 0;       // 单位 秒

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function cache($store = null, $ttl = 0)
    {
        $this->is_cache = true;
        $this->cache_store = $store;
        $this->cache_ttl = $ttl;

        return $this;
    }

    /**
     * 获取递归树
     *
     * @param  int|string|Collection  $items  可以传入某个id 查询这个id的下级树，也可以传一个查询列表结果，将获取这个列表的所有的下级 树
     * @param  \Closure  $resultCb  用来处理每一次查询的结果 比如要取出树中的所有 name
     */
    public function getTree(int | string | Collection $items = 0, ?\Closure $resultCb = null): Collection
    {
        if ($items instanceof Collection) {
            $key_sign = implode(',', $items->pluck($this->getModel()->getKeyName())->toArray());
        } else {
            $key_sign = $items;
        }
        $key = $this->getCacheKey($key_sign);

        return through_cache($key, function () use ($items, $resultCb) {
            if (! $items instanceof Collection) {
                $items = $this->getQuery()->where('parent_id', $items)->get();
                $resultCb && $items = $resultCb($items);
            }

            foreach ($items as $key => &$item) {
                $child = $this->getTree($item->id, $resultCb);
                if ($child) {
                    $item->children = $child;
                }
            }

            return $items;
        }, store: $this->cache_store, is_force: ! $this->is_cache, ttl: ($this->cache_ttl + mt_rand(0, 100)));
    }

    /**
     * 获取特定父级的递归树(包含自身)
     *
     * @param  \Closure  $resultCb  用来处理每一次查询的结果 比如要取出树中的所有 name
     */
    public function getChildren(int | string | Model $self, ?\Closure $resultCb = null): Collection
    {
        $key = $this->getCacheKey($self instanceof Model ? $self->{$this->getModel()->getKeyName()} : $self);

        return through_cache($key, function () use ($self, $resultCb) {
            if (! $self instanceof Model) {
                $self = $this->getQuery()->where('id', $self)->firstOrFail();
            }

            $items = $this->getQuery()->where('parent_id', $self->id)->get();
            $resultCb && $items = $resultCb($items);

            $items = $this->getTree($items, $resultCb);

            $self->children = $items;

            return $self;
        }, store: $this->cache_store, is_force: ! $this->is_cache, ttl: ($this->cache_ttl + mt_rand(0, 100)));
    }

    /**
     * 递归获取子对象 id
     *
     * @param  int|string|Model  $self  自己
     * @param  string  $field  要查询的字段
     * @param  bool  $has_self  是否包含自己
     */
    public function getChildFields(int | string | Model $self, $field = 'id', $has_self = true): array
    {
        $key_sign = $self instanceof Model ? $self->{$this->getModel()->getKeyName()} : $self . '-' . $field . '-' . $has_self;

        return through_cache($this->getCacheKey($key_sign), function () use ($self, $field, $has_self) {
            if (! $self instanceof Model) {
                $self = $this->getQuery()->find($self);
                if (! $self) {
                    return [];
                }
            }

            return $this->recursionGetChildFields($self, $field, $has_self);
        }, store: $this->cache_store, is_force: ! $this->is_cache, ttl: ($this->cache_ttl + mt_rand(0, 100)));
    }

    /**
     * 递归获取子对象 id
     *
     * @param  mixed  $item  要查询的 item
     * @param  string  $field  要查询的 字段
     * @param  bool  $has_self  是否包含自己
     */
    private function recursionGetChildFields($item, $field = 'id', bool $has_self = true): array
    {
        $ids = $item ? [$item->$field] : [];
        $childrenIds = $this->getQuery()->where('parent_id', $item->$field)->pluck($field);

        if ($childrenIds) {
            foreach ($childrenIds as $v) {
                $grandsonIds = $this->recursionGetChildFields($v, $field, $has_self);
                $ids = array_merge($ids, $grandsonIds);
            }
        }

        return $ids;
    }

    /**
     * 缓存递归获取当前对象的上级 指定字段
     *
     * @param  string  $field  要获取的字段
     * @param  bool  $has_self  是否包含自己
     * @return array
     */
    public function getParentFields(int | string | Model $self, $field = 'id', $has_self = true)
    {
        $key_sign = $self instanceof Model ? $self->{$this->getModel()->getKeyName()} : $self . '-' . $field . '-' . $has_self;

        return through_cache($this->getCacheKey($key_sign), function () use ($self, $field, $has_self) {
            if (! $self instanceof Model) {
                $self = $this->getQuery()->find($self);
                if (! $self) {
                    return [];
                }
            }

            $objectIds = array_reverse($this->recursionGetParentFields($self, $field));
            if ($has_self) {
                $objectIds[] = $self[$field];     // 加上自己
            }

            return $objectIds;
        }, store: $this->cache_store, is_force: ! $this->is_cache, ttl: ($this->cache_ttl + mt_rand(0, 100)));
    }

    /**
     * 递归获取所有上级 id
     *
     * @param  mixed  $item  要查询的 item
     * @param  string  $field  要获取的字段
     * @param  array  $ids  递归的结果
     * @return array
     */
    private function recursionGetParentFields($item, $field = 'id', $ids = [])
    {
        if ($item->parent_id) {
            $parent = $this->getQuery()->find($item->parent_id);
            if ($parent) {
                $ids[] = $parent->$field;

                return $this->recursionGetParentFields($parent, $field, $ids);
            }
        }

        return $ids;
    }

    /**
     * 检测 id 是不是自己的下级
     *
     * @param  int|string|Model  $child  要检测的对象
     * @param  int|string|Model  $self  自己
     * @param  string  $field  字段
     * @param  bool  $exception  是否抛出异常
     */
    public function isChild(int | string | Model $child, int | string | Model $self, string $field = 'id', bool $exception = true): bool
    {
        $child instanceof Model && $child = $child->$field;

        $childFields = $this->getChildFields($self, $field);
        if (in_array($child, $childFields)) {
            $exception && throw new SupportException('当前上级不能是自己的下级');

            return false;
        }

        return true;
    }

    /**
     * 检测id 是否可以设置为自己的上级
     *
     * @param  int|string|Model  $parent_id  要设置的上级id
     * @param  int|string|Model  $id  自己的id
     */
    public function checkParent(int | string | Model $parent, int | string | Model $self, string $field = 'id', bool $exception = true): bool
    {
        $parent instanceof Model && $parent = $parent->$field;
        $self instanceof Model ? $self_field = $self->$field : $self_field = $self;

        if ($parent == $self_field) {
            $exception && throw new SupportException('当前上级不能是自己');

            return false;
        }
        $childIds = $this->getChildFields($self, $field);
        if (in_array($parent, $childIds)) {
            $exception && throw new SupportException('当前上级不能是自己的下级');

            return false;
        }

        return true;
    }

    /**
     * 获取当前对象所属级别
     *
     * @param [type] $object
     * @return void
     */
    public function getLevel($object)
    {
        $parentIds = $this->getParentFields($object, 'id');

        return count($parentIds);
    }

    /**
     * 获取缓存key
     *
     * @param  string  $key
     * @return string
     */
    private function getCacheKey($key)
    {
        return 'tree_cache:' . $this->getModelName() . ':' . $key;
    }

    /**
     * 获取当前 查询
     *
     * @return think\model|think\db\Query
     */
    private function getQuery()
    {
        if ($this->model instanceof \Closure) {
            return ($this->model)();
        }

        return $this->model;
    }

    private function getModel()
    {
        $model = $this->getQuery();
        if (! $model instanceof Model) {
            $model = $model->getModel();
        }

        return $model;
    }

    /**
     * 获取modelname
     */
    private function getModelName(): string
    {
        return get_class($this->getModel());
    }
}
