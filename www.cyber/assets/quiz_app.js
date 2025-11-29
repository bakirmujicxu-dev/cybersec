// assets/quiz_app.js - Quiz mode functionality

let selectedCategory = 'all';
let selectedDifficulty = 'all';
let questions = [];
let currentQuestionIndex = 0;
let correctAnswers = 0;
let incorrectAnswers = 0;
let totalXP = 0;

document.addEventListener('DOMContentLoaded', () => {
    initQuizMode();
});

function initQuizMode() {
    // Category selection
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedCategory = btn.getAttribute('data-category');
        });
    });

    // Difficulty selection
    const difficultyButtons = document.querySelectorAll('.difficulty-btn');
    difficultyButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            difficultyButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedDifficulty = btn.getAttribute('data-difficulty');
        });
    });

    // Start quiz button
    const startBtn = document.getElementById('startQuiz');
    if (startBtn) {
        startBtn.addEventListener('click', startQuiz);
    }

    // Answer buttons
    const knowBtn = document.getElementById('knowBtn');
    const dontKnowBtn = document.getElementById('dontKnowBtn');
    const nextBtn = document.getElementById('nextBtn');
    const quitBtn = document.getElementById('quitQuiz');

    if (knowBtn) {
        knowBtn.addEventListener('click', () => handleAnswer(true));
    }

    if (dontKnowBtn) {
        dontKnowBtn.addEventListener('click', () => handleAnswer(false));
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', loadNextQuestion);
    }

    if (quitBtn) {
        quitBtn.addEventListener('click', quitQuiz);
    }

    // Flashcard click to flip
    const flashcard = document.getElementById('flashcard');
    if (flashcard) {
        flashcard.addEventListener('click', () => {
            if (!flashcard.classList.contains('flipped')) {
                flashcard.classList.add('flipped');
            }
        });
    }
}

async function startQuiz() {
    try {
        // Show loading
        showLoadingAnimation();

        // Fetch questions
        const response = await fetch(`api/get_quiz_questions.php?category=${selectedCategory}&difficulty=${selectedDifficulty}`);
        const data = await response.json();

        if (data.error) {
            showNotification(data.error, 'error');
            return;
        }

        if (!data || data.length === 0) {
            showNotification('No questions available for selected criteria', 'error');
            return;
        }

        questions = data;
        currentQuestionIndex = 0;
        correctAnswers = 0;
        incorrectAnswers = 0;
        totalXP = 0;

        // Hide setup, show quiz
        document.getElementById('quizSetup').classList.add('hidden');
        document.getElementById('quizArea').classList.remove('hidden');

        // Update total questions
        document.getElementById('totalQuestions').textContent = questions.length;

        // Load first question
        loadQuestion();

        // Hide loading
        hideLoadingAnimation();
    } catch (error) {
        console.error('Error starting quiz:', error);
        showNotification('Error loading questions', 'error');
        hideLoadingAnimation();
    }
}

function loadQuestion() {
    if (currentQuestionIndex >= questions.length) {
        endQuiz();
        return;
    }

    const question = questions[currentQuestionIndex];
    const flashcard = document.getElementById('flashcard');

    // Remove flipped class
    flashcard.classList.remove('flipped');

    // Update question info
    document.getElementById('currentQuestion').textContent = currentQuestionIndex + 1;
    document.getElementById('currentScore').textContent = totalXP;

    // Update category and difficulty
    document.getElementById('cardCategory').textContent = question.category_name || 'Unknown';
    document.getElementById('cardDifficulty').textContent = question.difficulty ? question.difficulty.toUpperCase() : 'MEDIUM';
    document.getElementById('cardDifficulty').className = 'card-difficulty difficulty-' + (question.difficulty || 'medium');

    // Set question and answer text
    document.getElementById('cardQuestion').textContent = question.question || 'Loading...';
    document.getElementById('cardAnswer').textContent = question.answer || 'Loading...';

    // Enable answer buttons, disable next button
    document.getElementById('knowBtn').disabled = false;
    document.getElementById('dontKnowBtn').disabled = false;
    document.getElementById('nextBtn').disabled = true;

    // Update progress bar
    updateProgressBar();
}

