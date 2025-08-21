document.addEventListener("DOMContentLoaded", function () {
    const carCards = document.querySelectorAll(".car-card");
    const continueButton = document.getElementById("continueToTestDrive");
    const backButton = document.getElementById("backToCarSelection");
    const confirmButton = document.getElementById("confirmButton");
    const successMessage = document.getElementById("successMessage");
    const successButton = document.getElementById("successButton");
    const loadingSpinner = document.getElementById("loadingSpinner");
    const colorOptions = document.querySelectorAll(".color-option");
    const loadMoreButton = document.getElementById("loadmoreCars");
    const fileInput = document.getElementById("fileInput");
    const fileList = document.getElementById("fileList");

    let selectedCar = null;
    let selectedColor = "Red"; // Default selection

    // Car Selection
    carCards.forEach(card => {
        card.addEventListener("click", () => {
            carCards.forEach(c => c.classList.remove("selected"));
            card.classList.add("selected");
            selectedCar = card;
            selectedColor = card.querySelector(".color-option.selected")?.getAttribute("data-color") || "Red";
        });
    });

    // Color Selection
    colorOptions.forEach(option => {
        option.addEventListener("click", function () {
            const parent = this.closest(".car-card");
            parent.querySelectorAll(".color-option").forEach(opt => opt.classList.remove("selected"));
            this.classList.add("selected");
            selectedColor = this.getAttribute("data-color");
        });
    });

    // Continue to Test Drive Page
    continueButton.addEventListener("click", () => {
        if (selectedCar) {
            document.getElementById("page1").classList.remove("active");
            document.getElementById("page2").classList.add("active");

            document.getElementById("step1").classList.remove("active");
            document.getElementById("step2").classList.add("active");

            const carModel = selectedCar.querySelector(".car-model").textContent;
            const carPrice = selectedCar.querySelector(".car-price").textContent;

            document.getElementById("selectedCarSummary").innerHTML = `
                <h3>Selected Car: ${carModel}</h3>
                <p>Color: ${selectedColor}</p>
                <p>Price: ${carPrice}</p>
            `;
        } else {
            alert("Please select a car before continuing.");
        }
    });

    // Back to Car Selection
    backButton.addEventListener("click", () => {
        document.getElementById("page2").classList.remove("active");
        document.getElementById("page1").classList.add("active");

        document.getElementById("step2").classList.remove("active");
        document.getElementById("step1").classList.add("active");
    });

    // Confirm Test Drive
    confirmButton.addEventListener("click", () => {
        const firstName = document.getElementById("firstName").value;
        const lastName = document.getElementById("lastName").value;
        const email = document.getElementById("email").value;
        const phone = document.getElementById("phone").value;
        const location = document.getElementById("location").value;
        const date = document.getElementById("dateInput").value;
        const timeSlot = document.getElementById("timeSlot").value;

        if (firstName && lastName && email && phone && location && date && timeSlot) {
            loadingSpinner.style.display = "flex";

            setTimeout(() => {
                loadingSpinner.style.display = "none";
                successMessage.style.display = "flex";
                document.getElementById("confirmationEmail").textContent = email;
            }, 2000);
        } else {
            alert("Please fill out all fields before confirming.");
        }
    });

    // Close Success Message
    successButton.addEventListener("click", () => {
        successMessage.style.display = "none";
    });

    // Load More Cars
    loadMoreButton.addEventListener("click", () => {
        document.querySelectorAll(".car-card.hidden").forEach(card => card.classList.remove("hidden"));
        loadMoreButton.style.display = "none"; // Hide button after loading
    });

    // File Upload Handling
    fileInput.addEventListener("change", function () {
        fileList.innerHTML = "";
        Array.from(this.files).forEach(file => {
            const fileItem = document.createElement("div");
            fileItem.textContent = file.name;
            fileList.appendChild(fileItem);
        });
    });
});
