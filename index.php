<?php
session_start();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dzīvnieku patversme - SirdsPaws</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

<?php include 'navbar.php'; ?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1>Atrodi savu labāko draugu</h1>
            <p>Simtiem dzīvnieku gaida savu otro iespēju. Kļūsti par viņu ceļa daļu un iegūsti uzticamu draugu uz visu mūžu.</p>
            <a href="pets.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1.2rem 2.5rem; display: inline-block;">Skatīt dzīvniekus</a>
        </div>
        <img src="kitty.jpg" alt="Dzīvnieki">
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="section-header">
        <h2>Mēs esam vairāk nekā patversme</h2>
        <p>Profesionāla aprūpe un mīlestība katram dzīvniekam</p>
    </div>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">💚</div>
            <h3>Profesionāla aprūpe</h3>
            <p>Katrs dzīvnieks saņem veterināro uzraudzību, veselīgu uzturu un ikdienas rūpes no mūsu pieredzējušās komandas</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">⭐</div>
            <h3>Individuāla pieeja</h3>
            <p>Mēs iepazīstam katra dzīvnieka raksturu un palīdzam atrast perfektu saderību ar jauno ģimeni</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🏡</div>
            <h3>Mūža atbalsts</h3>
            <p>Arī pēc adopcijas mēs esam blakus - sniedzam padomus, konsultācijas un nepieciešamo palīdzību</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">✓</div>
            <h3>Pārredzama procedūra</h3>
            <p>Vienkārša un godīga adopcijas procesa organizēšana ar pilnu atbalstu visā ceļā</p>
        </div>
    </div>
</section>

<!-- Story Section -->
<section class="story-section">
    <div class="story-container">
        <div class="story-image"></div>
        <div class="story-content">
            <h2>Mūsu stāsts</h2>
            <p>SirdsPaws tika dibināts ar vienu vienkāršu mērķi - dot katram dzīvniekam iespēju uz laimīgu dzīvi mīlošā ģimenē.</p>
            <p>Mēs ticam, ka katrs dzīvnieks ir unikāls un pelna otro iespēju. Mūsu komanda strādā katru dienu, lai nodrošinātu dzīvniekiem labāko aprūpi un atrastu viņiem ideālu māju.</p>
            <p>Kopš mūsu dibināšanas esam palīdzējuši simtiem dzīvnieku atrast savus mūža mājokļus un turpinām šo misiju ar vēl lielāku enerģiju.</p>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="stats-container">
        <div class="stat-item">
            <div class="number">200+</div>
            <div class="label">Laimīgi adoptēti dzīvnieki</div>
        </div>
        <div class="stat-item">
            <div class="number">50+</div>
            <div class="label">Gaida savu māju</div>
        </div>
        <div class="stat-item">
            <div class="number">5</div>
            <div class="label">Gadu pieredze</div>
        </div>
        <div class="stat-item">
            <div class="number">100%</div>
            <div class="label">Sirds un dvēsele</div>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="process-section">
    <div class="section-header">
        <h2>Kā adopcija notiek?</h2>
        <p>Vienkārši soļi ceļā uz jaunu ģimenes locekli</p>
    </div>
    
    <div class="steps-container">
        <div class="step-card">
            <div class="step-number">1</div>
            <h3>Iepazīsti dzīvniekus</h3>
            <p>Izpēti mūsu dzīvnieku profilus, uzzini par viņu raksturu un vajadzībām</p>
        </div>
        
        <div class="step-card">
            <div class="step-number">2</div>
            <h3>Izveido kontu</h3>
            <p>Reģistrējies mūsu vietnē un aizpildi savu profilu ar kontaktinformāciju</p>
        </div>
        
        <div class="step-card">
            <div class="step-number">3</div>
            <h3>Iesniedz pieteikumu</h3>
            <p>Pastāsti mums par sevi, saviem dzīves apstākļiem un pieredzi ar dzīvniekiem</p>
        </div>
        
        <div class="step-card">
            <div class="step-number">4</div>
            <h3>Satiecies klātienē</h3>
            <p>Pēc pieteikuma apstiprināšanas apmeklē patversmi un iepazīsties ar savu jauno draugu</p>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section class="gallery-section">
    <div class="section-header">
        <h2>Laimīgi stāsti</h2>
        <p>Daži no mūsu adoptētajiem draugiem jaunajās mājās</p>
    </div>
    
    <div class="gallery-grid">
        <div class="gallery-item"></div>
        <div class="gallery-item"></div>
        <div class="gallery-item"></div>
        <div class="gallery-item"></div>
        <div class="gallery-item"></div>
        <div class="gallery-item"></div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Sāc savu stāstu šodien</h2>
        <p>Tūkstošiem cilvēku jau ir atraduši savu labāko draugu. Tu vari būt nākamais!</p>
        <div class="cta-buttons">
            <a href="pets.php" class="btn btn-white">Skatīt dzīvniekus</a>
            <a href="register.php" class="btn btn-primary" style="background: rgba(255,255,255,0.2); border: 2px solid white;">Izveidot kontu</a>
        </div>
    </div>
</section>

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
                    <div><a href="events.php" style="color: #b8b8c8; text-decoration: none;">Pasākumi</a></div>
                    <div><a href="register.php" style="color: #b8b8c8; text-decoration: none;">Reģistrēties</a></div>
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

<script src="script.js"></script>
</body>
</html>