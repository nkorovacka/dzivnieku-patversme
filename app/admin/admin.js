// Check if user is admin
fetch("check_session.php")
  .then((res) => res.json())
  .then((data) => {
    if (!data.isAdmin) {
      window.location.href = "index.html"
    } else {
      loadUsers()
    }
  })
  .catch((err) => {
    console.error("Error checking session:", err)
    window.location.href = "index.html"
  })

// Load users
function loadUsers() {
  fetch("get_users.php")
    .then((res) => res.json())
    .then((users) => {
      document.getElementById("loadingMsg").style.display = "none"
      document.getElementById("usersTable").style.display = "block"

      const tbody = document.getElementById("usersTableBody")
      tbody.innerHTML = ""

      if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nav lietotāju</td></tr>'
        return
      }

      users.forEach((user) => {
        const tr = document.createElement("tr")
        tr.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.email}</td>
                    <td>${user.name || "-"}</td>
                    <td><span class="badge">${user.role}</span></td>
                    <td>${new Date(user.created_at).toLocaleDateString("lv-LV")}</td>
                `
        tbody.appendChild(tr)
      })
    })
    .catch((err) => {
      console.error("Error loading users:", err)
      document.getElementById("loadingMsg").style.display = "none"
      document.getElementById("errorMsg").style.display = "block"
      document.getElementById("errorMsg").textContent = "Kļūda ielādējot lietotājus"
    })
}

// Logout
document.getElementById("logoutBtn").addEventListener("click", () => {
  fetch("logout.php").then(() => {
    window.location.href = "index.html"
  })
})
