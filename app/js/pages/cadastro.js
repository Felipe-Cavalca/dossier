const bifrost = new Bifrost(
    (bifrost) => {
        bifrost.replaceTextInElement("body", bifrost.config);
    },
    (bifrost) => {

    }
);

function toInput(element) {
    window.location.href = element;
    setTimeout(() => {
        document.querySelector(`${element} input`).focus();
    }, 500);
}

function focusOnEnter(event, nextElement) {
    if (event.key === 'Enter') {
        event.preventDefault();
        toInput(nextElement);
    }
}

function focusOnButton(nextElement) {
    toInput(nextElement);
}
