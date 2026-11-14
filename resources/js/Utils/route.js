import { route as ziggyRoute } from 'ziggy-js'

let ziggyReady = false

export function initZiggy(routes, location) {
    window.Ziggy = {
        ...routes,
        location: location || window.location.pathname,
    }
    ziggyReady = true
}

export function route(name, params = {}, absolute = false) {
    if (!ziggyReady || typeof window.Ziggy === 'undefined') {
        console.warn(`Ziggy route "${name}" called before Ziggy was initialized.`)

        return '#'
    }

    try {
        return ziggyRoute(name, params, absolute)
    } catch (error) {
        console.warn(`Ziggy route "${name}" is not defined.`)

        return '#'
    }
}

export function hasRoute(name) {
    if (!ziggyReady || typeof window.Ziggy === 'undefined') {
        return false
    }

    return Boolean(window.Ziggy.routes && window.Ziggy.routes[name])
}

export function currentRoute(name) {
    if (!ziggyReady || typeof window.Ziggy === 'undefined') {
        return false
    }

    try {
        return ziggyRoute().current(name)
    } catch {
        return false
    }
}

export default { route, hasRoute, currentRoute, initZiggy }
