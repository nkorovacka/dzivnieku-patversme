// Globālie mainīgie
let currentUser = null
let pets = []
let applications = []
let users = []
let events = []
let favorites = []
let selectedDonationAmount = 0
let currentPetForDonation = null

// Inicializācija
document.addEventListener("DOMContentLoaded", () => {
  initializeData()
  updateAuthButtons()
})

// Datu inicializācija
function initializeData() {
  pets = [
    {
      id: 1,
      name: "Bella",
      type: "suns",
      age: "mazulis",
      gender: "mātīte",
      description:
        "Bella ir ļoti draudzīga un aktīva kucēna. Viņa mīl spēlēties un ir lieliski piemērota ģimenēm ar bērniem.",
      health: "Vakcinēta, sterilizēta, čipēta",
      status: "pieejams",
      emoji: "🐕",
    },
    {
      id: 2,
      name: "Whiskers",
      type: "kaķis",
      age: "pieaudzis",
      gender: "tēviņš",
      description: "Whiskers ir mierīgs un neatkarīgs kaķis. Viņš mīl gulēt saulē un tikt glāstīts.",
      health: "Vakcinēts, kastrēts, čipēts",
      status: "pieejams",
      emoji: "🐱",
    },
    {
      id: 3,
      name: "Luna",
      type: "kaķis",
      age: "jauns",
      gender: "mātīte",
      description: "Luna ir rotaļīga un ziņkārīga kaķenīte. Viņa ir sociāla un labi sadzīvo ar citiem dzīvniekiem.",
      health: "Vakcinēta, sterilizēta, čipēta",
      status: "rezervēts",
      emoji: "🐱",
    },
    {
      id: 4,
      name: "Buddy",
      type: "suns",
      age: "pieaudzis",
      gender: "tēviņš",
      description: "Buddy ir uzticīgs un aizsargājošs suns. Viņš ir lielisks apsargs un kompanjons.",
      health: "Vakcinēts, kastrēts, čipēts",
      status: "adoptēts",
      emoji: "🐕",
    },
    {
      id: 5,
      name: "Snowball",
      type: "trusis",
      age: "jauns",
      gender: "mātīte",
      description: "Snowball ir maigs un kluss trusis. Viņa mīl burkānus un ir ļoti sociāla.",
      health: "Vakcinēta, sterilizēta",
      status: "pieejams",
      emoji: "🐰",
    },
    {
      id: 6,
      name: "Max",
      type: "suns",
      age: "jauns",
      gender: "tēviņš",
      description: "Max ir enerģisks un gudrs suns. Viņš mīl skriet un spēlēties ar bumbiņu.",
      health: "Vakcinēts, kastrēts, čipēts",
      status: "pieejams",
      emoji: "🐕‍🦺",
    },
    {
      id: 7,
      name: "Mittens",
      type: "kaķis",
      age: "mazulis",
      gender: "mātīte",
      description: "Mittens ir maza un rotaļīga kaķenīte. Viņa ir ļoti mīļa un mīl tikt glāstīta.",
      health: "Vakcinēta, čipēta",
      status: "pieejams",
      emoji: "🐱",
    },
    {
      id: 8,
      name: "Charlie",
      type: "suns",
      age: "pieaudzis",
      gender: "tēviņš",
      description: "Charlie ir mierīgs un gudrs suns. Viņš ir perfekts kompanjons senioriem.",
      health: "Vakcinēts, kastrēts, čipēts",
      status: "pieejams",
      emoji: "🐕",
    },
    {
      id: 9,
      name: "Fluffy",
      type: "trusis",
      age: "pieaudzis",
      gender: "tēviņš",
      description: "Fluffy ir liels un mīksts trusis. Viņš ir ļoti draudzīgs un mīl uzmanību.",
      health: "Vakcinēts, kastrēts",
      status: "rezervēts",
      emoji: "🐰",
    },
    {
      id: 10,
      name: "Shadow",
      type: "kaķis",
      age: "jauns",
      gender: "tēviņš",
      description: "Shadow ir noslēpumains un elegants kaķis. Viņš mīl klusas vietas un novērošanu.",
      health: "Vakcinēts, kastrēts, čipēts",
      status: "pieejams",
      emoji: "🐈‍⬛",
    },
    {
      id: 11,
      name: "Daisy",
      type: "suns",
      age: "mazulis",
      gender: "mātīte",
      description: "Daisy ir skaista un rotaļīga kucēna. Viņa mīl bērnus un ir ļoti sociāla.",
      health: "Vakcinēta, čipēta",
      status: "pieejams",
      emoji: "🐕",
    },
    {
      id: 12,
      name: "Ginger",
      type: "kaķis",
      age: "pieaudzis",
      gender: "mātīte",
      description: "Ginger ir skaista rudā kaķe. Viņa ir neatkarīga, bet mīl glāstīšanu.",
      health: "Vakcinēta, sterilizēta, čipēta",
      status: "pieejams",
      emoji: "🐱",
    },
  ]

  events = [
    {
      id: 1,
      title: "Dzīvnieku adopcijas diena",
      date: "2024-03-15",
      time: "10:00",
      location: "Daugavgrīvas parks",
      description: "Liels pasākums, kur varēsi iepazīties ar mūsu dzīvniekiem un atrast savu nākamo labāko draugu!",
    },
    {
      id: 2,
      title: "Labdarības skrējiens suņiem",
      date: "2024-03-22",
      time: "09:00",
      location: "Mežaparks",
      description: "Piedalies skrējienā kopā ar savu suni un atbalsti patversmi!",
    },
  ]

  users = [
    {
      id: 1,
      name: "Anna",
      surname: "Bērziņa",
      email: "admin@sirdspaws.lv",
      phone: "+371 26123456",
      password: "admin123",
      role: "admin",
      registrationDate: "2024-01-01",
    },
  ]

  applications = [
    {
      id: 1,
      userId: 1,
      petId: 1,
      status: "izskatīšanā",
      date: "2024-03-01",
      address: "Rīga, Brīvības iela 123",
      housing: "dzivoklis",
      experience: "Man ir bijuši suņi jau 10 gadus",
      family: "Dzīvoju ar vīru un diviem bērniem",
      motivation: "Vēlamies dot mājvietu un mīlestību",
    },
  ]
}

