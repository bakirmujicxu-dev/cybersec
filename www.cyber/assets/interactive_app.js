// assets/interactive_app.js - Interactive learning elements functionality

document.addEventListener("DOMContentLoaded", () => {
    initInteractivePage();
});

function initInteractivePage() {
    // Initialize filters
    initFilters();

    // Initialize modals
    initModals();

    // Initialize drag and drop
    initDragAndDrop();

    // Initialize memory game
    initMemoryGame();

    // Initialize simulations
    initSimulations();

    // Initialize code challenges
    initCodeChallenges();

    // Initialize timer
    initTimer();
}

function initFilters() {
    const categoryFilter = document.getElementById('category-filter');
    const difficultyFilter = document.getElementById('difficulty-filter');
    const typeFilter = document.getElementById('type-filter');

    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterElements);
    }

    if (difficultyFilter) {
        difficultyFilter.addEventListener('change', filterElements);
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', filterElements);
    }
}

function filterElements() {
    const categoryValue = document.getElementById('category-filter').value;
    const difficultyValue = document.getElementById('difficulty-filter').value;
    const typeValue = document.getElementById('type-filter').value;

    const cards = document.querySelectorAll('.interactive-element-card');

    cards.forEach(card => {
        const category = card.getAttribute('data-category');
        const difficulty = card.getAttribute('data-difficulty');
        const type = card.getAttribute('data-type');

        let show = true;

        if (categoryValue !== 'all' && category !== categoryValue) {
            show = false;
        }

        if (difficultyValue !== 'all' && difficulty !== difficultyValue) {
            show = false;
        }

        if (typeValue !== 'all' && type !== typeValue) {
            show = false;
        }

        card.style.display = show ? 'flex' : 'none';
    });
}

function initModals() {
    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

function startChallenge(type, challengeId) {
    // Load specific challenge based on type
    switch(type) {
        case 'quiz':
            window.location.href = `quiz.php?daily=${challengeId}`;
            break;
        case 'scenario':
            window.location.href = `scenarios.php?daily=${challengeId}`;
            break;
        case 'interactive':
            startInteractiveElement(challengeId, 'interactive');
            break;
    }
}

function startInteractiveElement(elementId, type) {
    // Find element data from global elements array
    const element = elements.find(el => el.id === elementId);
    if (!element) return;

    // Set modal title
    document.getElementById('modal-title').textContent = element.title.toUpperCase();

    // Load appropriate interactive content based on type
    switch(element.element_type) {
        case 'drag_drop':
            loadDragDrop(element);
            break;
        case 'simulation':
            loadSimulation(element);
            break;
        case 'memory_game':
            loadMemoryGame(element);
            break;
        case 'code_challenge':
            loadCodeChallenge(element);
            break;
        default:
            document.getElementById('element-content').innerHTML = '<p>Interactive element not available.</p>';
    }

    // Open modal
    openModal('element-modal');

    // Start timer
    startTimer(element.time_limit);
}

// Drag and Drop Implementation
function loadDragDrop(element) {
    const content = JSON.parse(element.content);
    let html = '<h3>Drag the suspicious elements to the correct zone</h3>';
    html += '<div class="drag-drop-container">';

    // Create draggable items
    html += '<div class="drag-items-container">';
    content.elements.forEach(item => {
        html += `<div class="drag-item" draggable="true" data-id="${item.id}">${item.name}</div>`;
    });
    html += '</div>';

    // Create drop zones
    content.scenarios.forEach((scenario, index) => {
        html += `<div class="drop-zone" data-scenario="${scenario.id}">`;
        html += `<div class="drop-zone-label">Scenario ${index + 1}</div>`;
        html += '<div class="drop-zone-items"></div>';
        html += '</div>';
    });

    html += '</div>';
    html += '<div class="interactive-actions"><button class="btn-cyber" onclick="checkDragDropSolution()">Check Answer</button></div>';

    document.getElementById('element-content').innerHTML = html;

    // Initialize drag and drop after DOM update
    setTimeout(initDragAndDrop, 100);
}

function initDragAndDrop() {
    const dragItems = document.querySelectorAll('.drag-item');
    const dropZones = document.querySelectorAll('.drop-zone');

    dragItems.forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
    });

    dropZones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('drop', handleDrop);
        zone.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    e.target.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', e.target.innerHTML);
    e.dataTransfer.setData('text/plain', e.target.getAttribute('data-id'));
}

