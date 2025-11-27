<!-- <nav>
    <div class="ham-menu">
        <span></span>
        <span></span>
        <span></span>
    </div>
</nav> -->
<div class="sidebar">
    <h2 class="app-title"><img src="assets/images/logo.png" alt="Logo"></img> TrackSmart</h2>

    <a href="index.php"class="menu-item">

        <!-- <img src="assets/svg icons/dashboardIcon.svg" class="sidebar-icon" alt="Dashboard icon"></img> -->
        <svg class="sidebar-icon-svg" width="30" height="30" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.5 2.5H3.33333C2.8731 2.5 2.5 2.8731 2.5 3.33333V9.16667C2.5 9.6269 2.8731 10 3.33333 10H7.5C7.96024 10 8.33333 9.6269 8.33333 9.16667V3.33333C8.33333 2.8731 7.96024 2.5 7.5 2.5Z" stroke="#364153" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16.666 2.5H12.4993C12.0391 2.5 11.666 2.8731 11.666 3.33333V5.83333C11.666 6.29357 12.0391 6.66667 12.4993 6.66667H16.666C17.1263 6.66667 17.4993 6.29357 17.4993 5.83333V3.33333C17.4993 2.8731 17.1263 2.5 16.666 2.5Z" stroke="#364153" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16.666 10H12.4993C12.0391 10 11.666 10.3731 11.666 10.8333V16.6667C11.666 17.1269 12.0391 17.5 12.4993 17.5H16.666C17.1263 17.5 17.4993 17.1269 17.4993 16.6667V10.8333C17.4993 10.3731 17.1263 10 16.666 10Z" stroke="#364153" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M7.5 13.3335H3.33333C2.8731 13.3335 2.5 13.7066 2.5 14.1668V16.6668C2.5 17.1271 2.8731 17.5002 3.33333 17.5002H7.5C7.96024 17.5002 8.33333 17.1271 8.33333 16.6668V14.1668C8.33333 13.7066 7.96024 13.3335 7.5 13.3335Z" stroke="#364153" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
        Dashboard
    </a>



    <a href="add_income.php" class="menu-item"> <img src="assets/images/addincomeIcon.png" class="sidebar-icon" alt="Income icon"></img>Add Income</a>

    <a href="transactions.php" class="menu-item"> <img src="assets/images/transacIcon.png" class="sidebar-icon" alt="Transac icon"></img>Transactions</a>

    <a href="expenses.php" class="menu-item"> <img src="assets/images/budgetsIcon.png" class="sidebar-icon" alt="Budget here"></img>Budgets</a>

    <a href="reports.php" class="menu-item"> <img src="assets/images/reportIcon.png" class="sidebar-icon" alt="Report here"></img>Reports</a>

    <a href="todo.php" class="menu-item"> <img src="assets/images/todoIcon.png" class="sidebar-icon" alt="Todo here"></img>To-Do List</a>

    <a href="profile.php" class="menu-item"> <img src="assets/images/settingsIcon.png" class="sidebar-icon" alt="Image here"></img>Settings</a>

    <a href="#" onclick="confirmLogout()" class="menu-item logout">Logout</a>

</div>

<div id="logoutModal" class="logout-overlay">
  <div class="logout-box">
      <h3>Are you sure you want to logout?</h3>
      <div class="logout-actions">
          <button class="cancel-logout" onclick="closeLogout()">Cancel</button>
          <button class="confirm-logout" onclick="proceedLogout()">Yes, Logout</button>
      </div>
  </div>
</div>

<script>
function confirmLogout() {
    document.getElementById("logoutModal").style.display = "flex";
}
function closeLogout() {
    document.getElementById("logoutModal").style.display = "none";
}
function proceedLogout() {
    window.location.href = "logout.php"; 
}
</script>
<!-- <script>
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");

    menuToggle.addEventListener("click", () => {
        sidebar.classList.toggle("active");
    });
</script> -->