// assets/cyber_app.js - Main application JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Matrix rain effect
  createMatrixRain();

  // Glitch effect for text
  initGlitchEffect();

  // Smooth scrolling
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({ behavior: "smooth" });
      }
    });
  });
});

// Page transition animations
function createPageTransition(targetElement, callback) {
  // Create page transition overlay
  const overlay = document.createElement("div");
  overlay.className = "page-transition";
  overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #1f2937;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  `;

  // Create loading content
  const loadingContent = document.createElement("div");
  loadingContent.className = "page-content";
  loadingContent.innerHTML = `
    <div class="loading-spinner"></div>
    <div class="loading-text">Loading...</div>
  `;

  overlay.appendChild(loadingContent);
  document.body.appendChild(overlay);

  // Fade in
  setTimeout(() => {
    overlay.style.opacity = "1";
  }, 10);

  // Handle transition completion
  function completeTransition() {
    setTimeout(() => {
      overlay.style.opacity = "0";

      setTimeout(() => {
        document.body.removeChild(overlay);
        if (callback) callback();
      }, 300);
    }, 500);
  }

  // If navigating to a new page
  if (typeof targetElement === "string") {
    // Load new page
    setTimeout(() => {
      fetch(targetElement)
        .then((response) => response.text())
        .then((html) => {
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = html;

          // Extract main content
          const newContent = tempDiv.querySelector("main");
          if (newContent) {
            document.querySelector("main").replaceWith(newContent);
          }

          completeTransition();
        })
        .catch((error) => {
          console.error("Error loading page:", error);
          completeTransition();
        });
    }, 1000);
  } else {
    // Replace existing element
    setTimeout(() => {
      const existingContent = document.querySelector(targetElement);
      if (existingContent) {
        const newContent = existingContent.cloneNode(true);

        // Add transition effect
        existingContent.style.opacity = "0";
        existingContent.style.transform = "translateX(-20px)";

        setTimeout(() => {
          existingContent.parentNode.replaceChild(newContent, existingContent);

          newContent.style.opacity = "0";
          newContent.style.transform = "translateX(20px)";

          setTimeout(() => {
            newContent.style.opacity = "1";
            newContent.style.transform = "translateX(0)";
            completeTransition();
          }, 50);
        }, 50);
      }
    }, 500);
  }
}

// Apply page transitions to navigation
document.querySelectorAll("a[href]").forEach((link) => {
  if (!link.getAttribute("href").startsWith("#")) {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      createPageTransition(link.getAttribute("href"));
    });
  }
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      createPageTransition(target, () => {
        target.scrollIntoView({ behavior: "smooth" });
      });
    }
  });
});

function createMatrixRain() {
  const canvas = document.createElement("canvas");
  canvas.id = "matrixCanvas";
  canvas.style.position = "fixed";
  canvas.style.top = "0";
  canvas.style.left = "0";
  canvas.style.width = "100%";
  canvas.style.height = "100%";
  canvas.style.pointerEvents = "none";
  canvas.style.zIndex = "0";
  canvas.style.opacity = "0.1";

  const matrixBg = document.querySelector(".matrix-bg");
  if (matrixBg) {
    matrixBg.appendChild(canvas);
  }

  const ctx = canvas.getContext("2d");
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()";
  const fontSize = 14;
  const columns = canvas.width / fontSize;
  const drops = Array(Math.floor(columns)).fill(1);

  function draw() {
    ctx.fillStyle = "rgba(0, 0, 0, 0.05)";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    ctx.fillStyle = "#00d4ff";
    ctx.font = fontSize + "px monospace";

    for (let i = 0; i < drops.length; i++) {
      const text = chars[Math.floor(Math.random() * chars.length)];
      ctx.fillText(text, i * fontSize, drops[i] * fontSize);

      if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
        drops[i] = 0;
      }
      drops[i]++;
    }
  }

  setInterval(draw, 33);

  window.addEventListener("resize", () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  });
}

