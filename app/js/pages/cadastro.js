const bifrost = new Bifrost(
    (bifrost) => {
        bifrost.replaceTextInElement("body", bifrost.config);
    },
    (bifrost) => {

    }
);

function toInput(element) {
    window.location.href = element;
    document.querySelector(`${element} input`).focus();
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
