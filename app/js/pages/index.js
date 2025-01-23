const bifrost = new Bifrost(
    (bifrost) => {
    },
    (bifrost) => {
        bifrost.replaceTextInElement("body", bifrost.config);

        bifrost.form("form", () => {
            // return false;
        }, async (response) => {
            let jsonRes = await response.json();
            document.querySelector("c-alert").style.display = 'block'
            document.querySelector("#response-form").innerHTML = jsonRes.message;

            if (jsonRes.isSuccess) {
                window.location.href = './home.html';
            }
        });
    }
);
