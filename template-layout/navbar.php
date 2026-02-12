<header class="bg-white border-b border-gray-200 sticky top-0 z-20">
    <div class="flex items-center justify-between px-6 h-16">
        <div class="flex items-center space-x-4">
            <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <i class="fas fa-bars text-lg"></i>
            </button>

            <div class="relative hidden md:block">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" placeholder="Search or type command..."
                    class="pl-10 pr-16 py-2 w-80 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50">
                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-400 font-medium">⌘K</span>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <button class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="far fa-sun text-lg"></i>
            </button>

            <button class="relative text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="far fa-bell text-lg"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <div class="flex items-center space-x-3 pl-3 border-l border-gray-200 cursor-pointer hover:bg-gray-50 rounded-lg px-2 py-1 transition-colors">
                <img src="https://ui-avatars.com/api/?name=Musharof+C&background=4f46e5&color=fff"
                    alt="Avatar" class="w-9 h-9 rounded-full ring-2 ring-white">
                <div class="hidden lg:block">
                    <p class="text-sm font-medium text-gray-900">Musharof C</p>
                    <p class="text-xs text-gray-500">Admin</p>
                </div>
                <i class="fas fa-chevron-down text-xs text-gray-400 hidden lg:block"></i>
            </div>
        </div>
    </div>
</header>
