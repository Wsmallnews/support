<?php

namespace Wsmallnews\Support\Traits\Components;

use Illuminate\Database\Eloquent\Builder;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

trait CanPagination
{
    use WithoutUrlPagination;
    use WithPagination;

    /**
     * 分页类型
     */
    public string $pageType = 'scroll';      // scroll:滚动加载更多,paginator:分页器,manual:手动

    /**
     * 分页字段名
     */
    public string $pageName = 'page';

    /**
     * 每页条数
     */
    public int $perPage = 10;

    /**
     * 组装好的分页信息
     */
    public array $pageInfo = [];

    /**
     * 分页链接
     *
     * @var string
     */
    protected $links = null;

    public function withPagination(Builder $builder)
    {
        if ($this->pageType == 'paginator') {
            $current = $builder->paginate($this->perPage, pageName: $this->pageName);
            $collections = $current->getCollection();        // 获取 collection 格式的数据
        } else {
            $current = $builder->simplePaginate($this->perPage, pageName: $this->pageName);
            $collections = $this->products->merge($current->items());
        }

        // 分页链接
        $this->links = $current->links();

        // 分页信息
        $this->pageInfo = [
            'count' => $current->count(),                                       // 当前查询最终的结果数量
            'per_page' => $current->perPage(),                                  // 每页条件
            'current_page' => $current->currentPage(),                          // 当前页码
            'load_status' => 'loading',                                         // 默认加载中
            'is_last_page' => 0,                                                // 默认不是最有一页
        ];

        if ($this->pageType == 'paginator') {
            $this->pageInfo['total'] = $current->total();                  // 满足条件总条数
            $this->pageInfo['last_page'] = $current->lastPage();           // 最后的页码

            if ($this->pageInfo['current_page'] >= $this->pageInfo['last_page']) {
                $this->pageInfo['is_last_page'] = 1;
                $this->pageInfo['load_status'] = 'nomore';

                if ($this->pageInfo['current_page'] == 1 && $this->pageInfo['count'] <= 0) {
                    $this->pageInfo['load_status'] = 'empty';
                }
            }
        } else {
            if ($this->pageInfo['count'] < $this->pageInfo['per_page']) {
                $this->pageInfo['is_last_page'] = 1;
                $this->pageInfo['load_status'] = 'nomore';

                if ($this->pageInfo['current_page'] == 1 && $this->pageInfo['count'] <= 0) {
                    $this->pageInfo['load_status'] = 'empty';
                }
            }
        }

        return $collections;
    }
}
