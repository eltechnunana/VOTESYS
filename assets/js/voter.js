/**
 * Heritage Christian University Online Voting System
 * Voter Page JavaScript
 * Handles countdown timer, candidate selection, and voting interactions
 */

// Global variables
let countdownInterval;
let selectedCandidates = {};
let electionStartDate;
let electionEndDate;
let hasVoted = false;
let positions = [];

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize voter page data
    if (window.voterPageData) {
        electionStartDate = window.voterPageData.electionStartDate;
        electionEndDate = window.voterPageData.electionEndDate;
        hasVoted = window.voterPageData.hasVoted;
        positions = window.voterPageData.positions;
    }
    
    // Initialize components
    initializeCountdown();
    initializeCandidateSelection();
    initializeVotingForm();
    initializeAnimations();
    initializeReadMore();
    initializeMobileInteractions();
    
    console.log('Voter page initialized successfully');
});

/**
 * Initialize countdown timer
 */
function initializeCountdown() {
    if (!electionStartDate || !electionEndDate) {
        console.warn('Election start or end date not provided');
        return;
    }
    
    // Update countdown immediately
    updateCountdown();
    
    // Update countdown every second
    countdownInterval = setInterval(updateCountdown, 1000);
}

/**
 * Update countdown display
 */
function updateCountdown() {
    // Get current time in local timezone (same as server)
    // Use UTC time for consistent timezone handling
    const currentTime = new Date().getTime();
    const timeToStart = electionStartDate - currentTime;
    const timeToEnd = electionEndDate - currentTime;
    
    // Update countdown label based on election status
    const countdownLabel = document.querySelector('.countdown-text');
    
    if (timeToStart > 0) {
        // Election hasn't started yet - show time until start
        if (countdownLabel) {
            countdownLabel.textContent = 'Time Until Election Starts';
        }
        
        const days = Math.floor(timeToStart / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeToStart % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeToStart % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeToStart % (1000 * 60)) / 1000);
        
        updateCountdownElement('days', days);
        updateCountdownElement('hours', hours);
        updateCountdownElement('minutes', minutes);
        updateCountdownElement('seconds', seconds);
        
        // Remove urgency styling
        const countdownContainer = document.querySelector('.countdown-timer');
        if (countdownContainer) {
            countdownContainer.classList.remove('urgent');
        }
        
    } else if (timeToEnd > 0) {
        // Election is active - show time remaining to vote
        if (countdownLabel) {
            countdownLabel.textContent = 'Time Remaining to Vote';
        }
        
        const days = Math.floor(timeToEnd / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeToEnd % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeToEnd % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeToEnd % (1000 * 60)) / 1000);
        
        updateCountdownElement('days', days);
        updateCountdownElement('hours', hours);
        updateCountdownElement('minutes', minutes);
        updateCountdownElement('seconds', seconds);
        
        // Add urgency styling if less than 1 hour remaining
        const countdownContainer = document.querySelector('.countdown-timer');
        if (countdownContainer) {
            if (timeToEnd < 3600000) { // Less than 1 hour
                countdownContainer.classList.add('urgent');
            } else {
                countdownContainer.classList.remove('urgent');
            }
        }
        
    } else {
        // Election has ended
        clearInterval(countdownInterval);
        displayElectionEnded();
        return;
    }
}

/**
 * Update individual countdown element
 */
function updateCountdownElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        const formattedValue = value.toString().padStart(2, '0');
        if (element.textContent !== formattedValue) {
            element.textContent = formattedValue;
            // Add pulse animation for changes
            element.parentElement.classList.add('pulse');
            setTimeout(() => {
                element.parentElement.classList.remove('pulse');
            }, 1000);
        }
    }
}

/**
 * Display election ended message
 */
function displayElectionEnded() {
    const countdownContainer = document.querySelector('.countdown-container');
    if (countdownContainer) {
        countdownContainer.innerHTML = `
            <div class="election-ended">
                <i class="fas fa-clock text-warning"></i>
                <h3>Voting Has Ended</h3>
                <p>The election voting period has concluded.</p>
            </div>
        `;
    }
    
    // Disable voting if not already voted
    if (!hasVoted) {
        disableVoting('The voting period has ended.');
    }
}

