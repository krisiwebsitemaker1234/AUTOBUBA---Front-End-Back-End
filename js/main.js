;(($) => {
    /*------------------
          Preloader
      --------------------*/
    $(window).on("load", () => {
      $(".loader").fadeOut()
      $("#preloder").delay(200).fadeOut("slow")
  
      /*------------------
              Car filter
          --------------------*/
      $(".filter__controls li").on("click", function () {
        $(".filter__controls li").removeClass("active")
        $(this).addClass("active")
      })
      if ($(".car-filter").length > 0) {
        var containerEl = document.querySelector(".car-filter")
        var mixer = mixitup(containerEl)
      }
    })
  
    /*------------------
          Background Set
      --------------------*/
    $(".set-bg").each(function () {
      var bg = $(this).data("setbg")
      $(this).css("background-image", "url(" + bg + ")")
    })
  
    //Canvas Menu
    $(".canvas__open").on("click", () => {
      $(".offcanvas-menu-wrapper").addClass("active")
      $(".offcanvas-menu-overlay").addClass("active")
    })
  
    $(".offcanvas-menu-overlay").on("click", () => {
      $(".offcanvas-menu-wrapper").removeClass("active")
      $(".offcanvas-menu-overlay").removeClass("active")
    })
  
    //Search Switch
    $(".search-switch").on("click", () => {
      $(".search-model").fadeIn(400)
    })
  
    $(".search-close-switch").on("click", () => {
      $(".search-model").fadeOut(400, () => {
        $("#search-input").val("")
      })
    })
  
    /*------------------
          Navigation
      --------------------*/
    $(".header__menu").slicknav({
      prependTo: "#mobile-menu-wrap",
      allowParentLinks: true,
    })
  
    $(document).ready(() => {
      $(".header__menu").slicknav({
        prependTo: ".canvas__open",
        allowParentLinks: true,
      })
    })
  
    /*--------------------------
          Testimonial Slider
      ----------------------------*/
    $(".car__item__pic__slider").owlCarousel({
      loop: true,
      margin: 0,
      items: 1,
      dots: true,
      smartSpeed: 1200,
      autoHeight: false,
      autoplay: false,
    })
  
    /*--------------------------
          Testimonial Slider
      ----------------------------*/
    var testimonialSlider = $(".testimonial__slider")
    testimonialSlider.owlCarousel({
      loop: true,
      margin: 0,
      items: 2,
      dots: true,
      nav: true,
      navText: ["<i class='fa fa-angle-left'></i>", "<i class='fa fa-angle-right'></i>"],
      smartSpeed: 1200,
      autoHeight: false,
      autoplay: false,
      responsive: {
        768: {
          items: 2,
        },
        0: {
          items: 1,
        },
      },
    })
  
    /*-----------------------------
          Car thumb Slider
      -------------------------------*/
    $(".car__thumb__slider").owlCarousel({
      loop: true,
      margin: 25,
      items: 5,
      dots: false,
      smartSpeed: 1200,
      autoHeight: false,
      autoplay: true,
      mouseDrag: false,
      responsive: {
        768: {
          items: 5,
        },
        320: {
          items: 3,
        },
        0: {
          items: 2,
        },
      },
    })
  
    /*-----------------------
          Range Slider
      ------------------------ */
  
    // Function to initialize a slider
    function initSlider(selector, inputId, min, max, defaultValues) {
      $(selector).slider({
        range: true,
        min: min,
        max: max,
        values: defaultValues,
        slide: (event, ui) => {
          $(inputId).val("$" + ui.values[0].toLocaleString() + " - $" + ui.values[1].toLocaleString())
        },
      })
      $(inputId).val(
        "$" +
          $(selector).slider("values", 0).toLocaleString() +
          " - $" +
          $(selector).slider("values", 1).toLocaleString(),
      )
    }
  
    // Initialize all sliders
    $(document).ready(() => {
      // Rent A Car Sliders
      initSlider(".price-range", "#amount", 1, 600000, [80000, 320000])
      initSlider(".car-price-range", "#caramount", 1, 600000, [80000, 320000])
      initSlider(".filter-price-range", "#filterAmount", 1, 500000, [100000, 300000])
  
      // Buy A Car Slider
      initSlider(".buy-price-range", "#buyAmount", 1000, 500000, [10000, 200000])
    })
  
    /*--------------------------
          Select
      ----------------------------*/
    $("select").niceSelect()
  
    /*------------------
          Magnific
      --------------------*/
    $(".video-popup").magnificPopup({
      type: "iframe",
    })
  
    /*------------------
          Single Product
      --------------------*/
    $(".car-thumbs-track .ct").on("click", function () {
      $(".car-thumbs-track .ct").removeClass("active")
      var imgurl = $(this).data("imgbigurl")
      var bigImg = $(".car-big-img").attr("src")
      if (imgurl != bigImg) {
        $(".car-big-img").attr({
          src: imgurl,
        })
      }
    })
  
    /*------------------
          Counter Up
      --------------------*/
    $(".counter-num").each(function () {
      $(this)
        .prop("Counter", 0)
        .animate(
          {
            Counter: $(this).text(),
          },
          {
            duration: 4000,
            easing: "swing",
            step: function (now) {
              $(this).text(Math.ceil(now))
            },
          },
        )
    })
  })
  
  // Add smooth scrolling to all links with href starting with "#"
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault() // Prevent default anchor click behavior
      const targetId = link.getAttribute("href").substring(1) // Get target section ID
      const targetElement = document.getElementById(targetId) // Locate the target element by ID
      if (targetElement) {
        // Use scrollIntoView for smooth scrolling
        targetElement.scrollIntoView({
          behavior: "smooth", // Enables smooth scrolling animation
          block: "start", // Aligns the target section to the top
        })
      }
    })
  })

  
  // Phone number to display
  const phoneNumber = "+69 69 690 6969";
    
  // Get elements
  const phoneLogo = document.getElementById('phoneLogo');
  const contactModal = document.getElementById('contactModal');
  const closeModal = document.getElementById('closeModal');
  const phoneNumberElement = document.getElementById('phoneNumber');
  const modalCopyButton = document.getElementById('modalCopyButton');
  const modalTooltip = document.getElementById('modalTooltip');
  
  // Set the phone number
  phoneNumberElement.textContent = phoneNumber;
  phoneNumberElement.href = `tel:${phoneNumber}`;
  
  // Function to copy text to clipboard
  function copyToClipboard(text, tooltip) {
    navigator.clipboard.writeText(text).then(() => {
      tooltip.classList.add('show');
      setTimeout(() => {
        tooltip.classList.remove('show');
      }, 1500);
    }).catch(err => {
      console.error('Could not copy text: ', err);
    });
  }
  
  // Show modal when logo is clicked
  phoneLogo.addEventListener('click', function() {
    contactModal.style.display = 'flex';
  });
  
  // Copy from modal
  modalCopyButton.addEventListener('click', function() {
    copyToClipboard(phoneNumber, modalTooltip);
  });
  
  // Close modal when close button is clicked
  closeModal.addEventListener('click', function() {
    contactModal.style.display = 'none';
  });
  
  // Close modal when clicking outside the modal content
  contactModal.addEventListener('click', function(event) {
    if (event.target === contactModal) {
      contactModal.style.display = 'none';
    }
  });


  