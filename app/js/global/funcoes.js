// Suas funções aqui
var notyf;

function alert() {
    if(notyf == undefined) {
        notyf = new Notyf({
            position: {
                x: 'right',
                y: 'top',
            }
        });
    }

    return notyf;
}