/**
 * Initialize candidate selection functionality
 */
function initializeCandidateSelection() {
    if (hasVoted) {
        return; // Don't initialize if already voted
    }
    
    // Removed card click handlers - only vote buttons should be clickable
    // const candidateCards = document.querySelectorAll('.candidate-card');
    // candidateCards.forEach(card => {
    //     card.addEventListener('click', function() {
    //         handleCandidateSelection(this);
    //     });
    //     
    //     // Add keyboard support
    //     card.addEventListener('keydown', function(e) {
    //         if (e.key === 'Enter' || e.key === ' ') {
    //             e.preventDefault();
    //             handleCandidateSelection(this);
    //         }
    //     });
    //     
    //     // Make cards focusable
    //     card.setAttribute('tabindex', '0');
    // });
    
    // Add change handlers to radio buttons (for direct radio button clicks)
    const radioButtons = document.querySelectorAll('.candidate-radio');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const card = this.closest('.candidate-card');
                // Update visual selection without triggering circular calls
                updateCandidateSelection(card, false);
            }
        });
    });
}

/**
 * Update candidate selection (shared logic)
 */
function updateCandidateSelection(selectedCard, shouldCheckRadio = true) {
    const candidateId = selectedCard.dataset.candidateId;
    const positionSection = selectedCard.closest('.position-section');
    const positionId = positionSection.dataset.positionId;
    const radio = selectedCard.querySelector('.candidate-radio');
    
    // Remove selection from other candidates in the same position
    const otherCards = positionSection.querySelectorAll('.candidate-card');
    otherCards.forEach(card => {
        card.classList.remove('selected');
        const otherRadio = card.querySelector('.candidate-radio');
        if (otherRadio && shouldCheckRadio) {
            otherRadio.checked = false;
        }
    });
    
    // Select the clicked candidate
    selectedCard.classList.add('selected');
    if (radio && shouldCheckRadio) {
        radio.checked = true;
    }
    
    // Update selected candidates object
    selectedCandidates[positionId] = {
        candidateId: candidateId,
        candidateName: selectedCard.querySelector('.candidate-name').textContent,
        positionName: positionSection.querySelector('.position-title').textContent
    };
    
    // Update submit button state
    updateSubmitButtonState();
    
    // Add selection animation
    selectedCard.classList.add('fade-in');
    setTimeout(() => {
        selectedCard.classList.remove('fade-in');
    }, 500);
}

/**
 * Handle candidate selection (from card clicks)
 */
function handleCandidateSelection(selectedCard) {
    updateCandidateSelection(selectedCard, true);
}

/**
 * Update submit button state based on selections
 */
function updateSubmitButtonState() {
    const submitBtn = document.getElementById('submitVoteBtn');
    if (!submitBtn) return;
    
    const totalPositions = positions.length;
    const selectedCount = Object.keys(selectedCandidates).length;
    
    if (selectedCount === totalPositions) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('btn-secondary');
        submitBtn.classList.add('btn-success');
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit My Vote';
    } else {
        submitBtn.disabled = true;
        submitBtn.classList.remove('btn-success');
        submitBtn.classList.add('btn-secondary');
        submitBtn.innerHTML = `<i class="fas fa-vote-yea me-2"></i>Select Candidates (${selectedCount}/${totalPositions})`;
    }
}

/**
 * Initialize voting form functionality
 */
function initializeVotingForm() {
    if (hasVoted) {
        return; // Don't initialize if already voted
    }
    
    const submitBtn = document.getElementById('submitVoteBtn');
    
    // Wait for Bootstrap to be available
    if (typeof bootstrap === 'undefined') {
        setTimeout(initializeVotingForm, 100);
        return;
    }
    
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmVoteModal'));
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const votingForm = document.getElementById('votingForm');
    
    // Initially disable submit button
    updateSubmitButtonState();
    
    // Handle submit button click
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (Object.keys(selectedCandidates).length === positions.length) {
                showVoteConfirmation();
                confirmModal.show();
            } else {
                showAlert('Please select a candidate for each position before submitting.', 'warning');
            }
        });
    }
    
    // Handle final confirmation
    if (confirmSubmitBtn) {
        confirmSubmitBtn.addEventListener('click', function() {
            submitVote();
        });
    }
    
    // Handle form submission
    if (votingForm) {
        votingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Form submission is handled by submitVote function
        });
    }
}

