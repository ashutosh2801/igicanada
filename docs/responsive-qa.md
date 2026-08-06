# Responsive QA handoff

## Automated checks completed

- Both React entry points compile in the production Vite build.
- Retail and wholesale layouts use mobile-first grids and responsive breakpoints.
- Product galleries use horizontal, touch-scrollable thumbnail rails.
- Breadcrumbs and navigation rails scroll horizontally instead of widening the viewport.
- Retail mobile branding stays centered independently of the cart icon.

## Required rendered-device sign-off

| Device class | Viewports | Critical journeys |
|---|---|---|
| Mobile | 320×568, 390×844 | Home, menu/search, listing, product/gallery, cart, checkout, order confirmation, policies |
| Tablet | 768×1024, 1024×768 | Same journeys in portrait and landscape |
| Desktop | 1366×768, 1920×1080 | Mega menu, listing grids, 50/50 product layout, checkout, admin tables/forms |

For every viewport, verify no horizontal page overflow, no clipped text/buttons, visible keyboard focus, touch targets, image aspect ratios, validation messages, sticky-header overlap, and footer/legal links. Also test Safari/iOS, Chrome/Android, Chrome desktop, and Safari desktop. A real-device visual sign-off remains required because the in-app responsive browser was unavailable in this workspace.
