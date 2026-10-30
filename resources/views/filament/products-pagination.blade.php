<script>
    (() => {
        const installTableLoading = () => {
            if (!window.Livewire || window.__adminTableLoadingInstalled) {
                return
            }

            window.__adminTableLoadingInstalled = true
            let activeRequests = 0

            const getTable = (component) => component?.el?.querySelector?.('.fi-ta')
                ?? component?.el?.closest?.('.fi-ta')

            const showLoading = (table) => {
                if (!table) {
                    return
                }

                table.classList.add('admin-table-is-loading')

                if (table.querySelector('.admin-table-loading')) {
                    return
                }

                const loading = document.createElement('div')
                loading.className = 'admin-table-loading'
                loading.setAttribute('role', 'status')
                loading.innerHTML = '<span class="admin-table-loading-spinner"></span><span>Loading...</span>'
                table.append(loading)
            }

            const hideLoading = () => {
                activeRequests = Math.max(0, activeRequests - 1)

                if (activeRequests > 0) {
                    return
                }

                document.querySelectorAll('.admin-table-is-loading').forEach((table) => {
                    table.classList.remove('admin-table-is-loading')
                    table.querySelector('.admin-table-loading')?.remove()
                })
            }

            window.Livewire.hook('commit', ({ component, succeed, fail }) => {
                const table = getTable(component)

                if (!table) {
                    return
                }

                activeRequests++
                showLoading(table)
                succeed(hideLoading)
                fail(hideLoading)
            })
        }

        installTableLoading()

        const enhancePagination = () => {
            document.querySelectorAll('.fi-pagination').forEach((pagination) => {
                if (pagination.dataset.productsGoToPage === 'true') {
                    const existingControls = pagination.querySelector('.fi-pagination-products-right-controls')
                    pagination.style.position = 'relative'
                    existingControls?.style.setProperty('position', 'absolute')
                    existingControls?.style.setProperty('right', '0')
                    existingControls?.style.setProperty('top', '50%')
                    existingControls?.style.setProperty('transform', 'translateY(-50%)')
                    return
                }

                const pageItems = [...pagination.querySelectorAll('.fi-pagination-item button[aria-label]')]
                    .map((item) => ({
                        item: item.closest('.fi-pagination-item'),
                        match: item.getAttribute('aria-label')?.match(/(\d+)$/),
                        current: item.getAttribute('aria-current') === 'page',
                    }))
                    .filter(({ match }) => match)

                const lastPageButton = [...pagination.querySelectorAll('.fi-pagination-item button[aria-label]')]
                    .find((button) => /last page/i.test(button.getAttribute('aria-label') ?? ''))
                const lastPageAction = lastPageButton?.closest('.fi-pagination-item')?.getAttribute('wire:click') ?? ''
                const lastPageMatch = lastPageAction.match(/gotoPage\((\d+)/)
                const lastPage = Number(lastPageMatch?.[1] ?? Math.max(...pageItems.map(({ match }) => Number(match[1]))))
                const currentPage = pageItems.find(({ current }) => current)?.match?.[1] ?? '1'
                const pageAction = pageItems.find(({ item }) => item?.getAttribute('wire:click'))?.item?.getAttribute('wire:click') ?? ''
                const pageName = (lastPageAction || pageAction).match(/gotoPage\(\d+,\s*'([^']+)'/)?.[1]

                if (!Number.isFinite(lastPage) || lastPage < 2) {
                    return
                }

                const wrapper = document.createElement('label')
                wrapper.className = 'fi-pagination-products-go-to-page'
                wrapper.innerHTML = '<span>Go to page</span>'

                const select = document.createElement('select')
                select.setAttribute('aria-label', 'Go to page')
                select.className = 'fi-input'

                for (let page = 1; page <= lastPage; page++) {
                    const option = document.createElement('option')
                    option.value = page
                    option.textContent = page

                    if (String(page) === currentPage) {
                        option.selected = true
                    }

                    select.append(option)
                }

                select.addEventListener('change', () => {
                    const page = select.value
                    const existingPage = pageItems.find(({ match }) => match[1] === page)?.item

                    if (existingPage) {
                        existingPage.click()
                        return
                    }

                    let component = pagination

                    while (component && !component.getAttribute('wire:id')) {
                        component = component.parentElement
                    }

                    const livewireComponent = component
                        ? window.Livewire?.find(component.getAttribute('wire:id'))
                        : null

                    if (livewireComponent && pageName) {
                        livewireComponent.$wire.set(pageName, Number(page))
                    }
                })

                wrapper.append(select)
                const pageItemsList = pagination.querySelector('.fi-pagination-items')
                const nextButton = pagination.querySelector('.fi-pagination-next-btn')
                const rightControls = document.createElement('div')
                rightControls.className = 'fi-pagination-products-right-controls'
                pagination.style.position = 'relative'
                rightControls.style.cssText = 'position:absolute;right:0;top:50%;transform:translateY(-50%);display:inline-flex;align-items:center;gap:.75rem;z-index:2;'

                if (nextButton) {
                    nextButton.style.display = 'none'
                }

                if (pageItemsList) {
                    pageItemsList.style.display = 'none'
                }

                rightControls.append(wrapper)
                pagination.append(rightControls)
                pagination.dataset.productsGoToPage = 'true'
            })
        }

        enhancePagination()
        document.addEventListener('livewire:init', installTableLoading, { once: true })
        new MutationObserver(enhancePagination).observe(document.body, { childList: true, subtree: true })
    })()
</script>

<style>
    .fi-ta {
        position: relative;
    }

    .admin-table-loading {
        position: absolute;
        inset: 0;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        background: color-mix(in srgb, white 65%, transparent);
        color: rgb(75 85 99);
        font-size: 0.875rem;
        font-weight: 600;
        pointer-events: all;
    }

    .admin-table-loading-spinner {
        width: 1.1rem;
        height: 1.1rem;
        border: 2px solid rgb(209 213 219);
        border-top-color: rgb(185 28 28);
        border-radius: 9999px;
        animation: admin-table-spin 0.7s linear infinite;
    }

    @keyframes admin-table-spin {
        to { transform: rotate(360deg); }
    }

    .fi-pagination-products-go-to-page {
        position: absolute !important;
        inset-inline-end: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: inline-flex !important;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .fi-pagination-products-go-to-page select {
        min-width: 4.5rem;
        border-radius: 0.5rem;
        padding: 0.45rem 0.65rem;
    }

    .fi-pagination-products-right-controls {
        position: absolute !important;
        inset-inline-end: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: inline-flex !important;
        align-items: center;
        gap: 0.75rem;
    }

    .fi-pagination {
        position: relative;
    }

    .fi-pagination-products-right-controls .fi-pagination-items {
        display: flex !important;
    }
</style>
