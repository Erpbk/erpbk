<style>
  .entity-view-card {
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid #e9ecef;
  }

  .entity-view-card-hero {
    position: relative;
    background: linear-gradient(180deg, #1e4b8e 0%, #163a6e 100%);
    min-height: 248px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.35rem 1rem 1.6rem;
    overflow: hidden;
  }

  .entity-view-card-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.2;
    background-image:
      linear-gradient(135deg, rgba(255, 255, 255, 0.35) 25%, transparent 25%),
      linear-gradient(225deg, rgba(255, 255, 255, 0.2) 25%, transparent 25%);
    background-size: 42px 42px;
  }

  .entity-view-card-edit {
    position: absolute;
    top: 0.85rem;
    left: 0.9rem;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .entity-view-card-edit:hover {
    background: #fff;
    color: #1e4b8e;
  }

  .entity-view-card-status {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    z-index: 1;
  }

  .entity-view-card-active {
    background: #22c55e;
    color: #fff;
    font-weight: 600;
    font-size: 0.72rem;
    padding: 0.28rem 0.6rem;
    border-radius: 999px;
  }

  .entity-view-card-active.is-inactive {
    background: #64748b;
  }

  .entity-view-card-active.is-vacation {
    background: #f59e0b;
  }

  .entity-view-card-photo {
    object-fit: cover;
    display: block;
  }

  .entity-view-card-camera {
    position: absolute;
    right: 6px;
    bottom: 6px;
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 50%;
    background: #fff;
    color: #1e4b8e;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.16);
    cursor: pointer;
    padding: 0;
  }

  .entity-view-card-camera i {
    font-size: 1.2rem;
  }

  .entity-view-card-photo-wrap {
    position: relative;
    width: 188px;
    height: 188px;
    z-index: 1;
  }

  .entity-view-card-photo,
  .entity-view-card-photo-icon {
    width: 188px;
    height: 188px;
    border-radius: 50%;
    border: 5px solid #fff;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
  }

  .entity-view-card-photo-icon {
    color: #1e4b8e;
    font-size: 6.75rem;
  }

  .entity-view-card-photo-icon i {
    font-size: inherit;
    line-height: 1;
  }

  .entity-view-card .user-info h6 {
    font-size: 1.05rem;
    margin-bottom: 0.15rem;
  }

  .entity-view-card-id {
    color: #6c757d;
    font-size: 0.9rem;
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

  .entity-view-card .user_list + .user_list {
    margin-top: 0;
  }

  .entity-view-card .user_list .icons {
    flex: 0 0 1.25rem;
    width: 1.25rem;
    color: #5b6472;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .entity-view-card .user_list .icons i {
    font-size: 1.05rem;
    line-height: 1;
  }

  .entity-view-card .user_list_content {
    display: grid;
    grid-template-columns: 7.5rem minmax(0, 1fr);
    align-items: center;
    column-gap: 0.4rem;
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
      align-items: flex-start;
      padding-top: 0.12rem;
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

  .entity-info-section > .card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.9rem;
    box-shadow: 0 2px 14px rgba(16, 24, 40, 0.06);
    margin-bottom: 1rem;
    overflow: hidden;
  }

  .entity-info-section > .card > .card-header {
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

  .entity-view-card #employee-status-cards {
    margin-top: 0.75rem;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
  }

  .entity-view-card #employee-status-cards .status-card {
    min-width: 0;
    max-width: none;
    width: 100%;
    flex: unset;
    padding: 0.7rem 0.75rem;
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
