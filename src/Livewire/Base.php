<?php

namespace Wsmallnews\Support\Livewire;

use Closure;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Support\Exceptions\Halt;
use Livewire\Component;
use Throwable;

class Base extends Component
{
    use CanUseDatabaseTransactions;

    /**
     * 在数据库事务中执行给定的回调函数。
     *
     * 该方法根据 `$hasDatabaseTransactions` 属性决定是否启用事务。
     * 事务模式参考 Filament 的 CreateRecord 实现：
     * - 如果抛出 Halt 异常，根据异常的 shouldRollbackDatabaseTransaction() 决定回滚或提交
     * - 如果抛出其他异常，回滚事务并重新抛出
     * - 如果没有异常，提交事务
     *
     * @param  Closure  $callback  需要在事务中执行的匿名函数
     * @return mixed  回调函数的返回值
     *
     * @throws Throwable  当事务执行失败时抛出异常并自动回滚
     */
    public function transaction(Closure $callback): mixed
    {
        if (! $this->hasDatabaseTransactions()) {
            return $callback();
        }

        try {
            $this->beginDatabaseTransaction();

            $result = $callback();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return null;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        return $result;
    }

    /**
     * 抛出 Halt 异常以停止执行并可选地回滚事务。
     *
     * @param  bool  $shouldRollbackDatabaseTransaction  是否回滚数据库事务
     *
     * @throws Halt
     */
    protected function halt(bool $shouldRollbackDatabaseTransaction = false): void
    {
        throw (new Halt)->rollBackDatabaseTransaction($shouldRollbackDatabaseTransaction);
    }
}
