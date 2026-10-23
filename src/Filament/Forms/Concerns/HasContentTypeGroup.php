<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Content type switcher. Relies on the host's richEditor(), markdownEditor() and
 * plainImageUpload() factories so editors pick up the shared editor and upload config.
 */
trait HasContentTypeGroup
{
    // ------------------------- Public factories -------------------------

    /**
    * Content type group bound to a morphOne content relation: content_type switches the
    * editor (textarea, richtext, markdown or images) and everything is stored in the related
    * content column; images are stored as a JSON array of paths. Saving is internal, so
    * Create and Edit pages need no content handling.
    *
    * @param  array<int, ContentType|string>|null  $types  Allowed types; null uses the global config or every type
    * @param  ContentType|string|null  $defaultType  Default type; null uses the global config or Richtext
    * @param  string  $relationship  Relation name
    * @param  string|null  $label  Field label, defaults to the support translation
    * @param  string|null  $directory  Upload directory for editor attachments and images
    * @param  bool  $required  Whether content is mandatory (false deletes the related record when empty)
    */
    public static function contentTypeGroup(
        ?array $types = null,
        ContentType | string | null $defaultType = null,
        string $relationship = 'content',
        ?string $label = null,
        ?string $directory = null,
        bool $required = true,
    ): Group {
        $types = static::resolveContentTypes($types);
        $defaultType = static::resolveContentTypeDefault($defaultType, $types);

        $typeButtons = Forms\Components\ToggleButtons::make('content_type')
            ->label(__('sn-support::support.form_components.content.type_label'))
            ->options(collect($types)->mapWithKeys(fn (ContentType $type): array => [$type->value => (string) $type->getLabel()])->all())
            ->enum(ContentType::class)
            ->default($defaultType)
            ->inline()
            ->grouped()
            ->live();

        $detailLabel = $label ?? __('sn-support::support.form_components.content.detail_label');

        $editors = [
            // A single type hides the switcher; Hidden keeps content_type filled and dehydrated
            count($types) === 1
                ? Forms\Components\Hidden::make('content_type')->default($defaultType)
                : $typeButtons,
            Forms\Components\Hidden::make('content'),
        ];

        foreach ($types as $type) {
            $previewAction = static::previewContentAction();

            $editors[] = match ($type) {
                ContentType::Textarea => Forms\Components\Textarea::make('content_textarea')
                    ->label($detailLabel)
                    ->placeholder(__('sn-support::support.form_components.content.placeholder'))
                    ->rows(5)
                    ->autosize()
                    ->required($required)
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Textarea),
                ContentType::Richtext => static::richEditor('content_richtext')
                    ->label($detailLabel)
                    ->placeholder(__('sn-support::support.form_components.content.placeholder'))
                    ->fileAttachmentsDirectory($directory)
                    ->required($required)
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Richtext),
                ContentType::Markdown => static::markdownEditor('content_markdown')
                    ->label($detailLabel)
                    ->placeholder(__('sn-support::support.form_components.content.markdown_placeholder'))
                    ->fileAttachmentsDirectory($directory)
                    ->required($required)
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Markdown),
                ContentType::Images => static::plainImageUpload('content_images')
                    ->label($detailLabel)
                    ->directory($directory)
                    ->multiple()
                    ->required($required)
                    ->panelLayout('compact')        // Stacked layout, one image per row
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Images),
            };
        }

        return Group::make()
            ->relationship($relationship)
            ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => static::fillVirtualContentField($data))
            ->saveRelationshipsBeforeChildrenUsing(function (Group $component) use ($relationship, $required, $defaultType): void {
                $data = $component->getChildSchema()->getState(shouldCallHooksBefore: false);

                $type = static::normalizeContentType($data['content_type'] ?? null) ?? $defaultType;
                $value = $data['content_' . $type->value] ?? null;

                // Whitespace-only rich text (e.g. an empty <p></p>) counts as empty like any other type
                $isBlank = $type === ContentType::Richtext
                    ? blank(trim(strip_tags((string) $value)))
                    : blank($value);

                // Optional and empty: create no related record, and delete an existing one
                if ((! $required) && $isBlank) {
                    $component->getCachedExistingRecord()?->delete();

                    return;
                }

                $record = $component->getRecord()->{$relationship}()->updateOrCreate([], static::mapVirtualContentField($data, $type));
                $component->cachedExistingRecord($record);
            })
            ->schema($editors)
            ->columns(1);
    }

    // ------------------------- Preview action -------------------------

    /** Preview action: reads the virtual field and renders it in a modal. */
    protected static function previewContentAction(): Action
    {
        return Action::make('previewContent')
            ->label(__('sn-support::support.form_components.content.preview'))
            ->icon(Heroicon::OutlinedEye)
            ->modal()
            ->modalHeading(__('sn-support::support.form_components.content.preview'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('sn-support::support.table.column.content_close'))
            ->modalContent(function (Get $get) {
                $type = static::normalizeContentType($get('content_type'));

                if (! $type) {
                    return null;
                }

                return view('sn-support::components.content', [
                    'contentType' => $type,
                    'content' => $get('content_' . $type->value),
                ]);
            });
    }

    // ------------------------- Type resolution -------------------------

    /**
    * Resolve the allowed types: explicit argument, then form_components.content.types, then all.
    *
    * @param  array<int, ContentType|string>|null  $types
    * @return array<int, ContentType>
    */
    protected static function resolveContentTypes(?array $types): array
    {
        $configTypes = $types ?? SupportUtils::getConfig('form_components.content.types');

        $resolved = collect(is_array($configTypes) ? $configTypes : ContentType::cases())
            ->map(fn (mixed $type): ?ContentType => static::normalizeContentType($type))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return filled($resolved) ? $resolved : ContentType::cases();
    }

    /**
    * Resolve the default type: explicit argument, then form_components.content.default_type,
    * then Richtext, always constrained to the allowed list.
    *
    * @param  array<int, ContentType>  $types
    */
    protected static function resolveContentTypeDefault(ContentType | string | null $defaultType, array $types): ContentType
    {
        $defaultType = static::normalizeContentType($defaultType)
            ?? static::normalizeContentType(SupportUtils::getConfig('form_components.content.default_type'))
            ?? ContentType::Richtext;

        return in_array($defaultType, $types, true) ? $defaultType : $types[0];
    }

    /** Normalise a content type, accepting enum instances and raw strings. */
    protected static function normalizeContentType(mixed $type): ?ContentType
    {
        return match (true) {
            $type instanceof ContentType => $type,
            is_string($type) && filled($type) => ContentType::tryFrom($type),
            default => null,
        };
    }

    // ------------------------- Virtual field mapping -------------------------

    /**
    * Map the related content column onto the virtual field of the current type (images decoded).
    *
    * @param  array<string, mixed>  $data
    * @return array<string, mixed>
    */
    protected static function fillVirtualContentField(array $data): array
    {
        $type = static::normalizeContentType($data['content_type'] ?? null);

        if (! $type) {
            return $data;
        }

        $value = $data['content'] ?? null;

        if ($type === ContentType::Images) {
            $decoded = json_decode((string) ($value ?? ''), true);

            $data['content_images'] = is_array($decoded) ? array_values($decoded) : [];
        } else {
            $data['content_' . $type->value] = $value;
        }

        return $data;
    }

    /**
    * Map the virtual field back onto the content column (images encoded as JSON).
    *
    * @param  array<string, mixed>  $data
    * @return array<string, mixed>
    */
    protected static function mapVirtualContentField(array $data, ContentType $type): array
    {
        $value = $data['content_' . $type->value] ?? null;

        $data['content'] = $type === ContentType::Images
            ? (blank($value) ? null : json_encode(array_values($value)))
            : $value;

        unset($data['content_textarea'], $data['content_richtext'], $data['content_markdown'], $data['content_images']);

        return $data;
    }
}
