import DataTable from 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('project-filter-form');
    const resetButton = document.getElementById('reset-project-filters');
    const tableElement = document.getElementById('projects-table');
    const tableCard = document.querySelector('[data-project-table-card]');

    if (!filterForm || !resetButton || !tableElement) {
        return;
    }

    const labels = JSON.parse(tableElement.dataset.labels);
    const table = new DataTable(tableElement, {
        processing: true,
        serverSide: true,
        searching: false,
        pageLength: 10,
        order: [[5, 'desc']],
        ajax: {
            url: tableElement.dataset.url,
            data: data => {
                data.filter_search = document.getElementById('filter_search').value;
                data.filter_status = document.getElementById('filter_status').value;
            },
            error: () => tableCard?.classList.remove('is-loading'),
        },
        columns: [
            { data: 'name', name: 'name', searchable: false },
            { data: 'owner_name', name: 'owner_name', searchable: false },
            { data: 'status', name: 'status', searchable: false },
            { data: 'currency_code', name: 'currency_code', searchable: false },
            { data: 'project_duration_years', name: 'project_duration_years', searchable: false },
            { data: 'updated_at', name: 'updated_at', searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        language: labels,
        drawCallback: () => {
            tableCard?.classList.remove('is-loading');
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
                window.bootstrap?.Tooltip.getOrCreateInstance(element);
            });
        },
    });

    table.on('preXhr', () => tableCard?.classList.add('is-loading'));

    filterForm.addEventListener('submit', event => {
        event.preventDefault();
        table.draw();
    });

    resetButton.addEventListener('click', () => {
        filterForm.reset();
        table.draw();
    });
});
