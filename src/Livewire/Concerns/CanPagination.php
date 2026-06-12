<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\WithPagination;

trait CanPagination
{
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
     * 已加载的最后一页页码。
     * 值为 0 时表示需要新鲜开始（不合并旧数据，直接替换）。
     */
    public int $loadedPage = 0;

    /**
     * 组装好的分页信息
     */
    public array $pageInfo = [];

    /**
     * 上次加载时的查询指纹。
     * 与 $loadedPage 共同构成缓存 key。指纹变化时自动重置分页。
     */
    protected ?string $loadedFingerprint = null;

    /**
     * 分页链接
     *
     * @var string
     */
    protected $links = null;

    public function pageType(string $pageType)
    {
        $this->pageType = $pageType;

        return $this;
    }

    public function getPageType(): string
    {
        return $this->pageType;
    }

    /**
     * 重置分页缓存，下次 render 时强制重新查询并回到第 1 页。
     * 适用场景：数据新增/删除/修改后调用。
     */
    public function resetPagination(): void
    {
        $this->loadedPage = 0;
        $this->resetPage($this->pageName);
    }

    /**
     * 执行分页查询。
     *
     * @param  Builder  $builder  Eloquent 查询构造器
     * @param  string  $fingerprint  查询指纹，用于标识当前查询条件。
     *                               指纹变化时自动重置分页（回到第 1 页、清空已合并的数据）。
     *                               应包含所有影响查询结果的属性（搜索词、筛选条件等）。
     */
    public function withPagination(Builder $builder, string $fingerprint)
    {
        // 指纹变化 → 全面重置（清缓存、回到第 1 页）
        if ($fingerprint !== $this->loadedFingerprint) {
            $this->resetPagination();
            $this->loadedFingerprint = $fingerprint;
        }

        $requestedPage = $this->getPage($this->pageName);

        // 缓存命中：同页 + 同指纹 + 已加载过 → 跳过查询
        if ($requestedPage === $this->loadedPage && $this->loadedPage > 0) {
            return $this->getCurrents();
        }

        // 是否为新鲜开始（需要替换而非合并旧数据）
        $isFreshStart = ($this->loadedPage === 0);

        if ($this->getPageType() == 'paginator') {
            /** @var LengthAwarePaginator $current */
            $current = $builder->paginate($this->perPage, pageName: $this->pageName);
            $collections = $current->getCollection();        // 获取 collection 格式的数据
        } else {
            /** @var Paginator $current */
            $current = $builder->simplePaginate($this->perPage, pageName: $this->pageName);
            $collections = $isFreshStart
                ? collect($current->items())
                : $this->getCurrents()->merge($current->items());
        }

        // 记录当前页码
        $this->loadedPage = $requestedPage;

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

        if ($this->getPageType() == 'paginator') {
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
