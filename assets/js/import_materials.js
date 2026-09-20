document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modal-add-import");
    const openButton = document.getElementById("btn-open-import");
    const closeButtons = document.querySelectorAll(".close-import-modal");
    const codeInput = document.getElementById("import-code");
    const dateInput = document.getElementById("import-date");

    if (!modal || !openButton) {
        return;
    }

    const pad = (value) => String(value).padStart(2, "0");

    const setDefaultValues = () => {
        const now = new Date();
        const date = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
        const time = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        const sequence = String(document.querySelectorAll("#import-code").length + 3).padStart(2, "0");

        codeInput.value = `PN-${date}-${sequence}`;
        dateInput.value = time;
    };

    const closeModal = () => {
        modal.classList.add("hidden");
    };

    openButton.addEventListener("click", () => {
        setDefaultValues();
        modal.classList.remove("hidden");
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.classList.contains("hidden")) {
            closeModal();
        }
    });
});
