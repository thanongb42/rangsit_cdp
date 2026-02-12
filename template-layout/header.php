<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard Template</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * { font-family: 'Inter', sans-serif; }

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
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
