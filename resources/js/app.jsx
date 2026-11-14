import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import { router } from '@inertiajs/react'
import { initZiggy } from '@/Utils/route'
import '../css/app.css';

function updateTitle(props) {
    const title = props?.title || window.__appName || 'CBT Panel'
    document.title = title
}

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.jsx')
        return pages[`./Pages/${name}.jsx`]()
    },
    setup({ el, App, props }) {
        const sharedProps = props.initialPage?.props ?? props

        if (sharedProps.routes) {
            initZiggy(sharedProps.routes, sharedProps.location)
        }

        updateTitle(sharedProps)

        createRoot(el).render(<App {...props} />)
    },
})

// Update title on subsequent Inertia visits
router.on('afterRender', (page) => {
    updateTitle(page.props)
})
