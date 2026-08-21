<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Forms;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 内容编辑器表单组件工厂
 *
 * 统一应用 sn-support.form_components.editor 配置。
 */
trait HasEditorComponents
{
    // ========================= 内容编辑器组件 =========================

    /**
     * 创建一个 Markdown 编辑器组件
     *
     * @param  string  $name  字段名称
     */
    public static function markdownEditor(string $name): Forms\Components\MarkdownEditor
    {
        $component = Forms\Components\MarkdownEditor::make($name);

        return static::applyEditorConfig($component);
    }

    /**
     * 创建一个富文本编辑器组件
     *
     * @param  string  $name  字段名称
     */
    public static function richEditor(string $name): Forms\Components\RichEditor
    {
        $component = Forms\Components\RichEditor::make($name);

        return static::applyEditorConfig($component);
    }

    // ========================= 通用配置 =========================

    /**
     * 为编辑器组件应用配置
     */
    protected static function applyEditorConfig(
        Forms\Components\MarkdownEditor | Forms\Components\RichEditor $component
    ): Forms\Components\MarkdownEditor | Forms\Components\RichEditor {
        $config = SupportUtils::getConfig('form_components.editor');

        // 默认磁盘
        $component->fileAttachmentsDisk(SupportUtils::getFilesystemDisk());
        // 可见性
        if (isset($config['file_attachment']['visibility']) && $component instanceof Forms\Components\RichEditor) {
            $component->fileAttachmentsVisibility($config['file_attachment']['visibility']);
        }
        // 最大文件大小
        if (isset($config['file_attachment']['max_size'])) {
            $component->fileAttachmentsMaxSize($config['file_attachment']['max_size']);
        }
        // markdown 工具栏
        if (isset($config['markdown']['toolbar_buttons']) && $component instanceof Forms\Components\MarkdownEditor) {
            $component->toolbarButtons($config['markdown']['toolbar_buttons']);
        }
        // richtext 工具栏
        if (isset($config['richtext']['toolbar_buttons']) && $component instanceof Forms\Components\RichEditor) {
            $component->toolbarButtons($config['richtext']['toolbar_buttons']);
        }
        // 浮动工具栏
        if (isset($config['richtext']['floating_toolbars']) && $component instanceof Forms\Components\RichEditor) {
            $component->floatingToolbars($config['richtext']['floating_toolbars']);
        }
        // 文本颜色
        if (isset($config['richtext']['text_colors']) && $component instanceof Forms\Components\RichEditor) {
            $component->textColors($config['richtext']['text_colors']);
        }
        // 文本长度
        if (isset($config['max_length'])) {
            $component->maxLength($config['max_length']);
        }

        return $component;
    }
}
