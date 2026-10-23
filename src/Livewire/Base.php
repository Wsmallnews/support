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
    * Run the callback in a database transaction when $hasDatabaseTransactions is on. A Halt
    * exception rolls back or commits as it asks; any other exception rolls back and rethrows.
    *
    * @param  Closure  $callback  Work to run inside the transaction
    * @return mixed The callback result
    * @throws Throwable When the transaction fails, after rolling back
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
    * Throw a Halt exception to stop execution, optionally rolling back the transaction.
    *
    * @param  bool  $shouldRollbackDatabaseTransaction  Whether to roll back
    * @throws Halt
    */
    protected function halt(bool $shouldRollbackDatabaseTransaction = false): void
    {
        throw (new Halt)->rollBackDatabaseTransaction($shouldRollbackDatabaseTransaction);
    }
}
