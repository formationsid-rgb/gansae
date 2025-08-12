// cit_sae/assets/js/admin.js
document.addEventListener('DOMContentLoaded', () => {
    const manageUsersBtn = document.getElementById('manage-users-btn');
    const userManagementSection = document.getElementById('user-management-section');
    const usersTableBody = document.getElementById('users-table-body');
    const addUserBtn = document.getElementById('add-user-btn');

    const viewAuditLogBtn = document.getElementById('view-audit-log-btn');
    const auditLogSection = document.getElementById('audit-log-section');
    const auditLogTableBody = document.getElementById('audit-log-table-body');
    const exportAuditLogCsvBtn = document.getElementById('export-audit-log-csv-btn');
    const exportAuditLogPdfBtn = document.getElementById('export-audit-log-pdf-btn');

    const configureOcrModelsBtn = document.getElementById('configure-ocr-models-btn');
    const ocrTemplateForm = document.getElementById('ocr-template-form');
    const ocrTemplateStatus = document.getElementById('ocr-template-status');

    const viewStatisticsBtn = document.getElementById('view-statistics-btn');
    const manageSecurityBtn = document.getElementById('manage-security-btn');

    const manageGroupsBtn = document.getElementById('manage-groups-btn');
    const groupManagementSection = document.getElementById('group-management-section');
    const groupsTableBody = document.getElementById('groups-table-body');
    const addGroupBtn = document.getElementById('add-group-btn');

    if (!manageUsersBtn || !userManagementSection || !usersTableBody || !addUserBtn || !viewAuditLogBtn || !auditLogSection || !auditLogTableBody || !configureOcrModelsBtn || !viewStatisticsBtn || !manageSecurityBtn || !manageGroupsBtn || !groupManagementSection || !groupsTableBody || !addGroupBtn) {
        console.warn("Admin section elements not found. Skipping admin functionality setup.");
        return;
    }

    // --- Helper to hide all admin sub-sections ---
    function hideAllAdminSubsections() {
        userManagementSection.classList.add('hidden');
        auditLogSection.classList.add('hidden');
        groupManagementSection.classList.add('hidden');
    }

    // --- User Management ---
    manageUsersBtn.addEventListener('click', () => {
        hideAllAdminSubsections();
        userManagementSection.classList.remove('hidden');
        loadUsers();
    });

    async function loadUsers() {
        usersTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Chargement des utilisateurs...</td></tr>';
        const response = await fetchData('api/get_users.php');

        if (response.success) {
            usersTableBody.innerHTML = '';
            if (response.users.length > 0) {
                response.users.forEach(user => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.username}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium ${user.role === 'admin' ? 'bg-blue-100 text-blue-800' : (user.role === 'archivist' ? 'bg-yellow-100 text-yellow-800' : (user.role === 'contributor' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))} rounded-full">
                                ${user.role}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.groups.join(', ') || 'Aucun'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-indigo-600 hover:underline mr-3 edit-user-btn" data-id="${user.id}" data-username="${user.username}" data-email="${user.email}" data-role="${user.role}" data-groups='${JSON.stringify(user.groups)}'>Modifier</button>
                            <button class="text-red-600 hover:underline delete-user-btn" data-id="${user.id}" data-username="${user.username}">Supprimer</button>
                        </td>
                    `;
                    usersTableBody.appendChild(row);
                });
            } else {
                usersTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucun utilisateur trouvé.</td></tr>';
            }
        } else {
            usersTableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">${response.message}</td></tr>`;
        }
    }

    addUserBtn.addEventListener('click', async () => {
        const addUserModal = document.getElementById('add-user-modal');
        const modalBody = addUserModal.querySelector('.modal-content');

        // Charger les groupes disponibles
        const groupsResponse = await fetchData('api/get_groups.php');
        let groupOptions = '';
        if (groupsResponse.success && groupsResponse.groups.length > 0) {
            groupsResponse.groups.forEach(group => {
                groupOptions += `<option value="${group.id}">${group.name}</option>`;
            });
        } else {
            groupOptions = '<option value="" disabled>Aucun groupe disponible</option>';
        }

        modalBody.innerHTML = `
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Ajouter un nouvel utilisateur</h2>
            <form id="add-user-form">
                <div class="mb-4">
                    <label for="new-username" class="block text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                    <input type="text" id="new-username" name="username" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label for="new-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="new-email" name="email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label for="new-password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                    <input type="password" id="new-password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label for="new-role" class="block text-sm font-medium text-gray-700">Rôle</label>
                    <select id="new-role" name="role" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        <option value="viewer">Viewer</option>
                        <option value="contributor">Contributor</option>
                        <option value="archivist">Archivist</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="new-groups" class="block text-sm font-medium text-gray-700">Groupes (Ctrl+clic pour multiple)</label>
                    <select id="new-groups" name="groups[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" multiple size="5">
                        ${groupOptions}
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Ajouter l'utilisateur</button>
            </form>
            <div id="add-user-status" class="mt-4 text-sm text-center"></div>
        `;
        openModal('add-user-modal');

        modalBody.querySelector('.close-button').addEventListener('click', () => closeModal('add-user-modal'));
        addUserModal.addEventListener('click', (e) => {
            if (e.target === addUserModal) closeModal('add-user-modal');
        });

        const addUserForm = document.getElementById('add-user-form');
        const addUserStatus = document.getElementById('add-user-status');

        addUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            addUserStatus.textContent = 'Ajout en cours...';
            addUserStatus.className = 'mt-4 text-sm text-yellow-600';

            const formData = new FormData(addUserForm);
            
            // Ajouter les groupes sélectionnés
            const selectedGroups = Array.from(document.getElementById('new-groups').options)
                                        .filter(option => option.selected)
                                        .map(option => option.value);
            selectedGroups.forEach(group_id => {
                formData.append('groups[]', group_id);
            });

            try {
                const response = await fetchData('api/add_user.php', 'POST', formData);

                if (response.success) {
                    addUserStatus.textContent = response.message;
                    addUserStatus.className = 'mt-4 text-sm text-green-600';
                    addUserForm.reset();
                    loadUsers();
                    showFlashMessage(response.message, 'success');
                    setTimeout(() => closeModal('add-user-modal'), 1500);
                } else {
                    // Afficher le message d'erreur détaillé
                    addUserStatus.textContent = response.message || 'Erreur lors de l\'ajout de l\'utilisateur';
                    addUserStatus.className = 'mt-4 text-sm text-red-600';
                    showFlashMessage(response.message || 'Erreur lors de l\'ajout de l\'utilisateur', 'error');
                }
            } catch (error) {
                addUserStatus.textContent = 'Une erreur réseau est survenue.';
                addUserStatus.className = 'mt-4 text-sm text-red-600';
                showFlashMessage('Erreur réseau lors de l\'ajout de l\'utilisateur', 'error');
            }
        });
    });

    usersTableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('edit-user-btn')) {
            const userId = e.target.dataset.id;
            const username = e.target.dataset.username;
            const email = e.target.dataset.email;
            const role = e.target.dataset.role;
            const userGroups = JSON.parse(e.target.dataset.groups);

            const editUserModal = document.getElementById('edit-user-modal');
            const modalBody = editUserModal.querySelector('.modal-content');

            // Charger les groupes disponibles
            const groupsResponse = await fetchData('api/get_groups.php');
            let groupOptions = '';
            if (groupsResponse.success && groupsResponse.groups.length > 0) {
                groupsResponse.groups.forEach(group => {
                    // Comparer par ID pour la sélection correcte
                    const selected = userGroups.some(ug => ug === group.name) ? 'selected' : '';
                    groupOptions += `<option value="${group.id}" ${selected}>${group.name}</option>`;
                });
            } else {
                groupOptions = '<option value="" disabled>Aucun groupe disponible</option>';
            }

            modalBody.innerHTML = `
                <span class="close-button">&times;</span>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Modifier l'utilisateur</h2>
                <form id="edit-user-form">
                    <input type="hidden" name="id" value="${userId}">
                    <div class="mb-4">
                        <label for="edit-username" class="block text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                        <input type="text" id="edit-username" name="username" value="${username}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div class="mb-4">
                        <label for="edit-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="edit-email" name="email" value="${email}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div class="mb-4">
                        <label for="edit-password" class="block text-sm font-medium text-gray-700">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" id="edit-password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                    </div>
                    <div class="mb-4">
                        <label for="edit-role" class="block text-sm font-medium text-gray-700">Rôle</label>
                        <select id="edit-role" name="role" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            <option value="viewer" ${role === 'viewer' ? 'selected' : ''}>Viewer</option>
                            <option value="contributor" ${role === 'contributor' ? 'selected' : ''}>Contributor</option>
                            <option value="archivist" ${role === 'archivist' ? 'selected' : ''}>Archivist</option>
                            <option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit-groups" class="block text-sm font-medium text-gray-700">Groupes (Ctrl+clic pour multiple)</label>
                        <select id="edit-groups" name="groups[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" multiple size="5">
                            ${groupOptions}
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Mettre à jour</button>
                </form>
                <div id="edit-user-status" class="mt-4 text-sm text-center"></div>
            `;
            openModal('edit-user-modal');

            modalBody.querySelector('.close-button').addEventListener('click', () => closeModal('edit-user-modal'));
            editUserModal.addEventListener('click', (e) => {
                if (e.target === editUserModal) closeModal('edit-user-modal');
            });

            const editUserForm = document.getElementById('edit-user-form');
            const editUserStatus = document.getElementById('edit-user-status');

            editUserForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                editUserStatus.textContent = 'Mise à jour en cours...';
                editUserStatus.className = 'mt-4 text-sm text-yellow-600';

                const formData = new FormData(editUserForm);
                // Collecter les groupes sélectionnés
                const selectedGroups = Array.from(document.getElementById('edit-groups').options)
                                            .filter(option => option.selected)
                                            .map(option => option.value);
                selectedGroups.forEach(group_id => {
                    formData.append('groups[]', group_id);
                });

                const response = await fetchData('api/update_user.php', 'POST', formData);

                if (response.success) {
                    editUserStatus.textContent = response.message;
                    editUserStatus.className = 'mt-4 text-sm text-green-600';
                    loadUsers();
                    showFlashMessage(response.message, 'success');
                    setTimeout(() => closeModal('edit-user-modal'), 1500);
                } else {
                    editUserStatus.textContent = response.message;
                    editUserStatus.className = 'mt-4 text-sm text-red-600';
                    showFlashMessage(response.message, 'error');
                }
            });
        } else if (e.target.classList.contains('delete-user-btn')) {
            const userId = e.target.dataset.id;
            const username = e.target.dataset.username;
            if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${username}" ? Cette action est irréversible.`)) {
                const formData = new FormData();
                formData.append('id', userId);
                const response = await fetchData('api/delete_user.php', 'POST', formData);

                if (response.success) {
                    showFlashMessage(response.message, 'success');
                    loadUsers();
                } else {
                    showFlashMessage(response.message, 'error');
                }
            }
        }
    });

    // --- Audit Log ---
    viewAuditLogBtn.addEventListener('click', () => {
        hideAllAdminSubsections();
        auditLogSection.classList.remove('hidden');
        loadAuditLog();
    });

    async function loadAuditLog() {
        auditLogTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Chargement du journal d\'audit...</td></tr>';
        const response = await fetchData('api/get_audit_log.php');

        if (response.success) {
            auditLogTableBody.innerHTML = '';
            if (response.logs.length > 0) {
                response.logs.forEach(log => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.timestamp}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${log.username}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.action}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">${log.details}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.ip_address}</td>
                    `;
                    auditLogTableBody.appendChild(row);
                });
            } else {
                auditLogTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucune entrée dans le journal d\'audit.</td></tr>';
            }
        } else {
            auditLogTableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">${response.message}</td></tr>`;
        }
    }

    // Export Audit Log
    if (exportAuditLogCsvBtn) {
        exportAuditLogCsvBtn.addEventListener('click', () => {
            window.location.href = 'api/export_audit_log.php?format=csv';
        });
    }
    if (exportAuditLogPdfBtn) {
        exportAuditLogPdfBtn.addEventListener('click', () => {
            window.location.href = 'api/export_audit_log.php?format=pdf';
        });
    }

    // --- OCR Models Configuration ---
    configureOcrModelsBtn.addEventListener('click', () => {
        openModal('ocr-models-modal');
    });

    ocrTemplateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        ocrTemplateStatus.textContent = 'Enregistrement du modèle...';
        ocrTemplateStatus.className = 'mt-4 text-sm text-yellow-600';

        const formData = new FormData(ocrTemplateForm);
        const response = await fetchData('api/save_ocr_template.php', 'POST', formData);

        if (response.success) {
            ocrTemplateStatus.textContent = response.message;
            ocrTemplateStatus.className = 'mt-4 text-sm text-green-600';
            ocrTemplateForm.reset();
            showFlashMessage(response.message, 'success');
            // Vous pourriez recharger une liste de modèles OCR ici si vous en aviez une
            setTimeout(() => closeModal('ocr-models-modal'), 1500);
        } else {
            ocrTemplateStatus.textContent = response.message;
            ocrTemplateStatus.className = 'mt-4 text-sm text-red-600';
            showFlashMessage(response.message, 'error');
        }
    });

    // --- Statistics ---
    viewStatisticsBtn.addEventListener('click', () => {
        openModal('statistics-modal');
        loadStatistics();
    });

    async function loadStatistics() {
        // Clear previous charts if they exist
        const charts = Chart.instances;
        for (let chartId in charts) {
            charts[chartId].destroy();
        }

        const response = await fetchData('api/get_statistics.php');

        if (response.success) {
            const data = response.data;

            // Documents par type (Pie Chart)
            const docsByTypeCtx = document.getElementById('docsByTypeChart').getContext('2d');
            new Chart(docsByTypeCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(data.docs_by_type),
                    datasets: [{
                        data: Object.values(data.docs_by_type),
                        backgroundColor: ['#4299E1', '#F6AD55', '#667EEA', '#48BB78', '#ED8936', '#A0AEC0', '#F6E05E'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Documents par type'
                        }
                    }
                }
            });

            // Volume de téléversement par mois (Bar Chart)
            const uploadVolumeCtx = document.getElementById('uploadVolumeChart').getContext('2d');
            new Chart(uploadVolumeCtx, {
                type: 'bar',
                data: {
                    labels: data.upload_volume_by_month.map(item => item.month),
                    datasets: [{
                        label: 'Documents téléversés',
                        data: data.upload_volume_by_month.map(item => item.count),
                        backgroundColor: '#48BB78',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Volume de téléversement par mois'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Taux d'indexation OCR (Doughnut Chart)
            const ocrIndexingRateCtx = document.getElementById('ocrIndexingRateChart').getContext('2d');
            new Chart(ocrIndexingRateCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Archivés', 'En attente'],
                    datasets: [{
                        data: [data.ocr_indexing_rate.archived, data.ocr_indexing_rate.pending],
                        backgroundColor: ['#38A169', '#ECC94B'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: `Taux d'indexation OCR (Total: ${data.ocr_indexing_rate.total})`
                        }
                    }
                }
            });

            // Activité des utilisateurs (List)
            const userActivityList = document.getElementById('userActivityList');
            if (userActivityList) {
                userActivityList.innerHTML = '';
                if (data.user_activity.length > 0) {
                    data.user_activity.forEach(user => {
                        const p = document.createElement('p');
                        p.textContent = `${user.username}: ${user.action_count} actions`;
                        userActivityList.appendChild(p);
                    });
                } else {
                    userActivityList.innerHTML = '<p class="text-gray-500">Aucune activité récente.</p>';
                }
            }

        } else {
            console.error("Erreur lors du chargement des statistiques:", response.message);
            const statsModalContent = document.querySelector('#statistics-modal .modal-content');
            if (statsModalContent) {
                statsModalContent.innerHTML = `<span class="close-button">&times;</span><p class="text-red-500 text-center mt-4">${response.message}</p>`;
                statsModalContent.querySelector('.close-button').addEventListener('click', () => closeModal('statistics-modal'));
            }
        }
    }

    // --- Security ---
    manageSecurityBtn.addEventListener('click', () => {
        openModal('security-modal');
    });

    // --- Group Management ---
    manageGroupsBtn.addEventListener('click', () => {
        hideAllAdminSubsections();
        groupManagementSection.classList.remove('hidden');
        loadGroups();
    });

    async function loadGroups() {
        groupsTableBody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Chargement des groupes...</td></tr>';
        const response = await fetchData('api/get_groups.php');

        if (response.success) {
            groupsTableBody.innerHTML = '';
            if (response.groups.length > 0) {
                response.groups.forEach(group => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${group.name}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">${group.description}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-indigo-600 hover:underline mr-3 edit-group-btn" data-id="${group.id}" data-name="${group.name}" data-description="${group.description}">Modifier</button>
                            <button class="text-red-600 hover:underline delete-group-btn" data-id="${group.id}" data-name="${group.name}">Supprimer</button>
                        </td>
                    `;
                    groupsTableBody.appendChild(row);
                });
            } else {
                groupsTableBody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Aucun groupe trouvé.</td></tr>';
            }
        } else {
            groupsTableBody.innerHTML = `<tr><td colspan="3" class="px-6 py-4 text-center text-red-500">${response.message}</td></tr>`;
        }
    }

    addGroupBtn.addEventListener('click', () => {
        const addGroupModal = document.getElementById('add-group-modal');
        const modalBody = addGroupModal.querySelector('.modal-content');
        modalBody.innerHTML = `
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Ajouter un nouveau groupe</h2>
            <form id="add-group-form">
                <div class="mb-4">
                    <label for="new-group-name" class="block text-sm font-medium text-gray-700">Nom du groupe</label>
                    <input type="text" id="new-group-name" name="name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label for="new-group-description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="new-group-description" name="description" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"></textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Ajouter le groupe</button>
            </form>
            <div id="add-group-status" class="mt-4 text-sm text-center"></div>
        `;
        openModal('add-group-modal');

        modalBody.querySelector('.close-button').addEventListener('click', () => closeModal('add-group-modal'));
        addGroupModal.addEventListener('click', (e) => {
            if (e.target === addGroupModal) closeModal('add-group-modal');
        });

        const addGroupForm = document.getElementById('add-group-form');
        const addGroupStatus = document.getElementById('add-group-status');

        addGroupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            addGroupStatus.textContent = 'Ajout en cours...';
            addGroupStatus.className = 'mt-4 text-sm text-yellow-600';

            const formData = new FormData(addGroupForm);
            const response = await fetchData('api/add_group.php', 'POST', formData);

            if (response.success) {
                addGroupStatus.textContent = response.message;
                addGroupStatus.className = 'mt-4 text-sm text-green-600';
                addGroupForm.reset();
                loadGroups();
                showFlashMessage(response.message, 'success');
                setTimeout(() => closeModal('add-group-modal'), 1500);
            } else {
                addGroupStatus.textContent = response.message;
                addGroupStatus.className = 'mt-4 text-sm text-red-600';
                showFlashMessage(response.message, 'error');
            }
        });
    });

    groupsTableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('edit-group-btn')) {
            const groupId = e.target.dataset.id;
            const groupName = e.target.dataset.name;
            const groupDescription = e.target.dataset.description;

            const editGroupModal = document.getElementById('edit-group-modal');
            const modalBody = editGroupModal.querySelector('.modal-content');
            modalBody.innerHTML = `
                <span class="close-button">&times;</span>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Modifier le groupe</h2>
                <form id="edit-group-form">
                    <input type="hidden" name="id" value="${groupId}">
                    <div class="mb-4">
                        <label for="edit-group-name" class="block text-sm font-medium text-gray-700">Nom du groupe</label>
                        <input type="text" id="edit-group-name" name="name" value="${groupName}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div class="mb-4">
                        <label for="edit-group-description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="edit-group-description" name="description" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">${groupDescription}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Mettre à jour</button>
                </form>
                <div id="edit-group-status" class="mt-4 text-sm text-center"></div>
            `;
            openModal('edit-group-modal');

            modalBody.querySelector('.close-button').addEventListener('click', () => closeModal('edit-group-modal'));
            editGroupModal.addEventListener('click', (e) => {
                if (e.target === editGroupModal) closeModal('edit-group-modal');
            });

            const editGroupForm = document.getElementById('edit-group-form');
            const editGroupStatus = document.getElementById('edit-group-status');

            editGroupForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                editGroupStatus.textContent = 'Mise à jour en cours...';
                editGroupStatus.className = 'mt-4 text-sm text-yellow-600';

                const formData = new FormData(editGroupForm);
                const response = await fetchData('api/update_group.php', 'POST', formData);

                if (response.success) {
                    editGroupStatus.textContent = response.message;
                    editGroupStatus.className = 'mt-4 text-sm text-green-600';
                    loadGroups();
                    showFlashMessage(response.message, 'success');
                    setTimeout(() => closeModal('edit-group-modal'), 1500);
                } else {
                    editGroupStatus.textContent = response.message;
                    editGroupStatus.className = 'mt-4 text-sm text-red-600';
                    showFlashMessage(response.message, 'error');
                }
            });
        } else if (e.target.classList.contains('delete-group-btn')) {
            const groupId = e.target.dataset.id;
            const groupName = e.target.dataset.name;
            if (confirm(`Êtes-vous sûr de vouloir supprimer le groupe "${groupName}" ? Cette action est irréversible et les documents associés n'auront plus de groupe.`)) {
                const formData = new FormData();
                formData.append('id', groupId);
                const response = await fetchData('api/delete_group.php', 'POST', formData);

                if (response.success) {
                    showFlashMessage(response.message, 'success');
                    loadGroups();
                    loadUsers(); // Recharger les utilisateurs car leurs groupes peuvent avoir changé
                } else {
                    showFlashMessage(response.message, 'error');
                }
            }
        }
    });
});
