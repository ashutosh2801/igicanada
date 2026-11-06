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

        document.addEventListener('livewire:init', installTableLoading, { once: true })
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

    .fi-pagination-go-to-ctn {
        grid-column-start: 3;
        justify-self: end;
    }

    .fi-pagination-go-to {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .fi-pagination-go-to .fi-input-wrp {
        min-width: 5rem;
    }

    .fi-pagination-go-to-total {
        font-weight: 500;
        color: rgb(107 114 128);
    }
</style>
