document.addEventListener('alpine:init', () => {
    const theme =
        localStorage.getItem('sn-support-frontend-theme') ??
        getComputedStyle(document.documentElement).getPropertyValue(            // 读取默认主题色变量 （请在 app.blade.php 中设置全局变量）
            '--default-theme-mode',
        )

    //  根据获取的 theme 设置 store 中的 theme
    window.Alpine.store(
        'snSupportFrontendTheme',
        theme === 'dark' ||
            (theme === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)').matches)
            ? 'dark'
            : 'light',
    )

    // 监听主题变化事件
    // 当主题变化时，更新 store 中的 theme 并将主题保存到 localStorage 中
    window.addEventListener('sn-support-frontend-theme-changed', (event) => {
        let theme = event.detail

        localStorage.setItem('sn-support-frontend-theme', theme)

        if (theme === 'system') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light'
        }

        window.Alpine.store('snSupportFrontendTheme', theme)
    })

    // 监听系统主题变化事件
    // 当系统主题变化时，更新 store 中的 theme
    // 仅当用户选择 system 主题时才触发
    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', (event) => {
            if (localStorage.getItem('sn-support-frontend-theme') === 'system') {
                window.Alpine.store('snSupportFrontendTheme', event.matches ? 'dark' : 'light')
            }
        })

    // 监听 store 中的 theme 变化
    // 当 store 中的 theme 变化时，更新 document.documentElement 的 class 列表
    window.Alpine.effect(() => {
        const theme = window.Alpine.store('snSupportFrontendTheme')

        theme === 'dark'
            ? document.documentElement.classList.add('dark')
            : document.documentElement.classList.remove('dark')
    })
})
