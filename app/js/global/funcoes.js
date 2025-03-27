// Suas funções aqui
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
        console.warn("Service Worker não suportado neste navegador.");
        return;
    }

    // Aguarda o Service Worker estar pronto
    navigator.serviceWorker.ready
        .then(() => {
            console.log("[Interceptor] Service Worker está pronto.");

            // Escuta mensagens do Service Worker
            navigator.serviceWorker.addEventListener("message", (event) => {
                if (event.data?.type === 'unauthorized') {
                    redirecionarParaLogin();
                }
            });
        })
        .catch((err) => {
            console.warn("[Interceptor] Service Worker não ficou pronto:", err);
        });

    // Função que você pode customizar
    function redirecionarParaLogin() {
        // Se quiser, pode validar URL atual antes de redirecionar
        const allowedPaths = ['/', '/cadastro.html'];
        if (!allowedPaths.some(path => window.location.pathname.startsWith(path))) {
            window.location.href = "/";
        }
    }
})();