// Modālo logu funkcijas
function showModal(modalId) {
  document.getElementById(modalId).style.display = "block"
}

function hideModal(modalId) {
  document.getElementById(modalId).style.display = "none"
}

// Pieslēgšanās
function login(event) {
  event.preventDefault()
  const email = document.getElementById("loginEmail").value
  const password = document.getElementById("loginPassword").value

  const user = users.find((u) => u.email === email && u.password === password)

  if (user) {
    currentUser = user
    localStorage.setItem("currentUser", JSON.stringify(user))
    alert("Veiksmīgi pieslēdzies!")
    window.location.href = "index.html"
  } else {
    alert("Nepareizs e-pasts vai parole!")
  }
}

// Reģistrācija
function register(event) {
  event.preventDefault()
  const name = document.getElementById("registerName").value
  const surname = document.getElementById("registerSurname").value
  const email = document.getElementById("registerEmail").value
  const phone = document.getElementById("registerPhone").value
  const password = document.getElementById("registerPassword").value
  const confirmPassword = document.getElementById("registerConfirmPassword").value

  if (password !== confirmPassword) {
    alert("Paroles nesakrīt!")
    return
  }

  if (users.find((u) => u.email === email)) {
    alert("Lietotājs ar šo e-pastu jau eksistē!")
    return
  }

  const newUser = {
    id: users.length + 1,
    name: name,
    surname: surname,
    email: email,
    phone: phone,
    password: password,
    role: "user",
    registrationDate: new Date().toISOString().split("T")[0],
  }

  users.push(newUser)
  currentUser = newUser
  localStorage.setItem("currentUser", JSON.stringify(newUser))
  alert("Konts veiksmīgi izveidots!")
  window.location.href = "index.html"
}

// Izrakstīšanās
function logout() {
  currentUser = null
  favorites = []
  localStorage.removeItem("currentUser")
  localStorage.removeItem("favorites")
  alert("Tu esi veiksmīgi izrakstījies!")
  window.location.href = "index.html"
}

