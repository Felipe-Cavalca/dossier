const bifrost = new Bifrost(
    (bifrost) => {
    },
    (bifrost) => {
    }
);

// Exemplo: mostrar detalhes do erro se vier via query string
function getErrorDetails() {
    const params = new URLSearchParams(window.location.search);
    return params.get('msg') || '';
}
function tryAgain() {
    window.location.href = '/';
}
// Exibe detalhes se houver
const details = getErrorDetails();
if (details) {
    document.getElementById('details').textContent = details;
}
