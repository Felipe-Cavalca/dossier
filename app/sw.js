self.addEventListener("install", async event => {

});

self.addEventListener('fetch', (event) => {
    console.log("[Service Worker] Interceptando requisição:", event.request.url);
    event.respondWith(
        fetch(event.request).then(response => {
            if (response.status === 401 || response.status === 403) {
                // Envia uma mensagem para todas as abas abertas
                self.clients.matchAll().then(clients => {
                    clients.forEach(client => {
                        client.postMessage({ type: 'unauthorized' });
                    });
                });
            } else if (response.status === 500) {
                // Envia uma mensagem para todas as abas abertas
                self.clients.matchAll().then(clients => {
                    clients.forEach(client => {
                        client.postMessage({ type: 'server-error' });
                    });
                });
            }
            return response;
        }).catch(error => {
            return new Response('Erro', { status: 500 });
        })
    );
});
