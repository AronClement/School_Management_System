const navToggle = document.querySelector('.nav-toggle');
const siteNav = document.querySelector('.site-nav');

if (navToggle && siteNav) {
    navToggle.addEventListener('click', () => {
        siteNav.classList.toggle('nav-open');
        navToggle.classList.toggle('open');
    });
}

const currentYear = document.createElement('span');
currentYear.textContent = new Date().getFullYear();
const footerText = document.querySelector('.site-footer p');
if (footerText && !footerText.textContent.includes(currentYear.textContent)) {
    footerText.textContent = footerText.textContent.replace(/\d{4}/, currentYear.textContent);
}

function initAssignmentDeadline() {
    const timerEl = document.getElementById('deadline-timer');
    if (!timerEl) {
        return;
    }

    const deadlineValue = timerEl.dataset.deadline;
    if (!deadlineValue) {
        return;
    }

    const deadline = new Date(deadlineValue);
    if (isNaN(deadline.getTime())) {
        return;
    }

    const form = document.getElementById('assignment-form');
    const submitButton = document.getElementById('assignment-submit-button');

    const updateTimer = () => {
        const now = new Date();
        const diff = deadline.getTime() - now.getTime();

        if (diff <= 0) {
            timerEl.textContent = 'Deadline has passed. Submission is closed.';
            if (submitButton) {
                submitButton.disabled = true;
            }
            if (form) {
                form.classList.add('disabled');
            }
            clearInterval(intervalId);
            return;
        }

        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        timerEl.textContent = `Submit within ${hours}h ${minutes}m ${seconds}s`;
    };

    updateTimer();
    const intervalId = setInterval(updateTimer, 1000);
}

function printExam(url) {
    const printWindow = window.open(url, '_blank');
    if (!printWindow) {
        return;
    }
    printWindow.focus();
    printWindow.addEventListener('load', () => {
        printWindow.print();
    });
}

initAssignmentDeadline();
