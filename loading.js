document.addEventListener("DOMContentLoaded", () => {
    // Loading screen functionality
    const loadingScreen = document.getElementById("loading-screen");
    const pageContent = document.getElementById("page-content");
    const progressBar = document.getElementById("progress-bar");
    const needleContainer = document.querySelector(".needle-container");
    const speedValue = document.getElementById("speed-value");
  
    // Set total loading time to 1.5 seconds
    const totalLoadingTime = 1500; // 1.5 seconds in milliseconds
    const intervalTime = 50; // Update every 50ms
    const totalSteps = totalLoadingTime / intervalTime;
  
    // Simulate loading progress
    let progress = 0;
    let step = 0;
  
    const loadingInterval = setInterval(() => {
      step++;
      progress = Math.min((step / totalSteps) * 100, 100); // Ensure progress doesn't exceed 100%
  
      // Update progress bar
      progressBar.style.width = `${progress}%`;
  
      // Update speedometer needle position
      // -120 degrees is 0%, 120 degrees is 100%
      const needleRotation = -120 + progress * 2.4; // 240 degree total rotation
      needleContainer.style.transform = `rotate(${needleRotation}deg)`;
  
      // Update speed value (0-200 km/h)
      const speed = Math.round(progress * 2);
      speedValue.textContent = speed;
  
      // Check if loading is complete
      if (progress >= 100 || step >= totalSteps) {
        clearInterval(loadingInterval);
  
        // Delay hiding the loading screen slightly
        setTimeout(() => {
          // Hide loading screen
          loadingScreen.style.opacity = "0";
          loadingScreen.style.visibility = "hidden";
  
          // Show page content
          pageContent.classList.remove("hidden");
        }, 200); // 200ms delay
      }
    }, intervalTime);
  
    // Add animation delay to loading text
    const loadingTextSpans = document.querySelectorAll(".loading-text span");
    loadingTextSpans.forEach((span, index) => {
      span.style.setProperty("--i", index + 1);
    });
  });