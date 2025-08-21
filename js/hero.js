document.addEventListener("DOMContentLoaded", () => {
  // Initialize price range sliders
  initPriceRangeSliders()

  // Setup form submissions
  setupFormSubmissions()

  // Setup dynamic model loading
  setupDynamicModelLoading()

  // Load cart items on page load
  loadCartItems()
})

function initPriceRangeSliders() {
  // Check if noUiSlider is loaded
  if (typeof noUiSlider === "undefined") {
    console.error("noUiSlider is not loaded")
    return
  }

  // Rental form price slider
  const rentPriceRange = document.getElementById("rentPriceRange")
  if (rentPriceRange) {
    noUiSlider.create(rentPriceRange, {
      start: [0, 100000],
      connect: true,
      range: {
        min: 0,
        max: 100000,
      },
      format: {
        to: (value) => Math.round(value),
        from: (value) => Number(value),
      },
    })

    rentPriceRange.noUiSlider.on("update", (values, handle) => {
      document.getElementById("rentMinPrice").value = values[0]
      document.getElementById("rentMaxPrice").value = values[1]
    })
  }

  // Buy form price slider
  const buyPriceRange = document.getElementById("buyPriceRange")
  if (buyPriceRange) {
    noUiSlider.create(buyPriceRange, {
      start: [0, 100000],
      connect: true,
      range: {
        min: 0,
        max: 100000,
      },
      format: {
        to: (value) => Math.round(value),
        from: (value) => Number(value),
      },
    })

    buyPriceRange.noUiSlider.on("update", (values, handle) => {
      document.getElementById("buyMinPrice").value = values[0]
      document.getElementById("buyMaxPrice").value = values[1]
    })
  }
}

function setupFormSubmissions() {
  // Rental form submission
  const rentalForm = document.getElementById("rentalForm")
  if (rentalForm) {
    rentalForm.addEventListener("submit", (e) => {
      e.preventDefault()
      handleFormSubmit("rent")
    })
  }

  // Buy form submission
  const buyForm = document.getElementById("buyForm")
  if (buyForm) {
    buyForm.addEventListener("submit", (e) => {
      e.preventDefault()
      handleFormSubmit("buy")
    })
  }
}

function handleFormSubmit(formType) {
  const form = document.getElementById(formType === "rent" ? "rentalForm" : "buyForm")
  const formData = new FormData(form)

  // Create URL with parameters
  const params = new URLSearchParams()
  for (const [key, value] of formData.entries()) {
    params.append(key, value)
  }

  // Fetch results
  fetch("search-results.php?" + params.toString())
    .then((response) => response.text())
    .then((html) => {
      // Update modal content
      const searchResults = document.querySelector("#searchModal .modal-body .row")
      if (searchResults) {
        searchResults.innerHTML = html

        // Show modal using jQuery
        $("#searchModal").modal("show")

        // Setup result actions (add to cart, etc.)
        setupResultActions()
      } else {
        console.error("Search results container not found")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      alert("An error occurred while searching. Please try again.")
    })

  return false
}

function setupDynamicModelLoading() {
  // When brand is selected, load corresponding models
  const brandSelects = document.querySelectorAll('[name="brand"]')
  brandSelects.forEach((select) => {
    select.addEventListener("change", function () {
      const brand = this.value
      const formId = this.closest("form").id
      const modelSelect = document.getElementById(formId === "rentalForm" ? "rentModel" : "buyModel")

      if (!brand) {
        // Clear models if no brand selected
        modelSelect.innerHTML = '<option value="">Select Model</option>'
        return
      }

      // Fetch models for selected brand
      fetch("get-models.php?brand=" + encodeURIComponent(brand))
        .then((response) => response.json())
        .then((models) => {
          modelSelect.innerHTML = '<option value="">Select Model</option>'
          models.forEach((model) => {
            const option = document.createElement("option")
            option.value = model
            option.textContent = model
            modelSelect.appendChild(option)
          })
        })
        .catch((error) => {
          console.error("Error loading models:", error)
        })
    })
  })
}

function setupResultActions() {
  // Add to cart functionality for search results
  const addToCartButtons = document.querySelectorAll(".add-to-cart-btn")
  addToCartButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault()

      const carId = this.dataset.id
      const carName = this.dataset.name
      const carPrice = this.dataset.price
      const carImg = this.dataset.img
      const carType = this.dataset.type

      // Add to cart
      addToCart({
        id: carId,
        name: carName,
        price: carPrice,
        image: carImg,
        type: carType,
      })

      // Show notification
      showNotification("Car added to cart!")
    })
  })
}

function addToCart(item) {
  // Get current cart items from localStorage
  const cartItems = JSON.parse(localStorage.getItem("cartItems")) || []

  // Check if item already in cart
  const existingItem = cartItems.find((cartItem) => cartItem.id === item.id)
  if (!existingItem) {
    // Add to cart
    cartItems.push(item)
    localStorage.setItem("cartItems", JSON.stringify(cartItems))

    // Update cart count
    updateCartCount(cartItems.length)
  } else {
    showNotification("This car is already in your cart!")
  }
}

function updateCartCount(count) {
  const cartCount = document.getElementById("cart-count")
  if (cartCount) {
    cartCount.textContent = count
    if (count > 0) {
      cartCount.style.display = "inline-block"
    } else {
      cartCount.style.display = "none"
    }
  }
}

function loadCartItems() {
  const cartItems = JSON.parse(localStorage.getItem("cartItems")) || []
  updateCartCount(cartItems.length)
}

function showNotification(message) {
  // Create notification element
  const notification = document.createElement("div")
  notification.className = "alert alert-success position-fixed"
  notification.style.bottom = "20px"
  notification.style.right = "20px"
  notification.style.zIndex = "9999"
  notification.style.padding = "10px 20px"
  notification.style.borderRadius = "5px"
  notification.style.boxShadow = "0 4px 8px rgba(0,0,0,0.1)"
  notification.textContent = message

  // Add to document
  document.body.appendChild(notification)

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.opacity = "0"
    notification.style.transition = "opacity 0.5s"
    setTimeout(() => {
      notification.remove()
    }, 500)
  }, 3000)
}