function handleDragEnd(e) {
    e.target.classList.remove('dragging');
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    e.currentTarget.classList.add('drag-over');
    return false;
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    const dropZone = e.currentTarget;
    dropZone.classList.remove('drag-over');

    const itemId = e.dataTransfer.getData('text/plain');
    const draggedElement = document.querySelector(`.drag-item[data-id="${itemId}"]`);

    if (draggedElement) {
        const itemsContainer = dropZone.querySelector('.drop-zone-items');

        // Create a copy of the dragged element for the drop zone
        const droppedElement = document.createElement('div');
        droppedElement.className = 'drop-zone-item';
        droppedElement.setAttribute('data-id', itemId);
        droppedElement.textContent = draggedElement.textContent;

        itemsContainer.appendChild(droppedElement);

        // Remove the original if it was moved from another drop zone
        if (draggedElement.parentElement.classList.contains('drop-zone-items')) {
            draggedElement.remove();
        }
    }

    return false;
}

function checkDragDropSolution() {
    const dropZones = document.querySelectorAll('.drop-zone');
    const element = elements.find(el => el.title === document.getElementById('modal-title').textContent);
    const content = JSON.parse(element.content);
    let allCorrect = true;
    let score = 0;

    dropZones.forEach(zone => {
        const scenarioId = zone.getAttribute('data-scenario');
        const scenario = content.scenarios.find(s => s.id == scenarioId);
        const droppedItems = Array.from(zone.querySelectorAll('.drop-zone-item')).map(item => item.getAttribute('data-id'));

        const correctItems = scenario.correct_elements;
        const isCorrect = droppedItems.length === correctItems.length &&
                         droppedItems.every(item => correctItems.includes(item));

        if (isCorrect) {
            score += 25; // 25 points per correct scenario
            zone.style.border = '2px solid #10b981';
        } else {
            allCorrect = false;
            zone.style.border = '2px solid #ef4444';
            zone.classList.add('shake-animation');

            setTimeout(() => {
                zone.classList.remove('shake-animation');
            }, 500);
        }
    });

    // Show result
    setTimeout(() => {
        showResult(allCorrect, score, element.xp_reward);
    }, 1000);
}

// Memory Game Implementation
function loadMemoryGame(element) {
    const content = JSON.parse(element.content);
    let html = '<h3>Match the malware types with their descriptions</h3>';
    html += '<div class="memory-game" id="memory-game">';

    // Create pairs of cards
    const cards = [];
    content.cards.forEach(card => {
        cards.push({ id: card.id, type: card.type, content: card.name, pair: 'front' });
        cards.push({ id: card.id, type: card.type, content: card.description, pair: 'back' });
    });

    // Shuffle cards
    const shuffled = cards.sort(() => Math.random() - 0.5);

    // Create card elements
    shuffled.forEach((card, index) => {
        html += `<div class="memory-card" data-id="${card.id}" data-pair="${card.pair}" data-index="${index}">${card.content}</div>`;
    });

    html += '</div>';
    html += '<div class="interactive-actions"><button class="btn-cyber" onclick="checkMemorySolution()">Check Pairs</button></div>';

    document.getElementById('element-content').innerHTML = html;

    // Initialize memory game after DOM update
    setTimeout(initMemoryGame, 100);
}

function initMemoryGame() {
    const cards = document.querySelectorAll('.memory-card');
    let flippedCards = [];
    let matchedPairs = 0;

    cards.forEach(card => {
        card.addEventListener('click', () => {
            // Skip if already flipped or matched
            if (card.classList.contains('flipped') || card.classList.contains('matched')) {
                return;
            }

            // Flip the card
            card.classList.add('flipped');
            flippedCards.push(card);

            // Check for match if two cards are flipped
            if (flippedCards.length === 2) {
                const [card1, card2] = flippedCards;

                // Check if cards match
                if (card1.getAttribute('data-id') === card2.getAttribute('data-id')) {
                    // Match found
                    card1.classList.add('matched');
                    card2.classList.add('matched');
                    matchedPairs++;

                    // Check if all pairs are matched
                    const totalPairs = cards.length / 2;
                    if (matchedPairs === totalPairs) {
                        const element = elements.find(el => el.title === document.getElementById('modal-title').textContent);
                        const score = Math.round((totalPairs / matchedPairs) * 100);

                        setTimeout(() => {
                            showResult(true, score, element.xp_reward);
                        }, 500);
                    }
                } else {
                    // No match, flip cards back after delay
                    setTimeout(() => {
                        card1.classList.remove('flipped');
                        card2.classList.remove('flipped');
                    }, 1000);
                }

                // Reset flipped cards array
                flippedCards = [];
            }
        });
    });
}

function checkMemorySolution() {
    // This is handled by the click events above
    // This function is just for the button to maintain consistency
}

