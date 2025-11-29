// assets/scenario_app.js - Scenario mode functionality

let currentScenario = null;
let currentStepIndex = 0;
let scenarioSteps = [];
let correctChoices = 0;
let totalChoices = 0;

document.addEventListener("DOMContentLoaded", () => {
  initScenarioMode();
});

function initScenarioMode() {
  document
    .getElementById("quitScenario")
    .addEventListener("click", quitScenario);
}

async function startScenario(scenarioId) {
  try {
    const response = await fetch(`api/get_scenario.php?id=${scenarioId}`);
    const data = await response.json();

    if (data.error) {
      showNotification(data.error, "error");
      return;
    }

    currentScenario = data.scenario;
    scenarioSteps = data.steps;
    currentStepIndex = 0;
    correctChoices = 0;
    totalChoices = 0;

    document.getElementById("scenarioList").classList.add("hidden");
    document.getElementById("scenarioPlay").classList.remove("hidden");

    document.getElementById("scenarioTitle").textContent =
      currentScenario.title;
    document.getElementById("totalSteps").textContent = scenarioSteps.length;

    loadStep();
  } catch (error) {
    console.error("Error loading scenario:", error);
    showNotification("Error loading scenario", "error");
  }
}

function loadStep() {
  if (currentStepIndex >= scenarioSteps.length) {
    completeScenario();
    return;
  }

  const step = scenarioSteps[currentStepIndex];
  const progress = ((currentStepIndex + 1) / scenarioSteps.length) * 100;

  document.getElementById("currentStep").textContent = currentStepIndex + 1;
  document.getElementById("stepProgress").style.width = progress + "%";
  document.getElementById("storyContent").innerHTML =
    `<p>${step.story_text}</p>`;

  // Load choices
  const choicesContainer = document.getElementById("storyChoices");
  choicesContainer.innerHTML = "";

  step.choices.forEach((choice, index) => {
    const choiceBtn = document.createElement("button");
    choiceBtn.className = "choice-btn";
    choiceBtn.innerHTML = `
            <span class="choice-number">${index + 1}</span>
            <span class="choice-text">${choice.choice_text}</span>
        `;
    choiceBtn.addEventListener("click", () => makeChoice(choice));
    choicesContainer.appendChild(choiceBtn);
  });

  document.getElementById("choiceFeedback").classList.add("hidden");
}

function makeChoice(choice) {
  totalChoices++;

  const feedbackDiv = document.getElementById("choiceFeedback");
  const feedbackIcon = document.getElementById("feedbackIcon");
  const feedbackText = document.getElementById("feedbackText");

  if (choice.is_correct) {
    correctChoices++;
    feedbackIcon.textContent = "✓";
    feedbackIcon.className = "feedback-icon success";
    feedbackDiv.className = "choice-feedback success";
  } else {
    feedbackIcon.textContent = "✗";
    feedbackIcon.className = "feedback-icon error";
    feedbackDiv.className = "choice-feedback error";
  }

  feedbackText.textContent = choice.feedback;
  feedbackDiv.classList.remove("hidden");

  // Hide choices
  document.getElementById("storyChoices").style.display = "none";

  // Continue button
  document.getElementById("continueBtn").onclick = () => {
    document.getElementById("storyChoices").style.display = "block";
    currentStepIndex++;
    loadStep();
  };
}

function completeScenario() {
  const score =
    totalChoices > 0 ? Math.round((correctChoices / totalChoices) * 100) : 0;
  const xpEarned = Math.round((score / 100) * currentScenario.xp_reward);

  document.getElementById("scenarioPlay").classList.add("hidden");
  document.getElementById("scenarioComplete").classList.remove("hidden");

  document.getElementById("scenarioScore").textContent = score + "%";
  document.getElementById("scenarioXP").textContent = xpEarned;

  // Animate XP
  animateXP(document.getElementById("scenarioXP"), 0, xpEarned);

  // Save completion
  saveScenarioCompletion(score, xpEarned);
}

async function saveScenarioCompletion(score, xpEarned) {
  try {
    const formData = new FormData();
    formData.append("scenario_id", currentScenario.id);
    formData.append("score", score);
    formData.append("xp_earned", xpEarned);

    await fetch("api/save_scenario_completion.php", {
      method: "POST",
      body: formData,
    });
  } catch (error) {
    console.error("Error saving completion:", error);
  }
}

function quitScenario() {
  if (confirm("Are you sure you want to quit this scenario?")) {
    location.href = "scenarios.php";
  }
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
