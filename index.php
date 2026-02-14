<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
?>
<?php include "template-layout/header.php"; ?>

<?php include "template-layout/sidebar.php"; ?>

<div id="mainContent" class="main-expanded transition-all duration-300 min-h-screen flex flex-col">

    <?php include "template-layout/navbar.php"; ?>

    <main class="flex-1 p-6 lg:p-8">

        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-2xl font-semibold text-gray-900">Support Tickets</h1>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="#" class="hover:text-gray-700">Home</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Support Tickets</span>
                </nav>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ticket-alt text-indigo-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mb-1">5,347</p>
                        <p class="text-sm text-gray-500 font-medium">Total tickets</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
                                <i class="fas fa-hourglass-half text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mb-1">1,230</p>
                        <p class="text-sm text-gray-500 font-medium">Pending tickets</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mb-1">4,117</p>
                        <p class="text-sm text-gray-500 font-medium">Solved tickets</p>
                    </div>
                </div>
            </div>
        </div>


        <div class="bg-white rounded-xl border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Support Tickets</h3>
                        <p class="text-sm text-gray-500 mt-1">Your most recent support tickets list</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                All
                            </button>
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Solved
                            </button>
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Pending
                            </button>
                        </div>

                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" id="searchInput" placeholder="Search..."
                                class="pl-10 pr-4 py-2 w-full sm:w-56 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <select id="filterStatus" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                            <option value="">All Status</option>
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Processing">Processing</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>

                        <button class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-sliders-h mr-2"></i>
                            Filter
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3.5 text-left">
                                <input type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            </th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortTable(0)">
                                <div class="flex items-center space-x-1">
                                    <span>Ticket ID</span>
                                    <i class="fas fa-sort text-gray-400 text-xs"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortTable(1)">
                                <div class="flex items-center space-x-1">
                                    <span>Requested By</span>
                                    <i class="fas fa-sort text-gray-400 text-xs"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortTable(2)">
                                <div class="flex items-center space-x-1">
                                    <span>Subject</span>
                                    <i class="fas fa-sort text-gray-400 text-xs"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortTable(3)">
                                <div class="flex items-center space-x-1">
                                    <span>Create Date</span>
                                    <i class="fas fa-sort text-gray-400 text-xs"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortTable(4)">
                                <div class="flex items-center space-x-1">
                                    <span>Status</span>
                                    <i class="fas fa-sort text-gray-400 text-xs"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">

                            </th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="bg-white divide-y divide-gray-100">
                        </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    Showing <span id="showingStart" class="font-medium text-gray-900">1</span> to
                    <span id="showingEnd" class="font-medium text-gray-900">10</span> of
                    <span id="totalRecords" class="font-medium text-gray-900">50</span>
                </div>
                <div class="flex items-center space-x-2" id="pagination">
                    </div>
            </div>
        </div>

    </main>

    <?php include "template-layout/footer.php"; ?>

</div> <?php include "template-layout/scripts.php"; ?>