/**
 * Show vote confirmation modal with selected candidates
 */
function showVoteConfirmation() {
    const reviewContainer = document.getElementById('voteReview');
    if (!reviewContainer) return;
    
    let reviewHTML = '';
    
    Object.values(selectedCandidates).forEach(selection => {
        reviewHTML += `
            <div class="review-item">
                <div class="review-position">${selection.positionName}</div>
                <div class="review-candidate">${selection.candidateName}</div>
            </div>
        `;
    });
    
    reviewContainer.innerHTML = reviewHTML;
}

/**
 * Submit the vote
 */
function submitVote() {
    const form = document.getElementById('votingForm');
    const confirmBtn = document.getElementById('confirmSubmitBtn');
    
    if (!form) {
        showAlert('Voting form not found. Please refresh the page.', 'danger');
        return;
    }
    
    // Disable submit button and show loading
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting Vote...';
    }
    
    // Create FormData object
    const formData = new FormData(form);
    
    // Submit via AJAX
    fetch('process_vote.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and show success
            bootstrap.Modal.getInstance(document.getElementById('confirmVoteModal')).hide();
            showVoteSuccess();
        } else {
            throw new Error(data.message || 'Failed to submit vote');
        }
    })
    .catch(error => {
        console.error('Vote submission error:', error);
        showAlert(error.message || 'Failed to submit vote. Please try again.', 'danger');
        
        // Re-enable submit button
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Confirm & Submit Vote';
        }
    });
}

/**
 * Show vote success message and reload page
 */
function showVoteSuccess() {
    // Show success alert
    showAlert('Your vote has been submitted successfully! Thank you for participating.', 'success');
    
    // Reload page after 2 seconds to show "already voted" state
    setTimeout(() => {
        window.location.reload();
    }, 2000);
}

/**
 * Disable voting functionality
 */
function disableVoting(message) {
    const votingContainer = document.querySelector('.voting-container');
    if (votingContainer) {
        votingContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                <h3 class="mt-3">Voting Unavailable</h3>
                <p class="text-muted">${message}</p>
                <a href="landing.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Return to Home
                </a>
            </div>
        `;
    }
}

/**
 * Initialize animations
 */
function initializeAnimations() {
    // Add fade-in animation to main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }
    
    // Add staggered animation to position sections
    const positionSections = document.querySelectorAll('.position-section');
    positionSections.forEach((section, index) => {
        setTimeout(() => {
            section.classList.add('fade-in');
        }, index * 200);
    });
    
    // Add hover effects to candidate cards
    const candidateCards = document.querySelectorAll('.candidate-card');
    candidateCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.dynamic-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show dynamic-alert`;
    alertDiv.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of main content
    const mainContent = document.querySelector('.main-content .container');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

/**
 * Get appropriate icon for alert type
 */
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Handle page visibility change (pause countdown when tab is not active)
 */
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, pause countdown
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    } else {
        // Page is visible, resume countdown
        if (electionStartDate && electionEndDate && !hasVoted) {
            countdownInterval = setInterval(updateCountdown, 1000);
            updateCountdown(); // Update immediately
        }
    }
});

/**
 * Handle beforeunload event to warn about unsaved changes
 */
window.addEventListener('beforeunload', function(e) {
    if (Object.keys(selectedCandidates).length > 0 && !hasVoted) {
        const message = 'You have unsaved vote selections. Are you sure you want to leave?';
        e.returnValue = message;
        return message;
    }
});

/**
 * Keyboard shortcuts
 */
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + Enter to submit vote (if all selections made)
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const submitBtn = document.getElementById('submitVoteBtn');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.click();
        }
    }
    
    // Escape to close modal
    if (e.key === 'Escape') {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmVoteModal'));
        if (modal) {
            modal.hide();
        }
    }
});

/**
 * Utility function to format time
 */
function formatTime(milliseconds) {
    const seconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 0) {
        return `${days} day${days !== 1 ? 's' : ''}`;
    } else if (hours > 0) {
        return `${hours} hour${hours !== 1 ? 's' : ''}`;
    } else if (minutes > 0) {
        return `${minutes} minute${minutes !== 1 ? 's' : ''}`;
    } else {
        return `${seconds} second${seconds !== 1 ? 's' : ''}`;
    }
}

