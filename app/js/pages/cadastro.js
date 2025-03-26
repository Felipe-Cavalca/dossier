var originalTextButton;

const beforeLoad = (bifrost) => {
    bifrost.replaceTextInElement("body", bifrost.config);
}

const afterLoad = (bifrost) => {
    bifrost.form("form", beforeSend, afterSend);
}

const beforeSend = () => {
    let invalido = document.querySelector("input:invalid");

    if (invalido) {
        alert().error("Preencha todos os campos corretamente");
        return false;
    }

    let button = document.querySelector("button[type=submit]");
    button.disabled = true;
    originalTextButton = button.innerHTML;
    button.innerHTML = "Enviando...";

    return true;
}

const afterSend = async (response) => {
    let data = await response.json();

    let button = document.querySelector("button[type=submit]");
    button.disabled = false;
    button.innerHTML = originalTextButton;

    if (data.isSuccess) {
        alert().success("Cadastro realizado com sucesso, você será redirecionado para a página inicial");
        document.querySelector("form").reset();
        setInterval(() => {
            window.location.href = "/";
        }, 2000);
    } else {
        alert().error(data.message);
    }
}

new Bifrost(beforeLoad, afterLoad);
