document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM fully loaded - initializing hero functionality")
  
    // Set background image for hero section
    const heroSection = document.querySelector(".hero")
    if (heroSection) {
      const bgImage = heroSection.getAttribute("data-setbg")
      if (bgImage) {
        heroSection.style.backgroundImage = `url(${bgImage})`
      }
    }
  
    // Initialize price range sliders
    initPriceRangeSliders()
  
    // Handle form submissions
    setupFormSubmissions()
  
    // Setup tab functionality if not already handled by Bootstrap
    setupTabs()
  
    // Ensure we have a working modal
    ensureModalExists()
  })
  
  /**
   * Make sure we have a working modal in the DOM
   */
  function ensureModalExists() {
    console.log("Ensuring search modal exists")
  
    // Check if modal already exists
    let searchModal = document.getElementById("searchModal")
  
    // If modal doesn't exist, create it
    if (!searchModal) {
      console.log("Search modal not found - creating one")
      searchModal = document.createElement("div")
      searchModal.id = "searchModal"
      searchModal.className = "search-modal"
  
      searchModal.innerHTML = `
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Search Results</h5>
              <button type="button" class="close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div id="searchResults"></div>
            </div>
          </div>
        </div>
      `
  
      document.body.appendChild(searchModal)
    } else {
      console.log("Existing search modal found")
    }
  
    // Apply base styles to ensure modal works
    const modalStyle = document.createElement("style")
    modalStyle.textContent = `
      .search-modal {
        display: none;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
        z-index: 9999 !important;
        overflow: auto !important;
      }
      
      .search-modal .modal-dialog {
        position: relative !important;
        width: auto !important;
        max-width: 600px !important;
        margin: 10% auto !important;
      }
      
      .search-modal .modal-content {
        position: relative !important;
        background-color: #fff !important;
        border-radius: 5px !important;
        box-shadow: 0 5px 15px rgba(0,0,0,.5) !important;
      }
      
      .search-modal .modal-header {
        padding: 15px !important;
        border-bottom: 1px solid #e5e5e5 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
      }
      
      .search-modal .modal-body {
        padding: 15px !important;
      }
      
      .search-modal .close {
        cursor: pointer !important;
        background: none !important;
        border: none !important;
        font-size: 1.5rem !important;
      }
      
      .car-result {
        display: flex !important;
        margin-bottom: 15px !important;
        border-bottom: 1px solid #eee !important;
        padding-bottom: 15px !important;
      }
      
      .car-result-image {
        width: 120px !important;
        height: 80px !important;
        object-fit: cover !important;
        margin-right: 15px !important;
      }
      
      .car-result-details {
        flex: 1 !important;
      }
      
      .car-result-actions {
        margin-top: 10px !important;
      }
    `
  
    document.head.appendChild(modalStyle)
  
    // Add event listeners to close button
    const closeButtons = searchModal.querySelectorAll('.close, [data-dismiss="modal"]')
    closeButtons.forEach((button) => {
      button.addEventListener("click", () => {
        console.log("Close button clicked")
        hideModal()
      })
    })
  
    // Close modal when clicking outside of modal content
    searchModal.addEventListener("click", (event) => {
      if (event.target === searchModal) {
        console.log("Clicked outside modal content")
        hideModal()
      }
    })
  
    // Close modal when pressing ESC key
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && searchModal.style.display === "block") {
        console.log("ESC key pressed")
        hideModal()
      }
    })
  }
  
  /**
   * Show the modal
   */
  function showModal() {
    console.log("Showing modal")
  
    const searchModal = document.getElementById("searchModal")
    if (!searchModal) {
      console.error("Cannot show modal - element not found")
      return
    }
  
    // Force display block with !important
    searchModal.style.cssText = `
      display: block !important;
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: 100% !important;
      background-color: rgba(0, 0, 0, 0.5) !important;
      z-index: 9999 !important;
      overflow: auto !important;
    `
  
    // Prevent body scrolling
    document.body.style.overflow = "hidden"
    document.body.style.paddingRight = "15px" // Compensate for scrollbar
  
    console.log("Modal should now be visible")
  }
  
  /**
   * Hide the modal
   */
  function hideModal() {
    console.log("Hiding modal")
  
    const searchModal = document.getElementById("searchModal")
    if (!searchModal) {
      console.error("Cannot hide modal - element not found")
      return
    }
  
    searchModal.style.display = "none"
    document.body.style.overflow = ""
    document.body.style.paddingRight = ""
  
    console.log("Modal should now be hidden")
  }
  
  /**
   * Initialize price range sliders for both rental and buy forms
   */
  function initPriceRangeSliders() {
    console.log("Initializing price range sliders")
  
    // Check if jQuery and jQuery UI are available
    if (typeof jQuery !== "undefined" && typeof jQuery.fn.slider !== "undefined") {
      try {
        // Rental form price slider
        jQuery(".price-range").slider({
          range: true,
          min: 1,
          max: 600000,
          values: [80000, 320000],
          slide: (event, ui) => {
            jQuery("#amount").val("$" + ui.values[0].toLocaleString() + " - $" + ui.values[1].toLocaleString())
          },
        })
        jQuery("#amount").val(
          "$" +
            jQuery(".price-range").slider("values", 0).toLocaleString() +
            " - $" +
            jQuery(".price-range").slider("values", 1).toLocaleString(),
        )
  
        // Buy form price slider
        jQuery(".buy-price-range").slider({
          range: true,
          min: 1000,
          max: 500000,
          values: [10000, 200000],
          slide: (event, ui) => {
            jQuery("#buyAmount").val("$" + ui.values[0].toLocaleString() + " - $" + ui.values[1].toLocaleString())
          },
        })
        jQuery("#buyAmount").val(
          "$" +
            jQuery(".buy-price-range").slider("values", 0).toLocaleString() +
            " - $" +
            jQuery(".buy-price-range").slider("values", 1).toLocaleString(),
        )
      } catch (error) {
        console.error("Error initializing price sliders:", error)
      }
    } else {
      console.warn("jQuery UI slider not available. Price range sliders will not function.")
    }
  }
  
  /**
   * Setup form submissions for both rental and buy forms
   */
  function setupFormSubmissions() {
    console.log("Setting up form submissions")
  
    // Sample car data (replace with your actual data or API call)
    const carData = [
      { brand: "Acura", model: "MDX", year: 2020, mileage: 27, price: 45000, type: "rent", image: "img/cars/car-1.jpg" },
      { brand: "Audi", model: "Q3", year: 2019, mileage: 25, price: 38000, type: "rent", image: "img/cars/car-2.jpg" },
      { brand: "BMW", model: "X5", year: 2018, mileage: 15, price: 42000, type: "buy", image: "img/cars/car-3.jpg" },
      {
        brand: "Bentley",
        model: "Continental",
        year: 2020,
        mileage: 10,
        price: 180000,
        type: "buy",
        image: "img/cars/car-4.jpg",
      },
      {
        brand: "Bugatti",
        model: "Chiron",
        year: 2019,
        mileage: 10,
        price: 2500000,
        type: "buy",
        image: "img/cars/car-5.jpg",
      },
      { brand: "Audi", model: "A4", year: 2017, mileage: 25, price: 32000, type: "rent", image: "img/cars/car-6.jpg" },
    ]
  
    // Rental form submission
    const rentalForm = document.getElementById("rentalForm")
    if (rentalForm) {
      rentalForm.addEventListener("submit", (e) => {
        e.preventDefault()
        console.log("Rental form submitted")
  
        try {
          const year = document.getElementById("rentYear")?.value || ""
          const brand = document.getElementById("rentBrand")?.value || ""
          const model = document.getElementById("rentModel")?.value || ""
          const mileage = document.getElementById("rentMileage")?.value || ""
          const priceRange = document.getElementById("amount")?.value || ""
  
          console.log("Search criteria:", { year, brand, model, mileage, priceRange, type: "rent" })
  
          const results = searchCars(carData, year, brand, model, mileage, priceRange, "rent")
          console.log("Search results:", results)
  
          displaySearchResults(results)
        } catch (error) {
          console.error("Error processing rental form:", error)
          alert("There was an error processing your search. Please try again.")
        }
      })
    } else {
      console.warn("Rental form not found")
    }
  
    // Buy form submission
    const buyForm = document.getElementById("buyForm")
    if (buyForm) {
      buyForm.addEventListener("submit", (e) => {
        e.preventDefault()
        console.log("Buy form submitted")
  
        try {
          const year = document.getElementById("buyYear")?.value || ""
          const brand = document.getElementById("buyBrand")?.value || ""
          const model = document.getElementById("buyModel")?.value || ""
          const mileage = document.getElementById("buyMileage")?.value || ""
          const priceRange = document.getElementById("buyAmount")?.value || ""
  
          console.log("Search criteria:", { year, brand, model, mileage, priceRange, type: "buy" })
  
          const results = searchCars(carData, year, brand, model, mileage, priceRange, "buy")
          console.log("Search results:", results)
  
          displaySearchResults(results)
        } catch (error) {
          console.error("Error processing buy form:", error)
          alert("There was an error processing your search. Please try again.")
        }
      })
    } else {
      console.warn("Buy form not found")
    }
  }
  
  /**
   * Search cars based on form criteria
   */
  function searchCars(cars, year, brand, model, mileage, priceRange, type) {
    console.log("Searching cars with criteria:", { year, brand, model, mileage, priceRange, type })
  
    // Parse price range
    let minPrice = 0
    let maxPrice = Number.MAX_SAFE_INTEGER
  
    if (priceRange) {
      const priceMatch = priceRange.match(/\$([0-9,]+) - \$([0-9,]+)/)
      if (priceMatch) {
        minPrice = Number.parseInt(priceMatch[1].replace(/,/g, ""))
        maxPrice = Number.parseInt(priceMatch[2].replace(/,/g, ""))
      }
    }
  
    console.log("Price range:", { minPrice, maxPrice })
  
    // Filter cars based on criteria
    return cars.filter((car) => {
      if (type && car.type !== type) return false
      if (year && year !== "" && car.year.toString() !== year) return false
      if (brand && brand !== "" && car.brand !== brand) return false
      if (model && model !== "" && car.model !== model) return false
      if (mileage && mileage !== "" && car.mileage.toString() !== mileage) return false
      if (car.price < minPrice || car.price > maxPrice) return false
  
      return true
    })
  }
  
  /**
   * Display search results in the modal
   */
  function displaySearchResults(results) {
    console.log("Displaying search results:", results)
  
    const searchResults = document.getElementById("searchResults")
  
    if (!searchResults) {
      console.error("Search results container not found")
      return
    }
  
    // Clear previous results
    searchResults.innerHTML = ""
  
    if (results.length === 0) {
      console.log("No results found")
      searchResults.innerHTML = `
        <div class="no-results text-center py-4">
          <p>No results found. Try different search criteria.</p>
        </div>
      `
    } else {
      console.log(`Found ${results.length} results`)
      results.forEach((car) => {
        const resultItem = document.createElement("div")
        resultItem.className = "car-result"
  
        resultItem.innerHTML = `
          <img src="${car.image}" alt="${car.brand} ${car.model}" class="car-result-image">
          <div class="car-result-details">
            <h3>${car.brand} ${car.model}</h3>
            <p><strong>Year:</strong> ${car.year}</p>
            <p><strong>Mileage:</strong> ${car.mileage} mpg</p>
            <p class="price"><strong>Price:</strong> $${car.price.toLocaleString()}</p>
            <div class="car-result-actions">
              <button class="add-to-cart-btn btn btn-sm btn-outline-danger">
                <i class="fa fa-cart-plus"></i> Add to Cart
              </button>
              <button class="test-drive-btn btn btn-sm btn-outline-danger ml-2">
                <i class="fa fa-car"></i> Test Drive
              </button>
            </div>
          </div>
        `
  
        searchResults.appendChild(resultItem)
      })
    }
  
    // Show the modal
    showModal()
  
    // Add event listeners to the new buttons
    setupResultActions()
  }
  
  /**
   * Setup actions for search result items (add to cart, test drive)
   */
  function setupResultActions() {
    console.log("Setting up result actions")
  
    // Add to cart functionality
    document.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
        const carName = this.closest(".car-result").querySelector("h3").textContent
        console.log("Add to cart clicked for:", carName)
        addToCart(carName)
      })
    })
  
    // Test drive functionality
    document.querySelectorAll(".test-drive-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
        const carName = this.closest(".car-result").querySelector("h3").textContent
        console.log("Test drive clicked for:", carName)
        window.location.href = "Book a Test Drive.html?car=" + encodeURIComponent(carName)
      })
    })
  }
  
  /**
   * Add a car to the cart
   */
  function addToCart(carName) {
    console.log("Adding to cart:", carName)
  
    // Get existing cart items from localStorage
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || []
  
    // Check if car is already in cart
    const existingItem = cartItems.find((item) => item.name === carName)
  
    if (!existingItem) {
      // Add new item to cart
      cartItems.push({
        id: Date.now(),
        name: carName,
        price: "Price on request", // You might want to pass the actual price
        image: "img/cars/car-1.jpg", // You might want to pass the actual image
      })
  
      // Save updated cart to localStorage
      localStorage.setItem("cartItems", JSON.stringify(cartItems))
  
      // Show notification
      showNotification("Car added to cart!")
  
      // Update cart counter
      updateCartCounter(cartItems.length)
    } else {
      showNotification("This car is already in your cart!")
    }
  }
  
  /**
   * Show a notification message
   */
  function showNotification(message) {
    console.log("Showing notification:", message)
  
    const notification = document.createElement("div")
    notification.className = "notification alert alert-success"
    notification.textContent = message
    notification.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 9999;
      padding: 10px 20px;
      border-radius: 4px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    `
  
    document.body.appendChild(notification)
  
    setTimeout(() => {
      notification.style.opacity = "0"
      notification.style.transition = "opacity 0.3s ease"
      setTimeout(() => {
        notification.remove()
      }, 300)
    }, 3000)
  }
  
  /**
   * Update the cart counter display
   */
  function updateCartCounter(count) {
    console.log("Updating cart counter:", count)
  
    const cartCounter = document.getElementById("cart-count")
    if (cartCounter) {
      cartCounter.textContent = count
      cartCounter.style.display = count > 0 ? "block" : "none"
    } else {
      console.warn("Cart counter element not found")
    }
  }
  
  /**
   * Setup tab functionality if not already handled by Bootstrap
   */
  function setupTabs() {
    console.log("Setting up tabs")
  
    const tabLinks = document.querySelectorAll(".hero__tab .nav-tabs .nav-link")
    const tabContents = document.querySelectorAll(".hero__tab .tab-content .tab-pane")
  
    // If Bootstrap is handling tabs, this won't be necessary
    // This is a fallback in case Bootstrap JS is not loaded
    tabLinks.forEach((link) => {
      link.addEventListener("click", function (e) {
        console.log("Tab clicked:", this.getAttribute("href"))
  
        // If Bootstrap is handling tabs, let it do its thing
        if (typeof jQuery !== "undefined" && typeof jQuery.fn.tab !== "undefined") {
          return
        }
  
        // Otherwise, handle tab switching manually
        e.preventDefault()
  
        // Remove active class from all tabs
        tabLinks.forEach((tab) => tab.classList.remove("active"))
        tabContents.forEach((content) => content.classList.remove("active", "show"))
  
        // Add active class to clicked tab
        this.classList.add("active")
  
        // Get the target tab content
        const target = this.getAttribute("href")
        const targetContent = document.querySelector(target)
        if (targetContent) {
          targetContent.classList.add("active", "show")
        }
      })
    })
  }
  
  // Fix for undeclared $ variable
  var $ = jQuery
  
  