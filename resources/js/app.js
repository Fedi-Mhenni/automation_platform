import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Helper global fetch → accessible via $api() dans toutes les expressions Alpine
function apiFetch(url, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    return fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            ...(options.headers ?? {}),
        },
        ...options,
    }).then(r => {
        if (!r.ok) return r.json().then(e => Promise.reject(e));
        return r.json();
    });
}

window.api = apiFetch;

Alpine.magic('api', () => apiFetch);

Alpine.start();
