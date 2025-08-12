// assets/js/dashboard.js
async function loadDashboardData() {
    const totalDocumentsElem = document.getElementById('total-documents');
    const pendingIndexingElem = document.getElementById('pending-indexing');
    const activeUsersElem = document.getElementById('active-users');
    const recentActivityList = document.getElementById('recent-activity-list');

    if (!totalDocumentsElem || !pendingIndexingElem || !activeUsersElem || !recentActivityList) {
        console.warn("Dashboard elements not found. Skipping dashboard data load.");
        return;
    }

    totalDocumentsElem.textContent = 'Chargement...';
    pendingIndexingElem.textContent = 'Chargement...';
    activeUsersElem.textContent = 'Chargement...';
    recentActivityList.innerHTML = '<p class="text-gray-500 text-center">Chargement des activités...</p>';

    const response = await fetchData('api/get_dashboard_data.php');

    if (response.success) {
        const data = response.data;
        totalDocumentsElem.textContent = data.total_documents;
        pendingIndexingElem.textContent = data.pending_indexing;
        activeUsersElem.textContent = data.active_users;

        recentActivityList.innerHTML = ''; // Clear previous content
        if (data.recent_activity.length > 0) {
            data.recent_activity.forEach(activity => {
                const activityItem = document.createElement('div');
                activityItem.className = 'flex justify-between items-center pb-4 border-b border-gray-100';
                activityItem.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <img src="assets/img/avatar_default.png" alt="Avatar utilisateur" class="w-8 h-8 rounded-full">
                        <div>
                            <p class="font-medium">${activity.username || 'N/A'}</p>
                            <p class="text-sm text-gray-500">${activity.action}: ${activity.details.substring(0, 50)}...</p>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500">${new Date(activity.timestamp).toLocaleString('fr-FR')}</span>
                `;
                recentActivityList.appendChild(activityItem);
            });
        } else {
            recentActivityList.innerHTML = '<p class="text-gray-500 text-center">Aucune activité récente.</p>';
        }
    } else {
        totalDocumentsElem.textContent = 'Erreur';
        pendingIndexingElem.textContent = 'Erreur';
        activeUsersElem.textContent = 'Erreur';
        recentActivityList.innerHTML = `<p class="text-red-500 text-center">${response.message}</p>`;
    }
}
