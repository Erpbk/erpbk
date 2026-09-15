<style>
  .entity-view-card {
    border-radius: 1rem;
    overflow: visible;
    border: 1px solid #e9ecef;
  }

  .entity-view-card .user-avatar-section {
    overflow: visible;
  }

  .entity-view-card-hero {
    position: relative;
    background: #fff;
    min-height: 200px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 2.85rem 1rem 0;
    overflow: visible;
    border-bottom: 1px solid #eef0f3;
  }

  .entity-view-card-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.55;
    background-image:
      linear-gradient(135deg, rgba(15, 23, 42, 0.035) 25%, transparent 25%),
      linear-gradient(225deg, rgba(15, 23, 42, 0.025) 25%, transparent 25%);
    background-size: 28px 28px;
  }

  .entity-view-card-edit {
    position: absolute;
    top: 0.85rem;
    left: 0.85rem;
    right: auto;
    width: 32px;
    height: 32px;
    border: 1px solid #e5e7eb;
    border-radius: 50%;
    background: #fff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    transition: all 0.2s ease;
  }

  .entity-view-card-edit:hover {
    background: #f8fafc;
    color: #1e4b8e;
    border-color: #cbd5e1;
  }

  .entity-view-card-status {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    left: auto;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.2rem;
    z-index: 2;
  }

  .entity-view-card-active {
    background: #22c55e;
    color: #fff;
    font-weight: 700;
    font-size: 0.72rem;
    letter-spacing: 0.01em;
    padding: 0.28rem 0.7rem;
    border-radius: 999px;
    border: 0;
    box-shadow: 0 1px 3px rgba(34, 197, 94, 0.28);
  }

  .entity-view-card-active.is-inactive {
    background: #94a3b8;
    color: #fff;
    box-shadow: none;
  }

  .entity-view-card-active.is-vacation {
    background: #f59e0b;
    color: #fff;
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.28);
  }

  .entity-view-card-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.85rem;
    letter-spacing: -.01em;
  }

  .entity-view-card-photo {
    object-fit: fill;
    display: block;
  }

  .entity-view-card-camera {
    position: absolute;
    right: 8px;
    bottom: 8px;
    width: 34px;
    height: 34px;
    border: 1px solid #e5e7eb;
    border-radius: 50%;
    background: #fff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
    cursor: pointer;
    padding: 0;
  }

  .entity-view-card-camera i {
    font-size: 1rem;
  }

  .entity-view-card-photo-wrap {
    position: relative;
    width: 280px;
    height: 280px;
    z-index: 1;
    margin-bottom: -3.1rem;
  }

  .entity-view-card-photo,
  .entity-view-card-photo-icon {
    width: 280px;
    height: 280px;
    border-radius: 1.05rem;
    border: 3px solid #fff;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.12);
  }

  .entity-view-card-photo-icon {
    color: #94a3b8;
    font-size: 7rem;
  }

  .entity-view-card-photo-icon i {
    font-size: inherit;
    line-height: 1;
  }

  .entity-view-card .user-avatar-section>.card-body {
    padding-top: 3.85rem !important;
  }

  .entity-view-card .user-info h6 {
    font-size: 1.05rem;
    margin-bottom: 0.2rem;
    color: #1f2937;
  }

  .entity-view-card-id {
    color: #94a3b8;
    font-size: 0.82rem;
    font-weight: 500;
  }

  .entity-view-card .user_list {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    border: 0;
    background: transparent;
    padding: 0.7rem 0;
    margin: 0;
  }

  .entity-view-card .user_list+.user_list {
    margin-top: 0;
  }

  .entity-view-card .user_list .icons {
    flex: 0 0 2.35rem;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 50%;
    background: #f1f5f9;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .entity-view-card .user_list .icons i {
    font-size: 1rem;
    line-height: 1;
  }

  .entity-view-card .user_list_content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: stretch;
    row-gap: 0.12rem;
    flex: 1;
    min-width: 0;
  }

  .entity-view-card .user_list_content span {
    color: #8b8d97;
    font-size: 0.8125rem;
    font-weight: 500;
    line-height: 1.3;
  }

  .entity-view-card .user_list_content b,
  .entity-view-card .user_list_content a {
    color: #1f2937;
    font-weight: 700;
    font-size: 0.875rem;
    line-height: 1.3;
    text-decoration: none;
    word-break: break-word;
  }

  .entity-view-card .user_list_content .is-phone,
  .entity-view-card .user_list_content .is-phone a {
    color: #2f6fed;
  }

  @media (max-width: 1600px) {
    .entity-view-card .user_list {
      align-items: flex-start;
      width: 100%;
    }

    .entity-view-card .user_list .icons {
      align-items: center;
      padding-top: 0;
    }

    .entity-view-card .user_list_content {
      grid-template-columns: minmax(0, 1fr);
      align-items: stretch;
      row-gap: 0.15rem;
      width: 100%;
    }

    .entity-view-card .user_list_content span {
      justify-self: start;
      text-align: left;
    }

    .entity-view-card .user_list_content b,
    .entity-view-card .user_list_content a {
      justify-self: end;
      text-align: right;
      overflow-wrap: anywhere;
    }
  }

  .entity-profile-tabs .nav-pills .nav-link {
    background: transparent !important;
    border-radius: 0;
    color: #6c757d;
    border-bottom: 3px solid transparent;
    box-shadow: none !important;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    white-space: nowrap;
  }

  .entity-profile-tabs .nav-pills .nav-link.active {
    color: #1e4b8e !important;
    background: transparent !important;
    border-bottom-color: #1e4b8e;
  }

  .entity-info-section.card {
    background: transparent;
    border: 0;
    box-shadow: none;
  }

  .entity-info-section>.card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.9rem;
    box-shadow: 0 2px 14px rgba(16, 24, 40, 0.06);
    margin-bottom: 1rem;
    overflow: hidden;
  }

  .entity-info-section>.card>.card-header {
    background: transparent;
    border-bottom: 0;
    padding: 1.1rem 1.25rem 0.25rem;
    color: #2c3345;
    font-size: 0.95rem;
  }

  .entity-info-section label,
  .entity-info-section .form-group label,
  .entity-info-section strong {
    display: block;
    font-size: 0.75rem;
    font-weight: 500 !important;
    color: #8b8d97;
    margin-bottom: 0.2rem;
  }

  .entity-info-section p,
  .entity-info-section .form-group p,
  .entity-info-section dd {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3345;
    margin-bottom: 0.85rem;
  }

  .entity-info-card-icon {
    background: #cadaef;
    color: #024baa;
    padding: 0.28rem;
    border-radius: 0.4rem;
  }

  .entity-info-field label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500 !important;
    color: #8b8d97;
    margin-bottom: 0.2rem;
  }

  .entity-info-field p {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3345;
    margin-bottom: 0.35rem;
  }

  /* Employee status cards layout is owned by employees/view.blade.php
     (grouped flex + inner 2-col grid). Do not force a grid on the root. */
  .entity-view-card #employee-status-cards {
    margin-top: 0.9rem;
  }

  .entity-profile-tabs .card {
    width: 100%;
    max-width: 100%;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .entity-profile-tabs .card-body {
    padding: 0.75rem 1rem !important;
  }

  #mainNavigation {
    display: flex;
    flex-wrap: nowrap;
    overflow: hidden;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 0.25rem;
  }

  #mainNavigation .nav-item {
    flex-shrink: 0;
    white-space: nowrap;
    display: flex !important;
  }

  #actiondropdown {
    flex-shrink: 0 !important;
    border: 1px solid var(--bs-border-color);
    background: white;
    color: var(--bs-body-color);
    align-items: center;
    justify-content: center;
  }

  #actiondropdown:hover {
    background-color: var(--bs-light);
    border-color: var(--bs-primary);
  }

  .overflow-nav-item {
    display: flex;
    align-items: center;
  }

  .overflow-nav-item.active {
    background-color: var(--bs-primary);
    color: white;
  }

  .dropdown-item.active {
    background-color: var(--bs-primary) !important;
    color: white !important;
  }

  @media (max-width: 768px) {
    .entity-profile-tabs .card-body {
      padding: 0.5rem !important;
    }

    #mainNavigation .nav-link {
      padding: 0.25rem 0.5rem !important;
      font-size: 0.8rem;
    }
  }
</style>