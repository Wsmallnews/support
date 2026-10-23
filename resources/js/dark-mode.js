document.addEventListener('alpine:init', () => {
    const theme =
        localStorage.getItem('sn-support-frontend-theme') ??
        // Default theme mode, set as a global variable in the layout
        getComputedStyle(document.documentElement).getPropertyValue(
            '--default-theme-mode',
        )

    window.Alpine.store(
        'snSupportFrontendTheme',
        theme === 'dark' ||
            (theme === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)').matches)
            ? 'dark'
            : 'light',
    )

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

    // Follow the OS preference, but only while the user chose "system".
    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', (event) => {
            if (localStorage.getItem('sn-support-frontend-theme') === 'system') {
                window.Alpine.store('snSupportFrontendTheme', event.matches ? 'dark' : 'light')
            }
        })

    // Mirror store changes on the document element and persist the choice.
    window.Alpine.effect(() => {
        const theme = window.Alpine.store('snSupportFrontendTheme')

        theme === 'dark'
            ? document.documentElement.classList.add('dark')
            : document.documentElement.classList.remove('dark')
    })
})
