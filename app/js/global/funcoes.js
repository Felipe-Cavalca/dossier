// Cria o notfy globalmente
var notyf;
function alert() {
    if (notyf == undefined) {
        notyf = new Notyf({
            position: {
                x: 'right',
                y: 'top',
            }
        });
    }

    return notyf;
}

(function () {
    // Aguarda a página carregar completamente
    console.log("[Interceptor] Iniciando Interceptor...");

    // Verifica se Service Workers são suportados
    if (!('serviceWorker' in navigator)) {
        console.warn("[Interceptor] Service Worker não suportado neste navegador.");
        return;
    }

    // Aguarda o Service Worker estar pronto
    navigator.serviceWorker.ready
        .then(() => {
            console.log("[Interceptor] Service Worker está pronto.");

            // Escuta mensagens do Service Worker
            navigator.serviceWorker.addEventListener("message", (event) => {
                console.log("[Interceptor] Mensagem recebida do Service Worker:", event.data);

                if (event.data?.type === 'unauthorized') {
                    redirectToLogin();
                } else if (event.data?.type === 'server-error') {
                    redirectToError();
                }
            });
        })
        .catch((err) => {
            console.warn("[Interceptor] Service Worker não ficou pronto:", err);
        });

    /**
     * Redireciona para a página de login se o usuário não estiver autenticado.
     * * @returns {void}
     */
    function redirectToLogin() {
        const allowedPaths = ['/pages/', '/pages/cadastro.html'];
        if (!allowedPaths.some(path => window.location.pathname.startsWith(path))) {
            window.location.href = "/pages/index.html";
        }
    }

    /**
     * Redireciona para a página de erro caso receba um erro 500 do servidor.
     * * @returns {void}
     */
    function redirectToError() {
        window.location.href = "/pages/error.html";
    }
})();