// Enhanced glitch effect
function initGlitchEffect() {
  const glitchElements = document.querySelectorAll(".glitch-text");

  glitchElements.forEach((element) => {
    element.addEventListener("mouseenter", () => {
      element.classList.add("glitching");

      // Add random color change
      const originalColor = window.getComputedStyle(element).color;
      const colors = ["#ff0000", "#00ff00", "#0000ff", "#ff00ff"];
      const randomColor = colors[Math.floor(Math.random() * colors.length)];

      element.style.color = randomColor;
      element.style.textShadow = `
        2px 2px 0 ${randomColor},
        -2px -2px 0 ${randomColor}
      `;

      setTimeout(() => {
        element.classList.remove("glitching");
        element.style.color = originalColor;
        element.style.textShadow = "";
      }, 800);
    });

    // Periodic glitch on scroll
    let glitchTimeout;
    window.addEventListener("scroll", () => {
      clearTimeout(glitchTimeout);

      const random = Math.random();
      if (random > 0.95) {
        element.classList.add("glitching");
        glitchTimeout = setTimeout(() => {
          element.classList.remove("glitching");
        }, 200);
      }
    });
  });
}

// Loading animation for quiz and scenarios
function showLoadingAnimation(container, message = "Loading...") {
  const loadingOverlay = document.createElement("div");
  loadingOverlay.className = "loading-overlay";
  loadingOverlay.innerHTML = `
    <div class="loading-spinner"></div>
    <div class="loading-message">${message}</div>
  `;

  container.appendChild(loadingOverlay);
}

function hideLoadingAnimation(container) {
  const loadingOverlay = container.querySelector(".loading-overlay");
  if (loadingOverlay) {
    loadingOverlay.style.opacity = "0";

    setTimeout(() => {
      container.removeChild(loadingOverlay);
    }, 300);
  }
}

// Success animation for completed challenges
function showSuccessAnimation(element, message) {
  element.classList.add("success-animation");

  // Create success notification
  const notification = document.createElement("div");
  notification.className = "success-notification";
  notification.innerHTML = `
    <div class="success-icon">✓</div>
    <div class="success-message">${message}</div>
  `;

  document.body.appendChild(notification);

  // Animate in
  setTimeout(() => {
    notification.classList.add("show");
  }, 10);

  // Remove after delay
  setTimeout(() => {
    notification.classList.remove("show");

    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

// Enhanced notification system
function showNotification(message, type = "info", options = {}) {
  const notification = document.createElement("div");
  notification.className = `cyber-notification ${type}`;

  // Add custom content
  const icon =
    options.icon ||
    (type === "success"
      ? "✓"
      : type === "error"
        ? "✗"
        : type === "warning"
          ? "⚠"
          : "ℹ");
  const title =
    options.title ||
    (type === "success"
      ? "Success!"
      : type === "error"
        ? "Error!"
        : type === "warning"
          ? "Warning!"
          : "Information");
  const autoHide = options.autoHide !== false;
  const duration = options.duration || 3000;

  notification.innerHTML = `
    <div class="notif-icon">${icon}</div>
    <div class="notif-content">
      <div class="notif-title">${title}</div>
      <div class="notif-text">${message}</div>
      ${options.action ? `<div class="notif-action">${options.action}</div>` : ""}
    </div>
    ${options.closeable !== false ? '<button class="notif-close">×</button>' : ""}
  `;

  // Add close functionality
  if (options.closeable !== false) {
    const closeBtn = notification.querySelector(".notif-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => {
        hideNotification(notification);
      });
    }
  }

  document.body.appendChild(notification);

  // Animate in
  setTimeout(() => {
    notification.classList.add("show");
  }, 10);

  // Auto hide
  if (autoHide) {
    setTimeout(() => {
      hideNotification(notification);
    }, duration);
  }

  // Return notification element for manual control
  return notification;
}

function hideNotification(notification) {
  notification.classList.add("hiding");

  setTimeout(() => {
    notification.classList.remove("hiding");

    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 200);
}

// XP animation
function animateXP(element, start, end, duration = 1000) {
  const range = end - start;
  const increment = range / (duration / 16);
  let current = start;

  const timer = setInterval(() => {
    current += increment;
    if (
      (increment > 0 && current >= end) ||
      (increment < 0 && current <= end)
    ) {
      current = end;
      clearInterval(timer);
    }
    element.textContent = Math.floor(current);
  }, 16);
}

// XP animation
function animateXP(element, start, end, duration = 1000) {
  const range = end - start;
  const increment = range / (duration / 16);
  let current = start;

  const timer = setInterval(() => {
    current += increment;
    if (
      (increment > 0 && current >= end) ||
      (increment < 0 && current <= end)
    ) {
      current = end;
      clearInterval(timer);
    }
    element.textContent = Math.floor(current);
  }, 16);
}
