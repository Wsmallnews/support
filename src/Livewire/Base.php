<?php

namespace Wsmallnews\Support\Livewire;

use Closure;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Support\Exceptions\Halt;
use Livewire\Component;
use Throwable;
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Livewire\Concerns\ScopedBySite;

class Base extends Component
{
    use CanUseDatabaseTransactions;
    use ScopedBySite;

    /**
     * Current authenticated identity of the bound site (member or user, decided
     * by the site's auth.user_type). Read-only: components that accept an
     * explicit `authUser` prop use HasAuth, which overrides this as a fallback.
     */
    public function getAuthUser(): ?HasSnIdentifiable
    {
        $user = app(SiteContext::class)->getAuthUser();

        return $user instanceof HasSnIdentifiable ? $user : null;
    }

    /**
     * Run the callback in a database transaction when $hasDatabaseTransactions is on. A Halt
     * exception rolls back or commits as it asks; any other exception rolls back and rethrows.
     *
     * @param  Closure  $callback  Work to run inside the transaction
     * @return mixed The callback result
     *
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
     *
     * @throws Halt
     */
    protected function halt(bool $shouldRollbackDatabaseTransaction = false): void
    {
        throw (new Halt)->rollBackDatabaseTransaction($shouldRollbackDatabaseTransaction);
    }
}