function handleAnswer(isCorrect) {
    const question = questions[currentQuestionIndex];
    const flashcard = document.getElementById('flashcard');

    // Flip card to show answer
    if (!flashcard.classList.contains('flipped')) {
        flashcard.classList.add('flipped');
    }

    // Update counters
    if (isCorrect) {
        correctAnswers++;
        totalXP += question.xp_reward || 10;
        playSound('correct');
    } else {
        incorrectAnswers++;
        playSound('incorrect');
    }

    // Update progress bar
    updateProgressBar();

    // Update current score
    document.getElementById('currentScore').textContent = totalXP;

    // Disable answer buttons, enable next button
    document.getElementById('knowBtn').disabled = true;
    document.getElementById('dontKnowBtn').disabled = true;
    document.getElementById('nextBtn').disabled = false;

    // Save progress to database
    saveQuestionProgress(question.id, isCorrect);
}

async function saveQuestionProgress(questionId, isCorrect) {
    try {
        const formData = new FormData();
        formData.append('question_id', questionId);
        formData.append('is_correct', isCorrect ? 1 : 0);

        await fetch('api/update_quiz_progress.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Error saving progress:', error);
    }
}

function loadNextQuestion() {
    currentQuestionIndex++;
    loadQuestion();
}

function updateProgressBar() {
    const total = correctAnswers + incorrectAnswers;
    const correctPercent = total > 0 ? (correctAnswers / total) * 100 : 0;
    const incorrectPercent = total > 0 ? (incorrectAnswers / total) * 100 : 0;
    const accuracy = total > 0 ? Math.round((correctAnswers / total) * 100) : 0;

    document.getElementById('progressCorrect').style.width = correctPercent + '%';
    document.getElementById('progressIncorrect').style.width = incorrectPercent + '%';

    document.getElementById('correctCount').textContent = correctAnswers;
    document.getElementById('incorrectCount').textContent = incorrectAnswers;
    document.getElementById('accuracy').textContent = accuracy;
}

async function endQuiz() {
    const total = correctAnswers + incorrectAnswers;
    const accuracy = total > 0 ? Math.round((correctAnswers / total) * 100) : 0;

    // Hide quiz area, show results
    document.getElementById('quizArea').classList.add('hidden');
    document.getElementById('quizResults').classList.remove('hidden');

    // Update results
    document.getElementById('finalCorrect').textContent = correctAnswers;
    document.getElementById('finalIncorrect').textContent = incorrectAnswers;
    document.getElementById('finalAccuracy').textContent = accuracy + '%';
    document.getElementById('finalXP').textContent = totalXP;

    // Animate XP counter
    animateValue(document.getElementById('finalXP'), 0, totalXP, 1000);

    // Save quiz session
    await saveQuizSession();
}

async function saveQuizSession() {
    try {
        const formData = new FormData();
        formData.append('correct', correctAnswers);
        formData.append('incorrect', incorrectAnswers);
        formData.append('total_xp', totalXP);
        formData.append('category', selectedCategory);
        formData.append('difficulty', selectedDifficulty);

        await fetch('api/save_quiz_session.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Error saving quiz session:', error);
    }
}

function quitQuiz() {
    if (confirm('Are you sure you want to quit? Your progress will be saved.')) {
        endQuiz();
    }
}

function animateValue(element, start, end, duration) {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 16);
}

function showLoadingAnimation() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner"></div>
        <div class="loading-text">Loading questions...</div>
    `;
    loading.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    document.body.appendChild(loading);
}

function hideLoadingAnimation() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.style.opacity = '0';
        setTimeout(() => loading.remove(), 300);
    }
}

function playSound(type) {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        if (type === 'correct') {
            oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
            oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1); // E5
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } else if (type === 'incorrect') {
            oscillator.frequency.setValueAtTime(200, audioContext.currentTime);
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        }
    } catch (error) {
        // Silently fail if audio context not supported
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `cyber-notification ${type}`;
    notification.innerHTML = `
        <span class="notif-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
        <span class="notif-text">${message}</span>
    `;

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: rgba(31, 41, 55, 0.95);
        border: 2px solid ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        border-radius: 8px;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 10000;
        color: white;
        font-family: 'Courier New', monospace;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(0, 212, 255, 0.3);
        border-top-color: #00d4ff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .loading-text {
        margin-top: 1rem;
        color: #00d4ff;
        font-size: 1.2rem;
        font-family: 'Courier New', monospace;
    }

    .notif-icon {
        font-size: 1.2rem;
    }
`;
document.head.appendChild(style);
