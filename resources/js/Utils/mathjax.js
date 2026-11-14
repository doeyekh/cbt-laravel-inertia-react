let mathJaxPromise = null

export function loadMathJax() {
    if (mathJaxPromise) {
        return mathJaxPromise
    }

    mathJaxPromise = new Promise((resolve) => {
        if (window.MathJax?.startup?.promise) {
            resolve(window.MathJax.startup.promise)

            return
        }

        const script = document.createElement('script')
        script.src = 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js'
        script.async = true
        script.onload = () => resolve(window.MathJax?.startup?.promise ?? window.MathJax)
        script.onerror = () => resolve(null)
        document.head.appendChild(script)
    })

    return mathJaxPromise
}

export async function typesetMath(element) {
    if (!element) {
        return
    }

    const mathjax = await loadMathJax()

    if (!mathjax?.typesetPromise) {
        return
    }

    try {
        await mathjax.typesetPromise([element])
    } catch (error) {
        console.error('MathJax gagal merender rumus.', error)
    }
}

export default { loadMathJax, typesetMath }