// Simulation Implementation
function loadSimulation(element) {
    const content = JSON.parse(element.content);
    let html = '<h3>Password Strength Simulator</h3>';
    html += '<div class="simulation-container">';

    // Password input
    html += '<div class="password-input-container">';
    html += '<input type="password" id="password-input" placeholder="Enter a password to test its strength">';
    html += '<button class="btn-cyber" onclick="togglePasswordVisibility()">👁️</button>';
    html += '</div>';

    // Password strength meter
    html += '<div class="password-strength-meter">';
    html += '<div id="strength-meter-fill" class="password-strength-fill strength-weak"></div>';
    html += '</div>';

    // Requirements display
    html += '<div class="requirements-container">';
    html += '<h4>Password Requirements:</h4>';
    html += '<ul>';
    html += `<li id="req-length">Minimum ${content.min_length} characters</li>`;
    content.complexity_requirements.forEach(req => {
        html += `<li id="req-${req}">Include ${req} characters</li>`;
    });
    html += '</ul>';
    html += '</div>';

    // Feedback display
    html += '<div id="strength-feedback" class="strength-feedback">Enter a password to see its strength</div>';

    html += '</div>';
    html += '<div class="interactive-actions"><button class="btn-cyber" onclick="checkPasswordStrength()">Submit Password</button></div>';

    document.getElementById('element-content').innerHTML = html;

    // Add input event listener
    const passwordInput = document.getElementById('password-input');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
}

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password-input');
    if (passwordInput) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
        } else {
            passwordInput.type = 'password';
        }
    }
}

function checkPasswordStrength() {
    const passwordInput = document.getElementById('password-input');
    if (!passwordInput) return;

    const password = passwordInput.value;
    const element = elements.find(el => el.title === document.getElementById('modal-title').textContent);
    const content = JSON.parse(element.content);

    // Check requirements
    let score = 0;
    let requirementsMet = 0;

    // Length requirement
    if (password.length >= content.min_length) {
        document.getElementById('req-length').classList.add('requirement-met');
        score += 20;
        requirementsMet++;
    } else {
        document.getElementById('req-length').classList.remove('requirement-met');
    }

    // Complexity requirements
    content.complexity_requirements.forEach(req => {
        const reqElement = document.getElementById(`req-${req}`);

        let met = false;
        switch(req) {
            case 'uppercase':
                met = /[A-Z]/.test(password);
                break;
            case 'lowercase':
                met = /[a-z]/.test(password);
                break;
            case 'number':
                met = /[0-9]/.test(password);
                break;
            case 'special':
                met = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
                break;
        }

        if (met) {
            reqElement.classList.add('requirement-met');
            score += 20;
            requirementsMet++;
        } else {
            reqElement.classList.remove('requirement-met');
        }
    });

    // Calculate total score (0-100)
    score = Math.min(100, score);

    // Update strength meter
    const strengthMeter = document.getElementById('strength-meter-fill');
    const feedbackElement = document.getElementById('strength-feedback');

    if (score < 40) {
        strengthMeter.className = 'password-strength-fill strength-weak';
        feedbackElement.textContent = 'Weak password. Try adding more complexity.';
    } else if (score < 80) {
        strengthMeter.className = 'password-strength-fill strength-medium';
        feedbackElement.textContent = 'Medium strength. Getting better!';
    } else {
        strengthMeter.className = 'password-strength-fill strength-strong';
        feedbackElement.textContent = 'Strong password! Great job!';
    }

    // This just checks the strength, submission is handled by the button
}

// Code Challenge Implementation
function loadCodeChallenge(element) {
    const content = JSON.parse(element.content);
    let html = '<h3>Code Security Analysis</h3>';

    // Display the challenge
    html += '<div class="code-challenge">';
    html += '<div class="code-line-numbers">';

    // Generate line numbers (assuming 10 lines for simplicity)
    for (let i = 1; i <= 10; i++) {
        html += i + '<br>';
    }

    html += '</div>';
    html += '<div class="code-content">' + content.challenge + '</div>';
    html += '</div>';

    // Solution input area
    html += '<div class="solution-input-container">';
    html += '<h4>Identify the security issues:</h4>';
    html += '<textarea id="code-solution" rows="4" placeholder="Enter the security issues you found (separated by commas)"></textarea>';
    html += '</div>';

    // Hint button
    html += '<button class="hint-button" onclick="toggleHint()">Show Hint</button>';
    html += '<div id="hint-content" class="hint-content">';
    html += '<h4>Hint:</h4>';
    html += '<ul>';
    content.hints.forEach(hint => {
        html += `<li>${hint}</li>`;
    });
    html += '</ul>';
    html += '</div>';

    html += '<div class="interactive-actions"><button class="btn-cyber" onclick="checkCodeSolution()">Submit Solution</button></div>';

    document.getElementById('element-content').innerHTML = html;
}

