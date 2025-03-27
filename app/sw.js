self.addEventListener("install", async event => {

});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).then(response => {
            if (response.status === 401) {
                // Envia uma mensagem para todas as abas abertas
                self.clients.matchAll().then(clients => {
                    clients.forEach(client => {
                        client.postMessage({ type: 'unauthorized' });
                    });
                });
            }
            return response;
        }).catch(error => {
            return new Response('Erro', { status: 500 });
        })
    );
});
