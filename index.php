<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elvis Midega Salon - Home</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .hero-section {
            background-image: url('assets/images/salon-bg.jpg'); 
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            flex-direction: column;
            color: white;
            position: relative;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4); 
            z-index: 1;
        }

        .navbar-home, .hero-content {
            position: relative;
            z-index: 2;
        }

        .navbar-home {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
        }

        .logo-block {
            background-color: black;
            padding: 10px 15px;
            text-align: center;
            color: white;
        }

        .logo-main {
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: 2px;
        }

        .logo-sub {
            font-size: 0.7rem;
            letter-spacing: 6px;
            text-transform: lowercase;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 10px 0;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        /* --- Dropdown CSS --- */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: rgba(0, 0, 0, 0.85); 
            min-width: 320px; /* Widened slightly to accomodate longer text */
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.5);
            z-index: 10;
            border-radius: 4px;
            top: 100%;
            left: 0;
            padding: 10px 0;
        }

        .dropdown-content a {
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            font-size: 0.85rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        
        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.1); 
            opacity: 1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Utility classes for styling the dropdown text */
        .item-title { font-weight: bold; display: block; margin-bottom: 3px; }
        .item-sub { font-size: 0.75rem; color: #ccc; }
        .price-badge { float: right; color: #f0a500; font-weight: bold; }

        .login-link {
            font-weight: bold;
            border-bottom: 2px solid white;
            padding-bottom: 2px;
        }

        .hero-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .contact-info {
            font-family: 'Georgia', serif;
            font-style: italic;
            font-size: 1.1rem;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }

        .main-heading {
            font-family: 'Georgia', serif;
            font-size: 5rem;
            font-weight: normal;
            letter-spacing: 8px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin: 0;
        }
    </style>
</head>
<body>

    <div class="hero-section">
        
        <header class="navbar-home">
            <div class="logo-block">
                <span class="logo-main">ELVISMIDEGA</span><br>
                <span class="logo-sub">salon</span>
            </div>
            
            <div class="nav-right">
                <ul class="nav-links">
                    
                    <li class="dropdown">
                        <a href="#">Meet Our Team <i class="fas fa-chevron-down" style="font-size: 0.7em;"></i></a>
                        <div class="dropdown-content">
                            <a href="#"><span class="item-title">Achieng Odhiambo</span><span class="item-sub">achiengstaff@gmail.com</span></a>
                            <a href="#"><span class="item-title">Wanjiku Kamau</span><span class="item-sub">wanjikustaff@gmail.com</span></a>
                            <a href="#"><span class="item-title">Kevin Otieno</span><span class="item-sub">kevinstaff@gmail.com</span></a>
                            <a href="#"><span class="item-title">Zainab Ali</span><span class="item-sub">zainabstaff@gmail.com</span></a>
                            <a href="#"><span class="item-title">Brian Kiprop</span><span class="item-sub">brianstaff@gmail.com</span></a>
                            <a href="#"><span class="item-title">Mercy Chebet</span><span class="item-sub">mercystaff@gmail.com</span></a>
                        </div>
                    </li>

                    <li class="dropdown">
                        <a href="#">Services <i class="fas fa-chevron-down" style="font-size: 0.7em;"></i></a>
                        <div class="dropdown-content">
                            <a href="#">
                                <span class="item-title">Full Manicure <span class="price-badge">KES 1500</span></span>
                                <span class="item-sub"><i class="far fa-clock"></i> 45 mins</span>
                            </a>
                            <a href="#">
                                <span class="item-title">Gel Pedicure <span class="price-badge">KES 2000</span></span>
                                <span class="item-sub"><i class="far fa-clock"></i> 60 mins</span>
                            </a>
                            <a href="#">
                                <span class="item-title">Swedish Massage <span class="price-badge">KES 3500</span></span>
                                <span class="item-sub"><i class="far fa-clock"></i> 60 mins</span>
                            </a>
                            <a href="#">
                                <span class="item-title">Braiding (Knotless) <span class="price-badge">KES 4500</span></span>
                                <span class="item-sub"><i class="far fa-clock"></i> 180 mins</span>
                            </a>
                            <a href="#">
                                <span class="item-title">Standard Haircut <span class="price-badge">KES 500</span></span>
                                <span class="item-sub"><i class="far fa-clock"></i> 30 mins</span>
                            </a>
                            <a href="#">
                                <span class="item-title">Facial Treatment <span class="price-badge">KES 2500</span></span>
                                <span class="item-sub"><i class="far fa-clock"></i> 45 mins</span>
                            </a>
                        </div>
                    </li>

                    <li><a href="#">The Salon</a></li>
                    
                    <li><a href="login.php" class="login-link">Sign In / Book</a></li> 
                </ul>
                
                <button id="theme-toggle" class="btn" style="width: auto; padding: 5px 15px;">🌙 Dark Mode</button>
            </div>
        </header>

        <main class="hero-content">
            <p class="contact-info">Nairobi, Kenya — +254 700 000 000</p>
            <h1 class="main-heading">HAIR. CARE. VISIT.</h1>
        </main>

    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>