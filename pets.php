<?php
session_start();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dzīvnieki - SirdsPaws</title>
    <link rel="stylesheet" href="index.css">
    <style>
        /* Основные стили */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9ff;
            color: #333;
            line-height: 1.6;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }

        .hidden {
            display: none !important;
        }

        /* Search Section */
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 2.5rem;
        }

        .search-container {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
        }

        .search-input,
        .filter-select {
            padding: 1rem 1.3rem;
            border: 2px solid #e8ecf1;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input {
            background: #f8f9ff;
        }

        .search-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .search-input::placeholder {
            color: #999;
        }

        .filter-select {
            cursor: pointer;
            font-weight: 500;
            color: #555;
        }

        /* Pets Grid */
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        /* Pet Card */
        .pet-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
        }

        .pet-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        .pet-image {
            width: 100%;
            height: 280px;
            background: linear-gradient(135deg, #e8ecf3 0%, #d6dce6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            position: relative;
            overflow: hidden;
        }

        .pet-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.1) 100%);
        }

        .pet-info {
            padding: 1.5rem;
        }

        .pet-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .pet-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.3rem;
        }

        .pet-type {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .pet-details {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.95rem;
        }

        .pet-detail {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .pet-detail::before {
            content: '•';
            color: #667eea;
            font-weight: bold;
        }

        .pet-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.2rem;
            font-size: 0.95rem;
        }

        .pet-actions {
            display: flex;
            gap: 0.8rem;
        }

        .pet-actions .btn {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            font-size: 0.9rem;
        }

        .btn {
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-adopt {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-favorite {
            background: #f8f9ff;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-favorite.favorited {
            background: #667eea;
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 24px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .close {
            position: absolute;
            right: 2rem;
            top: 2rem;
            color: white;
            font-size: 2rem;
            font-weight: 300;
            cursor: pointer;
            transition: transform 0.2s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }

        .close:hover {
            transform: rotate(90deg) scale(1.1);
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 2.5rem;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #e8ecf1;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Pet Modal Details */
        .pet-modal-image {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #e8ecf3 0%, #d6dce6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
            margin-bottom: 2rem;
        }

        .pet-modal-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
            padding: 2rem;
            background: #f8f9ff;
            border-radius: 16px;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .detail-value {
            font-size: 1.2rem;
            color: #1a1a1a;
            font-weight: 700;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.8rem;
        }

        .empty-state p {
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .pets-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .search-container {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .pets-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .modal-header {
                padding: 1.5rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .close {
                right: 1rem;
                top: 1rem;
            }

            .pet-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .pet-name {
                font-size: 1.3rem;
            }

            .pet-image {
                height: 220px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="container">
        <section class="main-content">
            <div class="search-section">
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Meklēt dzīvniekus pēc vārda..." onkeyup="searchPets()">
                    <select class="filter-select" onchange="filterPets('type')">
                        <option value="">Visi dzīvnieki</option>
                        <option value="suns">Suņi</option>
                        <option value="kaķis">Kaķi</option>
                        <option value="trusis">Truši</option>
                    </select>
                    <select class="filter-select" onchange="filterPets('age')">
                        <option value="">Jebkurš vecums</option>
                        <option value="mazulis">Mazuļi (0-1 gads)</option>
                        <option value="jauns">Jauni (1-3 gadi)</option>
                        <option value="pieaudzis">Pieauguši (3+ gadi)</option>
                    </select>
                </div>
            </div>
            <div class="pets-grid" id="petsGrid"></div>
        </section>
    </main>

    <!-- Dzīvnieka profila modalais logs -->
    <div id="petModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="hideModal('petModal')">&times;</span>
                <h2 id="petModalName"></h2>
            </div>
            <div class="modal-body" id="petModalBody"></div>
        </div>
    </div>

    <!-- Adopcijas pieteikuma modalais logs -->
    <div id="adoptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="hideModal('adoptModal')">&times;</span>
                <h2>Adopcijas pieteikums</h2>
            </div>
            <div class="modal-body">
                <form onsubmit="submitAdoption(event)">
                    <div class="form-group">
                        <label>Dzīvesvieta (adrese):</label>
                        <input type="text" id="adoptAddress" required>
                    </div>
                    <div class="form-group">
                        <label>Dzīvokļa/mājas tips:</label>
                        <select id="adoptHousing" required>
                            <option value="">Izvēlies...</option>
                            <option value="dzivoklis">Dzīvoklis</option>
                            <option value="maja">Māja</option>
                            <option value="maja-ar-darzu">Māja ar dārzu</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pieredze ar dzīvniekiem:</label>
                        <textarea id="adoptExperience" rows="3" placeholder="Apraksti savu pieredzi ar dzīvniekiem..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Ģimenes sastāvs:</label>
                        <textarea id="adoptFamily" rows="3" placeholder="Cik cilvēku dzīvo mājās, vai ir bērni, citi dzīvnieki..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Motivācija:</label>
                        <textarea id="adoptMotivation" rows="3" placeholder="Kāpēc vēlies adoptēt šo dzīvnieku?" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-adopt" style="width: 100%; padding: 0.8rem;">Nosūtīt pieteikumu</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const samplePets = [
            {
                id: 1,
                name: 'Reksīts',
                type: 'suns',
                age: '2 gadi',
                breed: 'Labradoru retrivers',
                gender: 'Tēviņš',
                description: 'Draudzīgs un enerģisks suns, kas mīl spēlēties un iet pastaigās.',
                emoji: '🐕'
            },
            {
                id: 2,
                name: 'Minka',
                type: 'kaķis',
                age: '1 gads',
                breed: 'Britu īsspalvainā',
                gender: 'Mātīte',
                description: 'Maiga un mīļa kaķīte, kas mīl gulēt un ļauties.',
                emoji: '🐱'
            },
            {
                id: 3,
                name: 'Bounce',
                type: 'trusis',
                age: '6 mēneši',
                breed: 'Holandes pundurtrušis',
                gender: 'Tēviņš',
                description: 'Aktīvs un ziņkārīgs trušītis, kas mīl burkānus un zaļumus.',
                emoji: '🐰'
            },
            {
                id: 4,
                name: 'Bella',
                type: 'suns',
                age: '5 gadi',
                breed: 'Vācu aitu suns',
                gender: 'Mātīte',
                description: 'Uzticīga un gudra suņa meitene, lieliski piemērota ģimenēm ar bērniem.',
                emoji: '🐕'
            },
            {
                id: 5,
                name: 'Pusītis',
                type: 'kaķis',
                age: '3 gadi',
                breed: 'Maine Coon',
                gender: 'Tēviņš',
                description: 'Liels un mīlīgs kaķis ar maigu raksturu, mīl uzmanību.',
                emoji: '🐱'
            },
            {
                id: 6,
                name: 'Luna',
                type: 'kaķis',
                age: '4 mēneši',
                breed: 'Persiešu kaķis',
                gender: 'Mātīte',
                description: 'Rotaļīga un jautra kaķēns, kas meklē mīlošu māju.',
                emoji: '🐱'
            },
            {
                id: 7,
                name: 'Makss',
                type: 'suns',
                age: '7 gadi',
                breed: 'Bīgls',
                gender: 'Tēviņš',
                description: 'Mierīgs un piedzīvojis suns, kas mīl ilgas pastaigās un smaržot.',
                emoji: '🐕'
            },
            {
                id: 8,
                name: 'Sniegpārsliņa',
                type: 'trusis',
                age: '1 gads',
                breed: 'Lionhead trusis',
                gender: 'Mātīte',
                description: 'Baltā un pūkaina trušīte ar lielām ausīm, ļoti sociāla.',
                emoji: '🐰'
            },
            {
                id: 9,
                name: 'Čārlis',
                type: 'suns',
                age: '3 gadi',
                breed: 'Franču buldogs',
                gender: 'Tēviņš',
                description: 'Kompakts un mīļš sunītis, ideāls dzīvoklim.',
                emoji: '🐕'
            },
            {
                id: 10,
                name: 'Zosja',
                type: 'kaķis',
                age: '6 gadi',
                breed: 'Siāmas kaķis',
                gender: 'Mātīte',
                description: 'Eleganta un runīga kaķene, kas mīl būt uzmanības centrā.',
                emoji: '🐱'
            }
        ];

        let allPets = [...samplePets];
        let currentFilters = { type: '', age: '', search: '' };

        function displayPets() {
            const grid = document.getElementById('petsGrid');
            const filteredPets = filterPetsList();

            if (filteredPets.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <div class="empty-state-icon">🐾</div>
                        <h3>Nav atrasti dzīvnieki</h3>
                        <p>Mēģini mainīt filtrus vai meklēšanas kritērijus</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = filteredPets.map(pet => `
                <div class="pet-card" onclick="showPetDetails(${pet.id})">
                    <div class="pet-image">${pet.emoji}</div>
                    <div class="pet-info">
                        <div class="pet-header">
                            <h3 class="pet-name">${pet.name}</h3>
                            <span class="pet-type">${pet.type}</span>
                        </div>
                        <div class="pet-details">
                            <span class="pet-detail">${pet.age}</span>
                            <span class="pet-detail">${pet.gender}</span>
                            <span class="pet-detail">${pet.breed}</span>
                        </div>
                        <p class="pet-description">${pet.description}</p>
                        <div class="pet-actions">
                            <button class="btn btn-adopt" onclick="event.stopPropagation(); openAdoptModal(${pet.id})">Adoptēt</button>
                            <button class="btn btn-favorite" onclick="event.stopPropagation(); toggleFavorite(${pet.id})">❤</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function filterPetsList() {
            return allPets.filter(pet => {
                const matchType = !currentFilters.type || pet.type === currentFilters.type;
                const matchSearch = !currentFilters.search || 
                    pet.name.toLowerCase().includes(currentFilters.search.toLowerCase());
                return matchType && matchSearch;
            });
        }

        function searchPets() {
            currentFilters.search = event.target.value;
            displayPets();
        }

        function filterPets(filterType) {
            const value = event.target.value;
            if (filterType === 'type') {
                currentFilters.type = value;
            } else if (filterType === 'age') {
                currentFilters.age = value;
            }
            displayPets();
        }

        function showPetDetails(petId) {
            const pet = allPets.find(p => p.id === petId);
            if (!pet) return;

            document.getElementById('petModalName').textContent = pet.name;
            document.getElementById('petModalBody').innerHTML = `
                <div class="pet-modal-image">${pet.emoji}</div>
                <div class="pet-modal-details">
                    <div class="detail-item">
                        <div class="detail-label">Tips</div>
                        <div class="detail-value">${pet.type}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Vecums</div>
                        <div class="detail-value">${pet.age}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Dzimums</div>
                        <div class="detail-value">${pet.gender}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Šķirne</div>
                        <div class="detail-value">${pet.breed}</div>
                    </div>
                </div>
                <p style="line-height: 1.8; color: #666;">${pet.description}</p>
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button class="btn btn-adopt" style="flex: 1; padding: 0.8rem;" onclick="hideModal('petModal'); openAdoptModal(${pet.id})">Adoptēt</button>
                    <button class="btn btn-favorite" style="flex: 1; padding: 0.8rem;" onclick="toggleFavorite(${pet.id})">Pievienot favorītiem</button>
                </div>
            `;
            showModal('petModal');
        }

        let currentAdoptPetId = null;

        function openAdoptModal(petId) {
            currentAdoptPetId = petId;
            showModal('adoptModal');
        }

        function submitAdoption(event) {
            event.preventDefault();
            
            const application = {
                petId: currentAdoptPetId,
                address: document.getElementById('adoptAddress').value,
                housing: document.getElementById('adoptHousing').value,
                experience: document.getElementById('adoptExperience').value,
                family: document.getElementById('adoptFamily').value,
                motivation: document.getElementById('adoptMotivation').value,
                date: new Date().toISOString()
            };

            console.log('Pieteikums:', application);
            alert('Paldies! Tavs pieteikums ir nosūtīts. Mēs sazināsimies ar tevi tuvākajā laikā!');
            
            hideModal('adoptModal');
            event.target.reset();
        }

        function toggleFavorite(petId) {
            const btn = event.target;
            btn.classList.toggle('favorited');
            const isFavorited = btn.classList.contains('favorited');
            console.log(isFavorited ? 'Pievienots favorītiem!' : 'Izņemts no favorītiem!');
        }

        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            displayPets();
        });

        </script>
        
        <footer style="background: #1a1a2e; color: white; padding: 3rem 0 1rem 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; margin-bottom: 2rem;">
            <div>
                <h3 style="color: #667eea; margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 700;">
                    🐾 SirdsPaws
                </h3>
                <p style="margin-bottom: 1.5rem; line-height: 1.8; color: #b8b8c8;">
                    Palīdzam dzīvniekiem atrast mīlošas mājas un cilvēkiem - uzticamus draugus.
                </p>
            </div>

            <div>
                <h4 style="color: white; margin-bottom: 1rem; font-weight: 600;">Kontakti</h4>
                <div style="color: #b8b8c8; line-height: 2;">
                    <div>📍 Daugavgrīvas iela 123, Rīga</div>
                    <div>📞 +371 26 123 456</div>
                    <div>✉️ info@sirdspaws.lv</div>
                </div>
            </div>

            <div>
                <h4 style="color: white; margin-bottom: 1rem; font-weight: 600;">Saites</h4>
                <div style="color: #b8b8c8; line-height: 2;">
                    <div><a href="pets.php" style="color: #b8b8c8; text-decoration: none;">Dzīvnieki</a></div>
                    <div><a href="events.html" style="color: #b8b8c8; text-decoration: none;">Pasākumi</a></div>
                    <div><a href="register.html" style="color: #b8b8c8; text-decoration: none;">Reģistrēties</a></div>
                </div>
            </div>

            <div>
                <h4 style="color: white; margin-bottom: 1rem; font-weight: 600;">Darba laiks</h4>
                <div style="color: #b8b8c8; line-height: 2;">
                    <div>P-Pk: 9:00 - 18:00</div>
                    <div>S: 10:00 - 16:00</div>
                    <div>Sv: 10:00 - 14:00</div>
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem; text-align: center; color: #b8b8c8;">
            <p style="margin: 0;">© 2025 SirdsPaws. Radīts ar ❤️ dzīvniekiem</p>
        </div>
    </div>
</footer>
    
</body>
</html>