<script>
    // Sample data for support tickets table
    const orders = [
        { id: '#323534', customer: 'Lindsey Curtis', email: 'demoemail@gmail.com', product: 'Issue with Dashboard Login Access', amount: '12 Feb, 2027', status: 'Solved' },
        { id: '#323535', customer: 'Kaiya George', email: 'demoemail@gmail.com', product: 'Billing Information Not Updating Properly', amount: '13 Mar, 2027', status: 'Pending' },
        { id: '#323536', customer: 'Zain Geidt', email: 'demoemail@gmail.com', product: 'Bug Found in Dark Mode Layout', amount: '19 Mar, 2027', status: 'Pending' },
        { id: '#323537', customer: 'Abram Schleifer', email: 'demoemail@gmail.com', product: 'Request to Add New Integration Feature', amount: '25 Apr, 2027', status: 'Solved' },
        { id: '#323538', customer: 'Mia Chen', email: 'mia.chen@email.com', product: 'Unable to Reset Password', amount: '28 Apr, 2027', status: 'Pending' },
        { id: '#323539', customer: 'John Doe', email: 'john.doe@email.com', product: 'Feature Request: Dark Mode', amount: '30 Apr, 2027', status: 'Solved' },
        { id: '#323540', customer: 'Jane Smith', email: 'jane.smith@email.com', product: 'Error 500 on Dashboard', amount: '01 May, 2027', status: 'Pending' },
        { id: '#323541', customer: 'Carlos Ruiz', email: 'carlos.ruiz@email.com', product: 'Cannot Download Invoice', amount: '02 May, 2027', status: 'Solved' },
        { id: '#323542', customer: 'Emily Clark', email: 'emily.clark@email.com', product: 'UI Bug in Mobile View', amount: '03 May, 2027', status: 'Pending' },
        { id: '#323543', customer: 'Liam Wong', email: 'liam.wong@email.com', product: 'Account Locked', amount: '04 May, 2027', status: 'Solved' },
        { id: '#323544', customer: 'Sarah Johnson', email: 'sarah.j@email.com', product: 'Payment Gateway Issue', amount: '05 May, 2027', status: 'Processing' },
        { id: '#323545', customer: 'Michael Brown', email: 'michael.b@email.com', product: 'Data Export Not Working', amount: '06 May, 2027', status: 'Pending' },
        { id: '#323546', customer: 'Emma Davis', email: 'emma.d@email.com', product: 'API Integration Help Needed', amount: '07 May, 2027', status: 'Solved' },
        { id: '#323547', customer: 'Oliver Wilson', email: 'oliver.w@email.com', product: 'Security Concern Report', amount: '08 May, 2027', status: 'Processing' },
        { id: '#323548', customer: 'Sophia Martinez', email: 'sophia.m@email.com', product: 'Feature Enhancement Request', amount: '09 May, 2027', status: 'Pending' }
    ];

    let currentPage = 1;
    const rowsPerPage = 10;
    let filteredOrders = [...orders];
    let sortDirection = {};

    // Table functions
    function getStatusBadge(status) {
        const badges = {
            'Solved': 'bg-green-50 text-green-700 border border-green-200',
            'Pending': 'bg-yellow-50 text-yellow-700 border border-yellow-200',
            'Processing': 'bg-blue-50 text-blue-700 border border-blue-200',
            'Cancelled': 'bg-red-50 text-red-700 border border-red-200'
        };
        return `<span class="px-3 py-1 rounded-full text-xs font-medium ${badges[status]}">${status}</span>`;
    }

    function renderTable() {
        const tableBody = document.getElementById('tableBody');
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageData = filteredOrders.slice(start, end);

        tableBody.innerHTML = pageData.map(order => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                    <input type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium text-gray-900">${order.id}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                        <div class="text-sm font-medium text-gray-900">${order.customer}</div>
                        <div class="text-sm text-gray-500">${order.email}</div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 max-w-md">${order.product}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-600">${order.amount}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">${getStatusBadge(order.status)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <button class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        updatePaginationInfo();
        renderPagination();
    }

    function updatePaginationInfo() {
        const start = (currentPage - 1) * rowsPerPage + 1;
        const end = Math.min(start + rowsPerPage - 1, filteredOrders.length);
        document.getElementById('showingStart').textContent = start;
        document.getElementById('showingEnd').textContent = end;
        document.getElementById('totalRecords').textContent = filteredOrders.length;
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredOrders.length / rowsPerPage);
        const pagination = document.getElementById('pagination');

        let html = '';

        // Previous button
        html += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}
            class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <i class="fas fa-chevron-left"></i>
        </button>`;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button onclick="changePage(${i})"
                    class="min-w-[40px] px-3 py-2 text-sm ${i === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-300'}
                    border rounded-lg transition-colors">
                    ${i}
                </button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="px-2 py-2 text-gray-500">...</span>`;
            }
        }

        // Next button
        html += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}
            class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            <i class="fas fa-chevron-right"></i>
        </button>`;

        pagination.innerHTML = html;
    }

    function changePage(page) {
        const totalPages = Math.ceil(filteredOrders.length / rowsPerPage);
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            renderTable();
        }
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        filterTable();
    });

    // Filter functionality
    document.getElementById('filterStatus').addEventListener('change', (e) => {
        filterTable();
    });

    function filterTable() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('filterStatus').value;

        filteredOrders = orders.filter(order => {
            const matchesSearch = Object.values(order).some(value =>
                value.toString().toLowerCase().includes(searchTerm)
            );
            const matchesStatus = !statusFilter || order.status === statusFilter;
            return matchesSearch && matchesStatus;
        });

        currentPage = 1;
        renderTable();
    }

    // Sort functionality
    function sortTable(columnIndex) {
        const columns = ['id', 'customer', 'product', 'amount', 'status'];
        const column = columns[columnIndex];

        if (!sortDirection[column]) {
            sortDirection[column] = 'asc';
        } else if (sortDirection[column] === 'asc') {
            sortDirection[column] = 'desc';
        } else {
            sortDirection[column] = 'asc';
        }

        filteredOrders.sort((a, b) => {
            let aVal = a[column];
            let bVal = b[column];

            if (sortDirection[column] === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        renderTable();
    }

    // Initial render
    renderTable();
</script>
