        </main>
    </div>

    <style>
        /* Cards */
        .card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-card);
            padding: 24px;
            box-shadow: var(--shadow-subtle);
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
        }

        .card h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 .material-icons-outlined {
            color: var(--primary-color);
            font-size: 24px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(139, 95, 214, 0.02) 100%);
            border: 2px solid rgba(110, 65, 193, 0.1);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(110, 65, 193, 0.15);
            border-color: rgba(110, 65, 193, 0.3);
        }

        .stat-card .material-icons-outlined {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-medium);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1;
        }

        .stat-change {
            font-size: 12px;
            color: var(--text-medium);
        }

        /* Buttons */
        .button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--border-radius-button);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .button-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .button-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);
        }

        .button-secondary {
            background-color: var(--sidebar-bg);
            color: var(--text-dark);
            border: 1px solid var(--border-light);
        }

        .button-secondary:hover {
            background-color: var(--border-light);
            transform: translateY(-1px);
        }

        .button-success {
            background-color: var(--status-green);
            color: white;
        }

        .button-success:hover {
            background-color: #28A745;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
        }

        .button-danger {
            background-color: var(--status-red);
            color: white;
        }

        .button-danger:hover {
            background-color: #E02020;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
        }

        .button .material-icons-outlined {
            font-size: 18px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border-light);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        }

        .table thead th {
            padding: 14px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            border-bottom: 1px solid var(--border-light);
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(110, 65, 193, 0.03);
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: var(--text-dark);
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background-color: rgba(52, 199, 89, 0.1);
            color: var(--status-green);
        }

        .badge-danger {
            background-color: rgba(255, 59, 48, 0.1);
            color: var(--status-red);
        }

        .badge-warning {
            background-color: rgba(255, 149, 0, 0.1);
            color: var(--status-orange);
        }

        .badge-info {
            background-color: rgba(110, 65, 193, 0.1);
            color: var(--primary-color);
        }

        /* Alert */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert .material-icons-outlined {
            font-size: 24px;
        }

        .alert-success {
            background-color: rgba(52, 199, 89, 0.1);
            border: 1px solid rgba(52, 199, 89, 0.3);
            color: var(--status-green);
        }

        .alert-danger {
            background-color: rgba(255, 59, 48, 0.1);
            border: 1px solid rgba(255, 59, 48, 0.3);
            color: var(--status-red);
        }

        .alert-warning {
            background-color: rgba(255, 149, 0, 0.1);
            border: 1px solid rgba(255, 149, 0, 0.3);
            color: var(--status-orange);
        }

        .alert-info {
            background-color: rgba(110, 65, 193, 0.1);
            border: 1px solid rgba(110, 65, 193, 0.3);
            color: var(--primary-color);
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(110, 65, 193, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state .material-icons-outlined {
            font-size: 64px;
            color: var(--text-light);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--text-medium);
            margin-bottom: 24px;
        }
    </style>

    <script>
        // Toggle Submenu
        function toggleSubmenu(event, element) {
            event.preventDefault();

            const parent = element.closest('.menu-item-parent');
            const submenu = parent.querySelector('.submenu');

            // Toggle classes
            parent.classList.toggle('open');
            submenu.classList.toggle('open');
        }

        // Auto-open active submenu on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeSubmenus = document.querySelectorAll('.menu-item-parent.open');
            activeSubmenus.forEach(function(parent) {
                const submenu = parent.querySelector('.submenu');
                if (submenu) {
                    submenu.classList.add('open');
                }
            });
        });
    </script>
</body>
</html>

