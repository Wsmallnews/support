<?php

namespace Wsmallnews\Support\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Wsmallnews\Support\Exceptions\SupportException;

class BuilderMacros
{
    public static function register(): void
    {
        static::registerIncrementJson();
        static::registerDecrementJson();
    }

    protected static function registerIncrementJson(): void
    {
        Builder::macro('incrementJson', function ($jsonPath, $amount = 1, array $extra = []) {
            /** @var Builder $this */
            $fields = explode('->', $jsonPath);

            $field = $fields[0] ?? null;
            $subField = $fields[1] ?? null;
            static::validateJsonPath($field, $subField, $jsonPath);

            if (isset($extra['withupdate']) && $extra['withupdate']) {
                return $this->update([
                    $field => DB::raw("JSON_SET(
                        COALESCE({$field}, '{}'), '$.{$subField}', CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) + {$amount}
                    )"),
                ]);
            } else {
                Model::withoutTimestamps(
                    fn () => $this->update([
                        $field => DB::raw("JSON_SET(
                            COALESCE({$field}, '{}'), '$.{$subField}', CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) + {$amount}
                        )"),
                    ])
                );
            }
        });
    }

    protected static function registerDecrementJson(): void
    {
        Builder::macro('decrementJson', function ($jsonPath, $amount = 1, array $extra = []) {
            /** @var Builder $this */
            $fields = explode('->', $jsonPath);

            $field = $fields[0] ?? null;
            $subField = $fields[1] ?? null;
            static::validateJsonPath($field, $subField, $jsonPath);

            if (isset($extra['withupdate']) && $extra['withupdate']) {
                return $this->update([
                    $field => DB::raw("JSON_SET(
                        COALESCE({$field}, '{}'), '$.{$subField}', GREATEST(
                            (CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) - {$amount})
                        , 0)
                    )"),
                ]);
            } else {
                Model::withoutTimestamps(
                    fn () => $this->update([
                        $field => DB::raw("JSON_SET(
                            COALESCE({$field}, '{}'), '$.{$subField}', GREATEST(
                                (CAST(COALESCE({$field}->>'$.{$subField}', 0) AS SIGNED) - {$amount})
                            , 0)
                        )"),
                    ])
                );
            }
        });
    }

    protected static function validateJsonPath(?string $field, ?string $subField, string $jsonPath): void
    {
        if (blank($field) || blank($subField)) {
            throw new SupportException("json path format error: {$jsonPath}, for example, `counter->like_num`");
        }
    }
}
