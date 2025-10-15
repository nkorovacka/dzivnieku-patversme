// Global variables
let currentUser = null

// Check if user is logged in
function checkAuth() {
  const userEmail = localStorage.getItem("userEmail")
  const userRole = localStorage.getItem("userRole")

  if (userEmail && userRole) {
    currentUser = {
      email: userEmail,
      role: userRole,
    }
  }

  updateAuthButtons()
}

// Update auth buttons based on login status
function updateAuthButtons() {
  const authButtons = document.querySelector(".auth-buttons")
  if (!authButtons) return

  if (currentUser) {
    authButtons.innerHTML = `
            <span style="margin-right: 1rem; color: white;">${currentUser.email}</span>
            ${currentUser.role === "admin" ? '<a href="admin.html" class="btn btn-primary" style="margin-right: 0.5rem;">Admin panelis</a>' : ""}
            <button class="btn btn-secondary" onclick="logout()">Iziet</button>
        `
  } else {
    authButtons.innerHTML = `
            <a href="login.html" class="btn btn-primary">Pieslēgties</a>
            <a href="register.html" class="btn btn-secondary">Reģistrēties</a>
        `
  }
}

// Logout function
function logout() {
  fetch("logout.php")
    .then(() => {
      localStorage.removeItem("userEmail")
      localStorage.removeItem("userRole")
      currentUser = null
      window.location.href = "index.html"
    })
    .catch((err) => {
      console.error("Logout error:", err)
      localStorage.removeItem("userEmail")
      localStorage.removeItem("userRole")
      window.location.href = "index.html"
    })
}

// Modal functions
function showModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = "block"
  }
}

function hideModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = "none"
  }
}

// Admin tab switching
function showAdminTab(tabName) {
  // Hide all tabs
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active")
  })
  document.querySelectorAll(".admin-tab").forEach((btn) => {
    btn.classList.remove("active")
  })

  // Show selected tab
  const tabMap = {
    pets: "adminPetsTab",
    applications: "adminApplicationsTab",
    users: "adminUsersTab",
    events: "adminEventsTab",
  }

  const selectedTab = document.getElementById(tabMap[tabName])
  if (selectedTab) {
    selectedTab.classList.add("active")
  }

  // Activate button
  event.target.classList.add("active")
}

// Placeholder functions for admin features
function displayAdminData() {
  console.log("Loading admin data...")
  // This would load pets, applications, events data
}

function updateStats() {
  console.log("Updating statistics...")
  // This would update the stat cards
}

function addPet(event) {
  event.preventDefault()
  console.log("Adding pet...")
  // This would handle pet addition
  hideModal("addPetModal")
}

function addEvent(event) {
  event.preventDefault()
  console.log("Adding event...")
  // This would handle event addition
  hideModal("addEventModal")
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  checkAuth()

  // Close modals when clicking outside
  window.onclick = (event) => {
    if (event.target.classList.contains("modal")) {
      event.target.style.display = "none"
    }
  }
})
