import { useState, useCallback } from 'react';

export function useToast() {
    const [toasts, setToasts] = useState([]);

    const toast = useCallback(({ title, description, type = 'success', duration = 4000 }) => {
        const id = Date.now() + Math.random();

        setToasts((prev) => [...prev, { id, title, description, type, duration }]);

        setTimeout(() => {
            setToasts((prev) => prev.filter((t) => t.id !== id));
        }, duration);

        return id;
    }, []);

    const dismiss = useCallback((id) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    }, []);

    return { toasts, toast, dismiss };
}