// Autentifikācijas pogu atjaunošana
function updateAuthButtons() {
  // Ielādē lietotāju no localStorage
  const savedUser = localStorage.getItem("currentUser")
  if (savedUser) {
    currentUser = JSON.parse(savedUser)
  }

  // Ielādē favorītus no localStorage
  const savedFavorites = localStorage.getItem("favorites")
  if (savedFavorites) {
    favorites = JSON.parse(savedFavorites)
  }

  const loginBtn = document.querySelector('a[href="login.html"]')
  const registerBtn = document.querySelector('a[href="register.html"]')
  const adminBtn = document.getElementById("adminBtn")
  const logoutBtn = document.getElementById("logoutBtn")

  if (currentUser) {
    if (loginBtn) loginBtn.style.display = "none"
    if (registerBtn) registerBtn.style.display = "none"
    if (logoutBtn) logoutBtn.style.display = "inline-block"

    if (currentUser.role === "admin" && adminBtn) {
      adminBtn.style.display = "inline-block"
    }
  } else {
    if (loginBtn) loginBtn.style.display = "inline-block"
    if (registerBtn) registerBtn.style.display = "inline-block"
    if (logoutBtn) logoutBtn.style.display = "none"
    if (adminBtn) adminBtn.style.display = "none"
  }
}

// Dzīvnieku attēlošana
function displayPets(filteredPets = null) {
  const grid = document.getElementById("petsGrid")
  if (!grid) return

  const petsToShow = filteredPets || pets
  grid.innerHTML = ""

  petsToShow.forEach((pet) => {
    const petCard = createPetCard(pet)
    grid.appendChild(petCard)
  })
}

// Dzīvnieka kartes izveide
function createPetCard(pet) {
  const card = document.createElement("div")
  card.className = "pet-card"
  card.style.position = "relative"

  const statusClass =
    pet.status === "pieejams" ? "status-available" : pet.status === "rezervēts" ? "status-reserved" : "status-adopted"
  const statusText = pet.status === "pieejams" ? "Pieejams" : pet.status === "rezervēts" ? "Rezervēts" : "Adoptēts"

  card.innerHTML = `
        <button class="favorite-btn ${favorites.includes(pet.id) ? "active" : ""}" 
                onclick="toggleFavorite(${pet.id})">
            ${favorites.includes(pet.id) ? "❤️" : "🤍"}
        </button>
        <div class="pet-image">${pet.emoji}</div>
        <div class="pet-info">
            <div class="pet-name">${pet.name}</div>
            <div class="pet-details">
                ${pet.type.charAt(0).toUpperCase() + pet.type.slice(1)} • 
                ${pet.age === "mazulis" ? "Mazulis" : pet.age === "jauns" ? "Jauns" : "Pieaudzis"} • 
                ${pet.gender}
            </div>
            <div class="pet-status ${statusClass}">${statusText}</div>
            <p>${pet.description.substring(0, 100)}...</p>
        </div>
    `

  card.addEventListener("click", (e) => {
    if (!e.target.classList.contains("favorite-btn")) {
      showPetDetails(pet)
    }
  })

  return card
}

// Dzīvnieka detalizētas informācijas rādīšana
function showPetDetails(pet) {
  document.getElementById("petModalName").textContent = pet.name
  document.getElementById("donationPetName").textContent = pet.name
  currentPetForDonation = pet

  const content = document.getElementById("petModalContent")
  content.innerHTML = `
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">${pet.emoji}</div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
            <div><strong>Suga:</strong> ${pet.type.charAt(0).toUpperCase() + pet.type.slice(1)}</div>
            <div><strong>Vecums:</strong> ${pet.age === "mazulis" ? "Mazulis" : pet.age === "jauns" ? "Jauns" : "Pieaudzis"}</div>
            <div><strong>Dzimums:</strong> ${pet.gender}</div>
            <div><strong>Statuss:</strong> ${pet.status}</div>
        </div>
        <div style="margin-bottom: 2rem;">
            <h3>Apraksts</h3>
            <p>${pet.description}</p>
        </div>
        <div style="margin-bottom: 2rem;">
            <h3>Veselības informācija</h3>
            <p>${pet.health}</p>
        </div>
    `

  showModal("petModal")
}

