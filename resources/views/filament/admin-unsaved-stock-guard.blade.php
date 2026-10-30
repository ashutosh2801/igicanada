<script>
    (() => {
        if (window.__adminUnsavedStockGuardInstalled) {
            return
        }

        window.__adminUnsavedStockGuardInstalled = true
        window.__adminHasUnsavedStock = false

        const message = 'Stock changes are not saved. Click "Save stock" before leaving this page.'

        window.addEventListener('admin-stock-changed', () => {
            window.__adminHasUnsavedStock = true
        })

        window.addEventListener('stock-updates-saved', () => {
            window.__adminHasUnsavedStock = false
        })

        document.addEventListener('click', (event) => {
            if (!window.__adminHasUnsavedStock || event.defaultPrevented || event.button !== 0) {
                return
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return
            }

            const link = event.target.closest?.('a[href]')

            if (!link || link.target === '_blank' || link.hasAttribute('download')) {
                return
            }

            const url = new URL(link.href, window.location.href)

            if (url.origin !== window.location.origin || url.href === window.location.href) {
                return
            }

            event.preventDefault()
            event.stopImmediatePropagation()
            window.alert(message)
        }, true)

        window.addEventListener('beforeunload', (event) => {
            if (!window.__adminHasUnsavedStock) {
                return
            }

            event.preventDefault()
            event.returnValue = message
        })
    })()
</script>
