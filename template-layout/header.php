<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rangsit CDP — Community Data Platform</title>
    <!-- Prevent dark mode flash -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <!-- Font Awesome 6.7.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/v4-shims.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, body, button, input, select, textarea { font-family: 'Sarabun', 'Inter', sans-serif; }
        .fa, .fas, .far, .fab, .fa-solid, .fa-regular, .fa-brands { font-family: 'Font Awesome 6 Free', 'Font Awesome 6 Brands' !important; }

        .sidebar-collapsed { width: 72px; }
        .sidebar-expanded { width: 280px; }
        .main-collapsed { margin-left: 72px; }
        .main-expanded { margin-left: 280px; }

        .tooltip {
            visibility: hidden; opacity: 0; transition: opacity 0.2s; pointer-events: none;
        }
        .sidebar-collapsed .menu-item:hover .tooltip {
            visibility: visible; opacity: 1;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

        /* Table hover effect */
        tbody tr { transition: all 0.2s ease; }
        tbody tr:hover { background-color: #f9fafb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

        /* Smooth animations */
        .fade-in { animation: fadeIn 0.3s ease-in; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .sidebar-expanded, .sidebar-collapsed { width: 280px; }
            .main-collapsed, .main-expanded { margin-left: 0; }
        }

        /* ===== Dark Mode Overrides ===== */
        html.dark body { background-color: #0f172a; color: #e2e8f0; }

        /* Backgrounds */
        html.dark .bg-white { background-color: #1e293b !important; }
        html.dark .bg-gray-50 { background-color: #0f172a !important; }
        html.dark .bg-gray-100 { background-color: #1e293b !important; }

        /* Text */
        html.dark .text-gray-900 { color: #f1f5f9 !important; }
        html.dark .text-gray-800 { color: #e2e8f0 !important; }
        html.dark .text-gray-700 { color: #cbd5e1 !important; }
        html.dark .text-gray-600 { color: #94a3b8 !important; }
        html.dark .text-gray-500 { color: #64748b !important; }
        html.dark .text-gray-400 { color: #475569 !important; }

        /* Borders */
        html.dark .border-gray-200 { border-color: #334155 !important; }
        html.dark .border-gray-100 { border-color: #1e293b !important; }
        html.dark .border-gray-300 { border-color: #475569 !important; }

        /* Inputs */
        html.dark input, html.dark select, html.dark textarea {
            background-color: #0f172a !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
        }
        html.dark input::placeholder, html.dark textarea::placeholder {
            color: #64748b !important;
        }
        html.dark input:disabled, html.dark select:disabled {
            background-color: #1e293b !important;
            color: #64748b !important;
        }

        /* Hover states */
        html.dark .hover\:bg-gray-50:hover { background-color: #334155 !important; }
        html.dark .hover\:bg-gray-100:hover { background-color: #334155 !important; }
        html.dark tbody tr:hover { background-color: #334155 !important; }

        /* Table header */
        html.dark thead.bg-gray-50, html.dark .bg-gray-50 thead { background-color: #1e293b !important; }

        /* Dividers */
        html.dark .divide-gray-100 > :not([hidden]) ~ :not([hidden]) { border-color: #334155 !important; }
        html.dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]) { border-color: #334155 !important; }

        /* Cards with colored backgrounds (keep readable) */
        html.dark .bg-indigo-50 { background-color: rgba(79,70,229,0.15) !important; }
        html.dark .bg-blue-50 { background-color: rgba(59,130,246,0.15) !important; }
        html.dark .bg-green-50 { background-color: rgba(34,197,94,0.15) !important; }
        html.dark .bg-amber-50, html.dark .bg-yellow-50 { background-color: rgba(245,158,11,0.15) !important; }
        html.dark .bg-red-50 { background-color: rgba(239,68,68,0.15) !important; }
        html.dark .bg-orange-50 { background-color: rgba(249,115,22,0.15) !important; }

        /* Dropdown / Popover */
        html.dark .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.4), 0 4px 6px -4px rgba(0,0,0,0.3) !important; }

        /* Scrollbar dark */
        html.dark ::-webkit-scrollbar-track { background: #1e293b; }
        html.dark ::-webkit-scrollbar-thumb { background: #475569; }
        html.dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }

        /* Smooth transition for theme switch */
        html.dark * { transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