// Meklēšana
function searchPets() {
  const searchTerm = document.querySelector(".search-input").value.toLowerCase()
  const filteredPets = pets.filter(
    (pet) => pet.name.toLowerCase().includes(searchTerm) || pet.description.toLowerCase().includes(searchTerm),
  )
  displayPets(filteredPets)
}

// Filtrēšana
function filterPets(filterType) {
  const filters = document.querySelectorAll(".filter-select")
  let filteredPets = pets

  filters.forEach((filter) => {
    const value = filter.value
    if (value) {
      if (filter.onchange.toString().includes("type")) {
        filteredPets = filteredPets.filter((pet) => pet.type === value)
      } else if (filter.onchange.toString().includes("age")) {
        filteredPets = filteredPets.filter((pet) => pet.age === value)
      }
    }
  })

  displayPets(filteredPets)
}

// Favorītu pārvaldība
function toggleFavorite(petId) {
  if (!currentUser) {
    alert("Lai pievienotu favorītos, vispirms pieslēdzies!")
    return
  }

  const index = favorites.indexOf(petId)
  if (index > -1) {
    favorites.splice(index, 1)
  } else {
    favorites.push(petId)
  }

  // Saglabā favorītus localStorage
  localStorage.setItem("favorites", JSON.stringify(favorites))

  // Atjaunojam kartes
  displayPets()
  if (document.getElementById("favoritesGrid")) {
    displayFavorites()
  }
}

// Favorītu attēlošana
function displayFavorites() {
  const grid = document.getElementById("favoritesGrid")
  if (!grid) return

  const favoritePets = pets.filter((pet) => favorites.includes(pet.id))

  if (favoritePets.length === 0) {
    grid.innerHTML = '<p style="text-align: center; grid-column: 1/-1;">Nav pievienotu favorītu dzīvnieku.</p>'
    return
  }

  grid.innerHTML = ""
  favoritePets.forEach((pet) => {
    const petCard = createPetCard(pet)
    grid.appendChild(petCard)
  })
}

// Adopcijas pieteikums
function submitAdoption(event) {
  event.preventDefault()

  if (!currentUser) {
    alert("Lai pieteiktos adopcijai, vispirms pieslēdzies!")
    return
  }

  const newApplication = {
    id: applications.length + 1,
    userId: currentUser.id,
    petId: currentPetForDonation.id,
    status: "izskatīšanā",
    date: new Date().toISOString().split("T")[0],
    address: document.getElementById("adoptAddress").value,
    housing: document.getElementById("adoptHousing").value,
    experience: document.getElementById("adoptExperience").value,
    family: document.getElementById("adoptFamily").value,
    motivation: document.getElementById("adoptMotivation").value,
  }

  applications.push(newApplication)
  hideModal("adoptModal")
  hideModal("petModal")
  alert("Pieteikums veiksmīgi nosūtīts! Mēs ar Tevi sazināsimies tuvākajā laikā.")

  // Notīrām formu
  document.getElementById("adoptAddress").value = ""
  document.getElementById("adoptHousing").value = ""
  document.getElementById("adoptExperience").value = ""
  document.getElementById("adoptFamily").value = ""
  document.getElementById("adoptMotivation").value = ""
}

// Lietotāja pieteikumu attēlošana
function displayUserApplications() {
  const table = document.getElementById("applicationsTable")
  if (!table) return

  if (!currentUser) {
    table.innerHTML = '<tr><td colspan="4">Pieslēdzies, lai redzētu savus pieteikumus</td></tr>'
    return
  }

  const userApplications = applications.filter((app) => app.userId === currentUser.id)

  if (userApplications.length === 0) {
    table.innerHTML = '<tr><td colspan="4">Nav pieteikumu</td></tr>'
    return
  }

  table.innerHTML = ""
  userApplications.forEach((app) => {
    const pet = pets.find((p) => p.id === app.petId)
    const row = document.createElement("tr")
    row.innerHTML = `
            <td>${pet ? pet.name : "Nav atrasts"}</td>
            <td>${app.date}</td>
            <td>${app.status}</td>
            <td>
                <button class="btn btn-secondary" onclick="viewApplication(${app.id})">Skatīt</button>
            </td>
        `
    table.appendChild(row)
  })
}

