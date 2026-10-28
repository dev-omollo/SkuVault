<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            background: #ffffff;
            padding: 60px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        @media (max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
                padding: 40px 20px;
                text-align: center;
            }
            .left-content {
                order: 2;
            }
            .right-content {
                order: 1;
            }
            .btn-container {
                justify-content: center;
            }
        }
        .logo-placeholder {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: -0.025em;
        }
        p {
            font-size: 1.125rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn-container {
            display: flex;
            gap: 16px;
            margin-bottom: 40px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 32px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            min-width: 140px;
        }
        .btn-login {
            background-color: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
        }
        .btn-login:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }
        .btn-register {
            background-color: #1e3a8a;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.2);
        }
        .btn-register:hover {
            background-color: #172554;
            transform: translateY(-1px);
        }
        .footer-links {
            display: flex;
            gap: 20px;
            font-size: 0.875rem;
            color: #94a3b8;
            align-items: center;
        }
        @media (max-width: 900px) {
            .footer-links {
                justify-content: center;
            }
        }
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .footer-links a:hover {
            color: #64748b;
        }
        .right-content {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .illustration {
            width: 100%;
            max-width: 500px;
            height: auto;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Left Side: Content & Actions -->
        <div class="left-content">
            <div class="logo-placeholder">
                <div class="logo-icon">IMS</div>
            </div>
            
            <h1>Inventory Management System</h1>
            
            <p>Intelligent control for your modern supply chain. From acquisition to delivery, provides a seamless, secure, and data-driven view.</p>
            
            <div class="btn-container">
                <!-- Point these routes to your actual Laravel authentication endpoints -->
                <a href="{{ route('login') }}" class="btn btn-login">Log in</a>
                <a href="{{ route('register') }}" class="btn btn-register">Register</a>
            </div>
        </div>

        <!-- Right Side: Clean Inventory Vector Illustration -->
        <div class="right-content">
            <!-- Utilizing a responsive, modern SVG warehouse layout mirroring your chosen preview -->
            <svg class="illustration" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Background grid accent -->
                <path d="M50 100 L450 100 M50 200 L450 200 M50 300 L450 300" stroke="#f1f5f9" stroke-width="2"/>
                
                <!-- Warehouse Racks & Conveyor (Isometric concept simplified) -->
                <!-- Rack 1 (Left background) -->
                <rect x="80" y="80" width="80" height="120" rx="4" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="2"/>
                <line x1="80" y1="120" x2="160" y2="120" stroke="#cbd5e1" stroke-width="2"/>
                <line x1="80" y1="160" x2="160" y2="160" stroke="#cbd5e1" stroke-width="2"/>
                <!-- Boxes on Rack 1 -->
                <rect x="90" y="90" width="25" height="20" rx="2" fill="#fcd34d"/>
                <rect x="125" y="95" width="25" height="15" rx="2" fill="#fb923c"/>
                <rect x="95" y="130" width="30" height="20" rx="2" fill="#b45309"/>
                
                <!-- Rack 2 (Right background) -->
                <rect x="340" y="80" width="80" height="120" rx="4" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="2"/>
                <line x1="340" y1="120" x2="420" y2="120" stroke="#cbd5e1" stroke-width="2"/>
                <line x1="340" y1="160" x2="420" y2="160" stroke="#cbd5e1" stroke-width="2"/>
                <!-- Boxes on Rack 2 -->
                <rect x="350" y="95" width="30" height="15" rx="2" fill="#fb923c"/>
                <rect x="355" y="130" width="25" height="20" rx="2" fill="#fcd34d"/>
                <rect x="390" y="135" width="20" height="15" rx="2" fill="#b45309"/>

                <!-- Conveyor Belt System -->
                <rect x="100" y="280" width="300" height="20" rx="10" fill="#64748b"/>
                <circle cx="120" cy="290" r="6" fill="#334155"/>
                <circle cx="200" cy="290" r="6" fill="#334155"/>
                <circle cx="280" cy="290" r="6" fill="#334155"/>
                <circle cx="360" cy="290" r="6" fill="#334155"/>
                
                <!-- Moving Packages on Conveyor -->
                <rect x="140" y="240" width="45" height="40" rx="4" fill="#d97706" stroke="#b45309" stroke-width="2"/>
                <line x1="140" y1="260" x2="185" y2="260" stroke="#b45309" stroke-width="2"/>
                
                <rect x="230" y="245" width="40" height="35" rx="4" fill="#f59e0b" stroke="#d97706" stroke-width="2"/>
                <line x1="230" y1="262" x2="270" y2="262" stroke="#d97706" stroke-width="2"/>
                
                <!-- Central Inventory Manager Character (Vector representation) -->
                <circle cx="250" cy="140" r="22" fill="#ffedd5" stroke="#1e293b" stroke-width="2"/>
                <!-- Hair/Features -->
                <path d="M232 135 C235 120, 265 120, 268 135" fill="#1e293b"/>
                <!-- Eyes & Smile -->
                <circle cx="242" cy="140" r="2" fill="#1e293b"/>
                <circle cx="258" cy="140" r="2" fill="#1e293b"/>
                <path d="M246 150 Q250 154 254 150" stroke="#1e293b" stroke-width="2" fill="none"/>
                
                <!-- Suit/Body holding dashboard clip -->
                <path d="M215 210 L285 210 L275 162 L225 162 Z" fill="#1e3a8a" stroke="#1e293b" stroke-width="2"/>
                <!-- Tie -->
                <path d="M247 162 L253 162 L255 180 L250 185 L245 180 Z" fill="#3b82f6"/>
                
                <!-- Clipboard/Scanner in front -->
                <rect x="235" y="170" width="30" height="35" rx="2" fill="#ffffff" stroke="#475569" stroke-width="2"/>
                <rect x="240" y="178" width="20" height="3" fill="#3b82f6"/>
                <rect x="240" y="186" width="20" height="2" fill="#cbd5e1"/>
                <rect x="240" y="193" width="15" height="2" fill="#cbd5e1"/>
            </svg>
        </div>
    </div>

</body>
</html>
