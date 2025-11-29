// assets/training_app.js - Training modules functionality

let currentCategory = null;
let currentModule = null;
let modules = [];

document.addEventListener("DOMContentLoaded", () => {
  initTrainingMode();
});

function initTrainingMode() {
  // Back buttons
  document.getElementById("backToModules")?.addEventListener("click", () => {
    if (currentCategory) {
      loadModules(currentCategory);
    }
  });
}

async function loadModules(categoryId) {
  try {
    const response = await fetch(
      `api/get_modules.php?category_id=${categoryId}`,
    );
    const data = await response.json();

    if (data.error) {
      showNotification(data.error, "error");
      return;
    }

    currentCategory = categoryId;
    modules = data.modules;

    document.getElementById("categoryList").classList.add("hidden");
    document.getElementById("moduleList").classList.remove("hidden");
    document.getElementById("categoryTitle").textContent =
      data.category_name + " MODULES";

    const container = document.getElementById("modulesContainer");
    container.innerHTML = "";

    modules.forEach((module, index) => {
      const moduleCard = document.createElement("div");
      moduleCard.className = "module-card";
      moduleCard.innerHTML = `
                <div class="module-number">${index + 1}</div>
                <div class="module-info">
                    <h3 class="module-card-title">${module.title}</h3>
                    <div class="module-meta">
                        <span class="meta-item">⏱️ ${module.duration_minutes} min</span>
                        <span class="meta-item">⚡ +${module.xp_reward} XP</span>
                    </div>
                </div>
                <button class="cyber-btn small" onclick="viewModule(${module.id})">START</button>
            `;
      container.appendChild(moduleCard);
    });
  } catch (error) {
    console.error("Error loading modules:", error);
    showNotification("Error loading modules", "error");
  }
}

async function viewModule(moduleId) {
  try {
    const response = await fetch(`api/get_module.php?id=${moduleId}`);
    const data = await response.json();

    if (data.error) {
      showNotification(data.error, "error");
      return;
    }

    currentModule = data;

    document.getElementById("moduleList").classList.add("hidden");
    document.getElementById("moduleView").classList.remove("hidden");

    document.getElementById("moduleTitle").textContent = currentModule.title;
    document.getElementById("moduleDuration").textContent =
      currentModule.duration_minutes;
    document.getElementById("moduleXP").textContent = currentModule.xp_reward;

    // Format content with line breaks
    const formattedContent = currentModule.content.replace(/\n/g, "<br>");
    document.getElementById("moduleContent").innerHTML =
      `<div class="module-text">${formattedContent}</div>`;

    document.getElementById("completeModule").onclick = () => completeModule();
  } catch (error) {
    console.error("Error loading module:", error);
    showNotification("Error loading module", "error");
  }
}

async function completeModule() {
  try {
    const formData = new FormData();
    formData.append("module_id", currentModule.id);
    formData.append("category_id", currentCategory);

    const response = await fetch("api/complete_module.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.error) {
      showNotification(data.error, "error");
      return;
    }

    document.getElementById("moduleView").classList.add("hidden");
    document.getElementById("moduleComplete").classList.remove("hidden");

    document.getElementById("earnedXP").textContent = currentModule.xp_reward;
    animateXP(document.getElementById("earnedXP"), 0, currentModule.xp_reward);

    // Find next module
    const currentIndex = modules.findIndex((m) => m.id === currentModule.id);
    const nextModule = modules[currentIndex + 1];

    if (nextModule) {
      document.getElementById("nextModule").onclick = () => {
        document.getElementById("moduleComplete").classList.add("hidden");
        viewModule(nextModule.id);
      };
    } else {
      document.getElementById("nextModule").textContent = "BACK TO MODULES";
      document.getElementById("nextModule").onclick = () => {
        showCategories();
      };
    }
  } catch (error) {
    console.error("Error completing module:", error);
    showNotification("Error completing module", "error");
  }
}

function showCategories() {
  document.getElementById("moduleList").classList.add("hidden");
  document.getElementById("moduleView").classList.add("hidden");
  document.getElementById("moduleComplete").classList.add("hidden");
  document.getElementById("categoryList").classList.remove("hidden");
}

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

function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `cyber-notification ${type}`;
  notification.innerHTML = `
        <span class="notif-icon">${type === "success" ? "✓" : type === "error" ? "✗" : "ℹ"}</span>
        <span class="notif-text">${message}</span>
    `;

  document.body.appendChild(notification);

  setTimeout(() => notification.classList.add("show"), 10);
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}
