<style>
.filter-sidebar {
        position: fixed;
        top: 0;
        right: -380px;
        width: 380px;
        max-width: 100%;
        height: 100%;
        background: #fff;
        box-shadow: -2px 0 12px rgba(0, 0, 0, .1);
        z-index: 1051;
        transition: right .3s ease;
        overflow-y: auto;
        border-left: 1px solid #dee2e6;
    }

    .filter-sidebar.open {
        right: 0;
    }

    .filter-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        z-index: 1050;
        opacity: 0;
        visibility: hidden;
        transition: opacity .2s, visibility .2s;
    }

    .filter-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
    }

    .filter-body {
        padding: 1rem;
    }

    .filter-sidebar .btn-close {
        box-shadow: none;
    }

    .visa-workspace {
        display: grid;
        grid-template-columns: minmax(280px, 340px) 1fr;
        gap: 1.25rem;
        align-items: start;
    }

    @media (max-width: 991.98px) {
        .visa-workspace {
            grid-template-columns: 1fr;
        }
    }

    .visa-panel {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.85rem;
        box-shadow: 0 1px 6px rgba(15, 23, 42, .05);
        overflow: hidden;
    }

    .visa-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid #eef1f4;
        background: linear-gradient(180deg, #fbfcfe 0%, #fff 100%);
    }

    .visa-panel-header h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 650;
    }

    .visa-panel-subtitle {
        margin: .2rem 0 0;
        color: #6c757d;
        font-size: .8rem;
    }

    .visa-cat-list {
        padding: .65rem;
        max-height: calc(100vh - 260px);
        overflow-y: auto;
    }

    .visa-cat-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        width: 100%;
        padding: .8rem .85rem;
        margin-bottom: .4rem;
        border: 1px solid transparent;
        border-radius: .65rem;
        background: #f8f9fb;
        color: inherit;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .visa-cat-item:hover {
        background: #eef3fb;
        color: inherit;
    }

    .visa-cat-item.active {
        background: rgba(13, 110, 253, .08);
        border-color: rgba(13, 110, 253, .28);
        box-shadow: inset 3px 0 0 var(--bs-primary);
    }

    .visa-cat-item .visa-cat-icon {
        width: 36px;
        height: 36px;
        border-radius: .5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: var(--bs-primary);
        flex-shrink: 0;
    }

    .visa-cat-item.active .visa-cat-icon {
        background: var(--bs-primary);
        color: #fff;
    }

    .visa-cat-meta {
        min-width: 0;
        flex: 1;
    }

    .visa-cat-name {
        display: block;
        font-weight: 600;
        line-height: 1.2;
    }

    .visa-cat-count {
        font-size: .75rem;
        color: #6c757d;
    }

    .visa-empty {
        padding: 2.75rem 1.5rem;
        text-align: center;
    }

    .visa-empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f4f8;
        color: #6c757d;
        font-size: 1.75rem;
    }

    .visa-cat-item .visa-icon-btn {
        width: 30px;
        height: 30px;
        background: #fff;
        flex-shrink: 0;
    }

    .visa-view-tabs {
        display: flex;
        gap: .4rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .visa-view-tab {
        padding: .45rem .9rem;
        border-radius: .5rem;
        border: 1px solid #e6e8ef;
        background: #fff;
        color: #1f2333 !important;
        font-weight: 500;
        text-decoration: none;
        line-height: 1.3;
    }

    .visa-view-tab:hover,
    .visa-view-tab:focus {
        background: #f4f5f9;
        color: #1f2333 !important;
        text-decoration: none;
    }

    .visa-view-tab.active,
    .visa-view-tab.active:hover,
    .visa-view-tab.active:focus {
        background: var(--bs-primary, #696cff);
        border-color: var(--bs-primary, #696cff);
        color: #fff !important;
    }

    .visa-workspace.visa-workspace-all {
        grid-template-columns: 1fr;
    }
</style>