// Pasākumu attēlošana
function displayEvents() {
  const grid = document.getElementById("eventsGrid")
  if (!grid) return

  grid.innerHTML = ""

  events.forEach((event) => {
    const eventCard = document.createElement("div")
    eventCard.className = "pet-card"
    eventCard.innerHTML = `
            <div class="pet-image">📅</div>
            <div class="pet-info">
                <div class="pet-name">${event.title}</div>
                <div class="pet-details">
                    ${event.date} • ${event.time} • ${event.location}
                </div>
                <p>${event.description}</p>
                <button class="btn btn-primary" onclick="registerForEvent(${event.id})" style="width: 100%; margin-top: 1rem;">
                    Pieteikties
                </button>
            </div>
        `
    grid.appendChild(eventCard)
  })
}

// Ziedojumu funkcionalitāte
function processDonation() {
  if (selectedDonationAmount <= 0) {
    const amount = prompt("Ievadi ziedojuma summu (EUR):")
    if (amount && !isNaN(amount) && Number.parseFloat(amount) > 0) {
      selectedDonationAmount = Number.parseFloat(amount)
    } else {
      alert("Lūdzu, ievadi derīgu summu!")
      return
    }
  }

  alert(`Paldies par ziedojumu €${selectedDonationAmount}! Tev tiks nosūtīts maksājuma saite.`)
  selectedDonationAmount = 0

  // Atiestatām pogas
  document.querySelectorAll(".amount-btn").forEach((btn) => {
    btn.classList.remove("active")
    if (btn.dataset.amount === "custom") {
      btn.textContent = "Cita summa"
    }
  })
}

// Admin funkcijas
function displayAdminData() {
  if (!currentUser || currentUser.role !== "admin") return

  updateStats()
  displayAdminPets()
  displayAdminApplications()
  displayAdminUsers()
  displayAdminEvents()
}

function updateStats() {
  const totalPetsEl = document.getElementById("totalPets")
  const availablePetsEl = document.getElementById("availablePets")
  const adoptedPetsEl = document.getElementById("adoptedPets")
  const pendingApplicationsEl = document.getElementById("pendingApplications")

  if (totalPetsEl) totalPetsEl.textContent = pets.length
  if (availablePetsEl) availablePetsEl.textContent = pets.filter((p) => p.status === "pieejams").length
  if (adoptedPetsEl) adoptedPetsEl.textContent = pets.filter((p) => p.status === "adoptēts").length
  if (pendingApplicationsEl)
    pendingApplicationsEl.textContent = applications.filter((a) => a.status === "izskatīšanā").length
}

function showAdminTab(tabName) {
  document.querySelectorAll(".admin-tab").forEach((tab) => tab.classList.remove("active"))
  document.querySelectorAll(".tab-content").forEach((content) => content.classList.remove("active"))

  document.querySelector(`[onclick="showAdminTab('${tabName}')"]`).classList.add("active")
  document.getElementById(`admin${tabName.charAt(0).toUpperCase() + tabName.slice(1)}Tab`).classList.add("active")
}

function displayAdminPets() {
  const table = document.getElementById("adminPetsTable")
  if (!table) return

  table.innerHTML = ""
  pets.forEach((pet) => {
    const row = document.createElement("tr")
    row.innerHTML = `
            <td>${pet.name}</td>
            <td>${pet.type}</td>
            <td>${pet.age}</td>
            <td>${pet.status}</td>
            <td>
                <button class="btn btn-secondary" onclick="editPet(${pet.id})">Rediģēt</button>
                <button class="btn btn-secondary" onclick="deletePet(${pet.id})">Dzēst</button>
            </td>
        `
    table.appendChild(row)
  })
}