/**
 * Debug function (remove in production)
 */
/**
 * Initialize read more functionality for candidate manifestos
 */
function initializeReadMore() {
    const readMoreLinks = document.querySelectorAll('.read-more');
    
    readMoreLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const fullText = this.getAttribute('data-full-text');
            const manifestoPreview = this.parentElement;
            
            // Create expanded content
            const expandedContent = document.createElement('div');
            expandedContent.className = 'manifesto-expanded';
            expandedContent.innerHTML = `
                <p>${fullText}</p>
                <a href="#" class="read-less">Read Less</a>
            `;
            
            // Replace preview with expanded content
            manifestoPreview.style.display = 'none';
            manifestoPreview.parentElement.appendChild(expandedContent);
            
            // Add read less functionality
            const readLessLink = expandedContent.querySelector('.read-less');
            readLessLink.addEventListener('click', function(e) {
                e.preventDefault();
                expandedContent.remove();
                manifestoPreview.style.display = 'block';
            });
            
            // Smooth scroll to keep content in view
            expandedContent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });
}

/**
 * Initialize mobile-specific interactions
 */
function initializeMobileInteractions() {
    // Add touch feedback for candidate cards
    const candidateCards = document.querySelectorAll('.candidate-card');
    
    candidateCards.forEach(card => {
        // Touch start - add pressed state
        card.addEventListener('touchstart', function() {
            this.classList.add('touch-pressed');
        });
        
        // Touch end - remove pressed state
        card.addEventListener('touchend', function() {
            setTimeout(() => {
                this.classList.remove('touch-pressed');
            }, 150);
        });
        
        // Touch cancel - remove pressed state
        card.addEventListener('touchcancel', function() {
            this.classList.remove('touch-pressed');
        });
    });
    
    // Improve button interactions on mobile
    const voteButtons = document.querySelectorAll('.vote-btn');
    
    voteButtons.forEach(button => {
        button.addEventListener('touchstart', function() {
            this.classList.add('touch-active');
        });
        
        button.addEventListener('touchend', function() {
            setTimeout(() => {
                this.classList.remove('touch-active');
            }, 200);
        });
    });
    
    // Add swipe gesture for position navigation on mobile
    if (window.innerWidth <= 768) {
        let startX = 0;
        let currentPositionIndex = 0;
        const positions = document.querySelectorAll('.position-section');
        
        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        document.addEventListener('touchend', function(e) {
            const endX = e.changedTouches[0].clientX;
            const diffX = startX - endX;
            
            // Swipe threshold
            if (Math.abs(diffX) > 50) {
                if (diffX > 0 && currentPositionIndex < positions.length - 1) {
                    // Swipe left - next position
                    currentPositionIndex++;
                    positions[currentPositionIndex].scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                } else if (diffX < 0 && currentPositionIndex > 0) {
                    // Swipe right - previous position
                    currentPositionIndex--;
                    positions[currentPositionIndex].scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            }
        });
    }
}

function debugVoterPage() {
    console.log('=== Voter Page Debug Info ===');
    console.log('Has Voted:', hasVoted);
    console.log('Election Start Date (UTC):', new Date(electionStartDate).toISOString());
        console.log('Election End Date (UTC):', new Date(electionEndDate).toISOString());
        const currentTime = new Date().getTime();
        console.log('Current Time (UTC):', new Date(currentTime).toISOString());
        const timeToStart = electionStartDate - currentTime;
        const timeToEnd = electionEndDate - currentTime;
    if (timeToStart > 0) {
        console.log('Time Until Election Starts:', formatTime(timeToStart));
    } else if (timeToEnd > 0) {
        console.log('Time Remaining to Vote:', formatTime(timeToEnd));
    } else {
        console.log('Election Status: Ended');
    }
    console.log('Positions:', positions);
    console.log('Selected Candidates:', selectedCandidates);
}

// Expose debug function to global scope for development
window.debugVoterPage = debugVoterPage;

// Log initialization
console.log('Voter page JavaScript loaded successfully');