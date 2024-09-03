function beforeLoad(bifrost) {
    bifrost.replaceTextInElement("body", bifrost.configApp)
};

function afterLoad(bifrost) {
    loadScrool();

    bifrost.form("form", beforeForm, afterForm);
};

function loadScrool() {
    let currentSection = 0;
    const sections = document.querySelectorAll('.full-screen');

    window.addEventListener('wheel', function (event) {
        if (event.deltaY > 0 && currentSection < sections.length - 1) {
            currentSection++;
        } else if (event.deltaY < 0 && currentSection > 0) {
            currentSection--;
        }

        window.scrollTo({
            top: sections[currentSection].offsetTop,
            behavior: 'smooth'
        });
    });
}

function beforeForm() {

}

function afterForm(data) {
    data = JSON.parse(data);
    if (data.status) {
        window.location.href = "home.html";
    } else {
        $("c-alert").show();
    }
}

let bifrost = new Bifrost(beforeLoad, afterLoad);