function displayAdminApplications() {
  const table = document.getElementById("adminApplicationsTable")
  if (!table) return

  table.innerHTML = ""
  applications.forEach((app) => {
    const user = users.find((u) => u.id === app.userId)
    const pet = pets.find((p) => p.id === app.petId)
    const row = document.createElement("tr")
    row.innerHTML = `
            <td>${user ? user.name + " " + user.surname : "Nav atrasts"}</td>
            <td>${pet ? pet.name : "Nav atrasts"}</td>
            <td>${app.date}</td>
            <td>${app.status}</td>
            <td>
                <button class="btn btn-secondary" onclick="approveApplication(${app.id})">Apstiprināt</button>
                <button class="btn btn-secondary" onclick="rejectApplication(${app.id})">Noraidīt</button>
            </td>
        `
    table.appendChild(row)
  })
}

function displayAdminUsers() {
  const table = document.getElementById("adminUsersTable")
  if (!table) return

  table.innerHTML = ""
  users.forEach((user) => {
    const row = document.createElement("tr")
    row.innerHTML = `
            <td>${user.name} ${user.surname}</td>
            <td>${user.email}</td>
            <td>${user.registrationDate}</td>
            <td>
                <button class="btn btn-secondary" onclick="viewUser(${user.id})">Skatīt</button>
            </td>
        `
    table.appendChild(row)
  })
}

function displayAdminEvents() {
  const table = document.getElementById("adminEventsTable")
  if (!table) return

  table.innerHTML = ""
  events.forEach((event) => {
    const row = document.createElement("tr")
    row.innerHTML = `
            <td>${event.title}</td>
            <td>${event.date}</td>
            <td>${event.location}</td>
            <td>
                <button class="btn btn-secondary" onclick="editEvent(${event.id})">Rediģēt</button>
                <button class="btn btn-secondary" onclick="deleteEvent(${event.id})">Dzēst</button>
            </td>
        `
    table.appendChild(row)
  })
}

// Ziedojumu summas pogu funkcionalitāte
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".amount-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".amount-btn").forEach((b) => b.classList.remove("active"))
      this.classList.add("active")
      selectedDonationAmount = this.dataset.amount === "custom" ? 0 : Number.parseInt(this.dataset.amount)

      if (this.dataset.amount === "custom") {
        const customAmount = prompt("Ievadi ziedojuma summu (EUR):")
        if (customAmount && !isNaN(customAmount) && Number.parseFloat(customAmount) > 0) {
          selectedDonationAmount = Number.parseFloat(customAmount)
          this.textContent = `€${selectedDonationAmount}`
        } else {
          this.classList.remove("active")
          selectedDonationAmount = 0
          this.textContent = "Cita summa"
        }
      }
    })
  })
})

// Palīgfunkcijas (stub implementācijas)
function registerForEvent(eventId) {
  if (!currentUser) {
    alert("Lai pieteiktos pasākumam, vispirms pieslēdzies!")
    return
  }
  alert("Pieteikums pasākumam nosūtīts!")
}

function viewApplication(appId) {
  alert("Pieteikuma detaļas...")
}

function addPet(event) {
  event.preventDefault()
  alert("Dzīvnieks pievienots!")
  hideModal("addPetModal")
}

function addEvent(event) {
  event.preventDefault()
  alert("Pasākums pievienots!")
  hideModal("addEventModal")
}

function editPet(petId) {
  alert("Rediģēt dzīvnieku...")
}

function deletePet(petId) {
  if (confirm("Vai tiešām vēlies dzēst šo dzīvnieku?")) {
    alert("Dzīvnieks dzēsts!")
  }
}

function approveApplication(appId) {
  alert("Pieteikums apstiprināts!")
}

function rejectApplication(appId) {
  alert("Pieteikums noraidīts!")
}

function viewUser(userId) {
  alert("Lietotāja detaļas...")
}

function editEvent(eventId) {
  alert("Rediģēt pasākumu...")
}

function deleteEvent(eventId) {
  if (confirm("Vai tiešām vēlies dzēst šo pasākumu?")) {
    alert("Pasākums dzēsts!")
  }
}

function processPetDonation() {
  processDonation()
}
