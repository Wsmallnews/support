<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Forms;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/** Editor component factories, all sharing the form_components.editor config. */
trait HasEditorComponents
{
    // ------------------------- Editors -------------------------

    /**
     * Markdown editor.
     *
     * @param  string  $name  Column name
     */
    public static function markdownEditor(string $name): Forms\Components\MarkdownEditor
    {
        $component = Forms\Components\MarkdownEditor::make($name);

        return static::applyEditorConfig($component);
    }

    /**
     * Rich text editor.
     *
     * @param  string  $name  Column name
     */
    public static function richEditor(string $name): Forms\Components\RichEditor
    {
        $component = Forms\Components\RichEditor::make($name);

        return static::applyEditorConfig($component);
    }

    // ------------------------- Shared config -------------------------

    /** Apply the shared editor config. */
    protected static function applyEditorConfig(
        Forms\Components\MarkdownEditor | Forms\Components\RichEditor $component
    ): Forms\Components\MarkdownEditor | Forms\Components\RichEditor {
        $config = SupportUtils::getConfig('form_components.editor');

        // Disk
        $component->fileAttachmentsDisk(SupportUtils::getFilesystemDisk());
        // Visibility
        if (isset($config['file_attachment']['visibility']) && $component instanceof Forms\Components\RichEditor) {
            $component->fileAttachmentsVisibility($config['file_attachment']['visibility']);
        }
        // Max file size
        if (isset($config['file_attachment']['max_size'])) {
            $component->fileAttachmentsMaxSize($config['file_attachment']['max_size']);
        }
        // Markdown toolbar
        if (isset($config['markdown']['toolbar_buttons']) && $component instanceof Forms\Components\MarkdownEditor) {
            $component->toolbarButtons($config['markdown']['toolbar_buttons']);
        }
        // Rich text toolbar
        if (isset($config['richtext']['toolbar_buttons']) && $component instanceof Forms\Components\RichEditor) {
            $component->toolbarButtons($config['richtext']['toolbar_buttons']);
        }
        // Floating toolbars
        if (isset($config['richtext']['floating_toolbars']) && $component instanceof Forms\Components\RichEditor) {
            $component->floatingToolbars($config['richtext']['floating_toolbars']);
        }
        // Text colours
        if (isset($config['richtext']['text_colors']) && $component instanceof Forms\Components\RichEditor) {
            $component->textColors($config['richtext']['text_colors']);
        }
        // Max length
        if (isset($config['max_length'])) {
            $component->maxLength($config['max_length']);
        }

        return $component;
    }
}