function toggleHint() {
    const hintContent = document.getElementById('hint-content');
    if (hintContent) {
        hintContent.classList.toggle('show');
    }
}

function checkCodeSolution() {
    const solutionInput = document.getElementById('code-solution');
    if (!solutionInput) return;

    const userSolution = solutionInput.value.toLowerCase().split(',').map(s => s.trim());
    const element = elements.find(el => el.title === document.getElementById('modal-title').textContent);
    const content = JSON.parse(element.content);
    const correctSolution = content.solution.map(s => s.toLowerCase());

    // Check if user solution is correct
    let allCorrect = true;
    let score = 0;

    if (userSolution.length === correctSolution.length) {
        for (let i = 0; i < correctSolution.length; i++) {
            if (correctSolution.includes(userSolution[i])) {
                score += 100 / correctSolution.length;
            } else {
                allCorrect = false;
            }
        }
    } else {
        allCorrect = false;
    }

    // Show result
    showResult(allCorrect, Math.round(score), element.xp_reward);
}

// Timer Implementation
let timerInterval;
let timeRemaining;
let timerElement;

function initTimer() {
    timerElement = document.createElement('div');
    timerElement.className = 'timer';
    timerElement.style.display = 'none';
    document.body.appendChild(timerElement);
}

function startTimer(seconds) {
    if (!timerElement) {
        initTimer();
    }

    timeRemaining = seconds;
    timerElement.style.display = 'flex';
    timerElement.innerHTML = `<span class="timer-icon">⏱️</span> Time: ${formatTime(timeRemaining)}`;

    // Clear any existing timer
    if (timerInterval) {
        clearInterval(timerInterval);
    }

    // Start new timer
    timerInterval = setInterval(() => {
        timeRemaining--;
        timerElement.innerHTML = `<span class="timer-icon">⏱️</span> Time: ${formatTime(timeRemaining)}`;

        // Add warning when time is running out
        if (timeRemaining <= 10) {
            timerElement.style.color = '#ef4444';
        }

        // Time's up
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timerElement.style.display = 'none';

            // Auto-submit with failure
            const element = elements.find(el => el.title === document.getElementById('modal-title').textContent);
            showResult(false, 0, 0);
        }
    }, 1000);
}

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    if (timerElement) {
        timerElement.style.display = 'none';
    }
}

// Result Display
function showResult(success, score, maxXP) {
    // Stop timer
    stopTimer();

    // Calculate XP earned
    let xpEarned = 0;
    if (success) {
        xpEarned = Math.round(maxXP * (score / 100));
    }

    // Close element modal
    closeModal('element-modal');

    // Prepare result content
    let resultClass = 'poor';
    let resultMessage = 'Try again next time!';

    if (success) {
        if (score >= 90) {
            resultClass = 'excellent';
            resultMessage = 'Outstanding work!';
        } else if (score >= 70) {
            resultClass = 'good';
            resultMessage = 'Good job!';
        } else {
            resultMessage = 'You passed!';
        }
    }

    const resultContent = `
        <div class="result-display">
            <div class="result-score ${resultClass}">${score}%</div>
            <div class="result-message">${resultMessage}</div>
            <div class="result-xp">+${xpEarned} XP</div>
            <button class="btn-cyber" onclick="closeModal('result-modal')">Close</button>
        </div>
    `;

    document.getElementById('result-content').innerHTML = resultContent;

    // Open result modal
    openModal('result-modal');

    // Save progress
    if (xpEarned > 0) {
        saveProgress(xpEarned);
    }
}

function saveProgress(xpEarned) {
    const element = elements.find(el => el.title === document.getElementById('modal-title').textContent);

    fetch('api/save_interactive_completion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            element_id: element.id,
            xp_earned: xpEarned
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI with new XP if available
            if (data.new_xp) {
                const xpElement = document.querySelector('.user-xp');
                if (xpElement) {
                    animateXP(xpElement, parseInt(xpElement.textContent), data.new_xp);
                }
            }

            // Show notification
            showNotification(`Earned ${xpEarned} XP!`, 'success');
        } else {
            showNotification('Error saving progress', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving progress', 'error');
    });
}

// Notification system (reusing from cyber_app.js)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `cyber-notification ${type}`;
    notification.innerHTML = `
        <span class="notif-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
        <span class="notif-text">${message}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}
