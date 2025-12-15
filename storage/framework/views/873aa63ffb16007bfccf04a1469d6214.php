<?php $__env->startSection('title','Bikes'); ?>

<?php $__env->startPush('third_party_stylesheets'); ?>
<link href="https://fonts.googleapis.com/css2?family=Rockwell:wght@400;700&display=swap" rel="stylesheet">
<style>
    .bike-info-sidebar {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
    }
    
    .bike-header-compact {
        text-align: center;
        margin-bottom: 20px; 
        width: 100%;
        overflow: hidden;
    }
    
    .vehicle-type {
        font-size: 0.8rem; 
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
        line-height: 1.2;
        word-break: break-word;
    }
    
    /* Number Plate Container */
    .plate-container {
        width: 100%;
        display: flex;
        justify-content: center;
        margin: 0 auto;
        max-width: 100%;
        overflow: hidden;
    }
    
    /* Number Plate Styles -style for all emirates */
    .number-plate {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 6px;
        font-family: 'Rockwell', 'Rockwell Condensed', 'Bodoni MT', serif;
        font-weight: bold;
        text-align: center;
        min-width: 160px;
        max-width: 90%;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        border: 1px solid #000000;
        background: linear-gradient(to bottom, #ffffff, #f0f0f0);
        color: #000000; 
        box-sizing: border-box;
        margin: 0 auto;
        position: relative;
    }
    
    /* Arabic emirate name - top left corner */
    .plate-arabic-corner {
        position: absolute;
        top: 5px;
        left: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1;
        color: #000000;
        font-family: 'Rockwell', 'Rockwell Condensed', 'Bodoni MT', serif;
        text-align: left;
        max-width: 40%;
        word-break: break-word;
    }
    
    /* Bike code - top right corner */
    .plate-bike-code-corner {
        position: absolute;
        top: 5px;
        right: 20px;
        font-size: 0.9rem;
        color: #000000;
        font-weight: 600;
        line-height: 1;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: right;
        max-width: 40%;
        word-break: break-word;
    }
    
    .number-plate .plate-number {
        font-size: 1.4rem;
        letter-spacing: 1px;
        margin-top: 12px; /* Added margin for corner elements */
        margin-bottom: 4px;
        color: #000000; 
        font-weight: 800;
        line-height: 1.2;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    .number-plate .plate-emirate-english {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #000000; 
        font-weight: 600;
        line-height: 1;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    /* Default Plate Design */
    .plate-default {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-color: #000000;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-block;
        max-width: 90%;
        box-sizing: border-box;
        margin: 0 auto;
        word-break: break-word;
    }
    
    /* Basic Information Section - Matching rider profile style */
    .basic-information {
        margin-top: 20px;
        width: 100%;
        overflow: hidden;
    }
    
    .basic-information h3 {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dce1e6;
    }
    
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
        width: 100%;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 6px;
        padding-bottom: 10px;
        width: 100%;
        box-sizing: border-box;
    }
    
    
    .info-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #6c757d;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    
    .info-content {
        flex: 1;
        min-width: 0; /* Prevents content overflow */
    }
    
    .info-label {
        font-size: 0.875rem;
        color: #6c757d;
        display: block;
        margin-bottom: 2px;
        word-break: break-word;
    }
    
    .info-value {
        font-size: 0.8rem;
        font-weight: 600;
        color: #495057;
        display: block;
        word-break: break-word;
        overflow-wrap: break-word;
        line-height: 1.3;
    }
    
    .info-value.status-badge {
        display: inline-block;
        padding: 8px 14px; 
        border-radius: 8px; 
        font-size: 0.7rem; 
        font-weight: 600;
        text-align: center;
        margin-top: 4px;
        min-width: 100px; 
        max-width: 100%;
    }
    
    .status-badge.Active {
        color: #059669;
        background: #f0fdf4;
        border: 1px solid #44bd97;
    }
    
    .status-badge.Return {
        color: #ffc107;
        background: #faf4de;
        border: 1px solid #dbba56;
    }
    
    .status-badge.Absconded {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #d67676;
    }
    
    .status-badge.Vacation {
        color: #0dcaf0;
        background: #defafa;
        border: 1px solid #84cbd9;
    }
    
    /* Default to gray if warehouse doesn't match specific names */
    .status-badge.warehouse-default {
        color: #41464b;
        background: #e2e3e5;
        border: 1px solid #c4c8cb;
    }
    
    /* Action Buttons */
    .bike-actions-compact {
        display: flex;
        flex-wrap: wrap;
        gap: 8px; 
        justify-content: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 2px solid #dce1e6;
        width: 100%;
        box-sizing: border-box;
    }
    
    .btn-compact {
        flex: 1;
        min-width: 120px; 
        padding: 8px 12px; 
        border-radius: 6px; 
        font-weight: 600;
        font-size: 0.6rem; 
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px; 
        transition: all 0.2s ease;
        text-decoration: none;
        border: 1px solid transparent; 
        cursor: pointer;
        box-sizing: border-box;
        max-width: 100%;
        word-break: break-word;
    }
    
    .btn-compact i {
        font-size: 0.9rem; 
    }
    
    .btn-edit-compact {
        background: linear-gradient(135deg, #024baa 0%, #4f46e5 100%);
        color: white;
        border-color: #024baa;
    }
    
    .btn-edit-compact:hover {
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .btn-assign-compact {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-color: #10b981;
    }
    
    .btn-assign-compact:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .btn-view-assignment {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border-color: #f59e0b;
    }
    
    .btn-view-assignment:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .road-status-container {
    text-align: center;
    margin: 15px 0 20px 0;
    width: 100%;
    }

    .road-status-badge {
        display: inline-block;
        padding: 4px 16px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        text-align: center;
        min-width: 120px;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .road-onroad {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: 1px solid #218838;
    }

    .road-offroad {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        border: 1px solid #c82333;
    }
    
    .road-onroadRed {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); /* Both red tones */
        border: 2px solid #b02a37; /* Darker red border */
        color: #ffffff;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .bike-info-sidebar {
            padding: 20px 15px;
            max-width: 100%;
            border-radius: 10px;
        }
        
        .bike-header-compact {
            padding-left: 5px;
            padding-right: 5px;
        }
        
        .bike-actions-compact {
            flex-direction: column;
            gap: 6px; /* Smaller gap on mobile */
        }
        
        .btn-compact {
            min-width: 100%;
            padding: 7px 10px; /* Further reduced for mobile */
            font-size: 0.8rem;
        }
        
        .vehicle-type {
            font-size: 1.1rem; /* Adjusted for mobile */
        }
        
        .number-plate {
            min-width: 140px;
            padding: 6px 10px;
            max-width: 85%;
        }
        
        .plate-arabic-corner,
        .plate-bike-code-corner {
            font-size: 0.6rem;
            top: 3px;
        }
        
        .plate-arabic-corner {
            left: 6px;
        }
        
        .plate-bike-code-corner {
            right: 6px;
        }
        
        .number-plate .plate-number {
            font-size: 1.2rem;
            margin-top: 10px;
        }
        
        .number-plate .plate-emirate-english {
            font-size: 0.7rem;
        }
        
        .plate-default {
            max-width: 85%;
            padding: 6px 12px;
        }
        
        /* Mobile responsive for info items */
        .info-item {
            flex-wrap: wrap;
        }
        
        .info-icon {
            width: 18px;
            height: 18px;
            font-size: 0.8rem;
        }
        
        .info-label {
            font-size: 0.8rem;
        }
        
        .info-value {
            font-size: 0.9rem;
        }
        
        .info-value.status-badge {
            font-size: 0.9rem;
            padding: 6px 12px;
            min-width: 90px;
        }
    }
    
    @media (min-width: 769px) and (max-width: 992px) {
        .bike-info-sidebar {
            max-width: 440px;
        }
        
        .number-plate {
            min-width: 170px;
            max-width: 85%;
        }
        
        .btn-compact {
            min-width: 130px; /* Adjusted for medium screens */
        }
    }
    
    @media (min-width: 993px) {
        .bike-info-sidebar {
            max-width: 420px;
        }
        
        .number-plate {
            min-width: 180px;
            max-width: 85%;
        }
        
        .btn-compact {
            min-width: 120px; /* Adjusted for large screens */
        }
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4);
        }
        70% {
            transform: scale(1.02);
            box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
        }
    }
    
    /* Extra small devices */
    @media (max-width: 480px) {
        .bike-info-sidebar {
            padding: 15px 10px;
        }
        
        .vehicle-type {
            font-size: 1.1rem; /* Adjusted for mobile */
        }
        
        .number-plate {
            min-width: 130px;
            padding: 5px 8px;
            max-width: 80%;
        }
        
        .plate-arabic-corner,
        .plate-bike-code-corner {
            font-size: 0.55rem;
            top: 2px;
        }
        
        .plate-arabic-corner {
            left: 5px;
        }
        
        .plate-bike-code-corner {
            right: 5px;
        }
        
        .number-plate .plate-number {
            font-size: 1.1rem;
            margin-top: 8px;
        }
        
        .btn-compact {
            padding: 6px 8px;
            font-size: 0.75rem;
        }
        
        .btn-compact i {
            font-size: 0.8rem;
        }
        
        .info-value.status-badge {
            font-size: 0.85rem;
            padding: 5px 10px;
            min-width: 80px;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
  <div class="col-xl-3 col-md-3 col-lg-4 order-1 order-md-0">
    <!-- Single container - no nested card -->
    <div class="bike-info-sidebar">
        <!-- Header with Model Type and Emirates -->
        <div class="bike-header-compact">
            <div class="vehicle-type">
                <?php echo e($bikes->model_type ?? 'Vehicle'); ?>

            </div>
            <!-- Number Plate Display -->
            <?php
                $emirateCode = strtolower(trim($bikes->emirates ?? ''));
                $plateNumber = $bikes->plate ?? 'N/A';
                $bikeCode = $bikes->bike_code ?? 'N/A';
                
                // Define emirate names in Arabic and English
                $emiratesData = [
                    'dxb' => ['arabic' => 'دبي', 'english' => 'DUBAI'],
                    'auh' => ['arabic' => 'أبوظبي', 'english' => 'ABU DHABI'],
                    'shj' => ['arabic' => 'الشارقة', 'english' => 'SHARJAH'],
                    'rak' => ['arabic' => 'رأس الخــيمة', 'english' => 'RAS AL KHAIMAH'],
                    'fuj' => ['arabic' => 'الفجيرة', 'english' => 'FUJAIRAH'],
                    'ajm' => ['arabic' => 'عجمان', 'english' => 'AJMAN'],
                    'uaq' => ['arabic' => 'أم القيوين', 'english' => 'UMM AL QUWAIN'],
                ];
                
                $currentEmirate = $emiratesData[$emirateCode] ?? null;
                
                // Map warehouse names to status classes
                $warehouse = $bikes->warehouse ?? '';
                $warehouseClass = 'warehouse-default';
                
                if (strtolower($warehouse) == 'active') {
                    $warehouseClass = 'Active';
                } elseif (strtolower($warehouse) == 'return') {
                    $warehouseClass = 'Return';
                } elseif (strtolower($warehouse) == 'absconded') {
                    $warehouseClass = 'Absconded';
                } elseif (strtolower($warehouse) == 'vacation') {
                    $warehouseClass = 'Vacation';
                }
            ?>
            
            <div class="plate-container">
                <?php if($currentEmirate): ?>
                    <!-- Unified RAK-style plate for all emirates -->
                    <div class="number-plate ">
                        <!-- Arabic emirate name - top left corner -->
                        <div class="plate-arabic-corner"><?php echo e($currentEmirate['arabic']); ?></div>
                        
                        <!-- Bike code - top right corner -->
                        <?php if($bikeCode != 'N/A'): ?>
                            <div class="plate-bike-code-corner"><?php echo e($bikeCode); ?></div>
                        <?php endif; ?>
                        
                        <!-- Plate number - center -->
                        <div class="plate-number"><?php echo e($plateNumber); ?></div>
                        
                        <!-- English emirate name - bottom center -->
                        <div class="plate-emirate-english"><?php echo e($currentEmirate['english']); ?></div>
                    </div>
                <?php else: ?>
                    <!-- Default Badge for other emirates -->
                    <div class="number-plate ">
                        
                        <!-- Bike code - top right corner -->
                        <?php if($bikeCode != 'N/A'): ?>
                            <div class="plate-bike-code-corner"><?php echo e($bikeCode); ?></div>
                        <?php endif; ?>
                        
                        <!-- Plate number - center -->
                        <div class="plate-number"><?php echo e($plateNumber); ?></div>
                    
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="road-status-container">
            <?php
                $warehouse = strtolower(trim($bikes->warehouse ?? ''));
                $roadStatus = 'N/A';
                $roadStatusClass = '';
                
                if ($warehouse === 'active') {
                    $roadStatus = 'On Road';
                    $roadStatusClass = 'road-onroad';
                } elseif ($warehouse === 'return' || $warehouse === 'vacation' || $warehouse === 'express garage') {
                    $roadStatus = 'Off Road';
                    $roadStatusClass = 'road-offroad';
                }else{
                    $roadStatus = 'On Road';
                    $roadStatusClass = 'road-onroadRed';
                }
            ?>
            
            <?php if($roadStatus !== 'N/A'): ?>
            <div class="road-status-badge <?php echo e($roadStatusClass); ?>">
                <?php echo e($roadStatus); ?>

            </div>
            <?php endif; ?>
        </div>
        
        <!-- Basic Information Section - Matching rider profile style -->
        <div class="basic-information">
            <h3></h3>
            <ul class="info-list">

                <li class="info-item">
                    <div class="info-icon">
                        <i class="ti ti-user"></i>
                    </div>
                    <div class="info-content">
                        <?php
                            $rider = DB::table('riders')->where('id', $bikes->rider_id)->first();
                            $riderName = $rider->name ?? 'Not Assigned';
                        ?>
                        <span class="info-label">Rider</span>
                        <?php if($rider): ?>
                            <a href="<?php echo e(route('riders.show', $rider->id)); ?>"><?php echo e($riderName); ?></a>
                        <?php else: ?>
                            <span><?php echo e($riderName); ?></span>
                        <?php endif; ?>
                    </div>
                </li>

                <!-- Leasing Company -->
                <li class="info-item">
                    <div class="info-icon">
                        <i class="ti ti-building"></i>
                    </div>
                    <div class="info-content">
                        <span class="info-label">Leasing Company</span>
                        <span class="info-value">
                            <?php
                                $company = DB::table('leasing_companies')->where('id', $bikes->company)->first();
                                echo $company->name ?? 'N/A';
                            ?>
                        </span>
                    </div>
                </li>

                <!-- Bike Expiry -->
                <li class="info-item">
                    <div class="info-icon">
                        <i class="ti ti-calendar-stats"></i>
                    </div>
                    <div class="info-content">
                        <span class="info-label">Bike Expiry</span>
                        <?php
                            $expiryDate = $bikes->expiry_date ?? null;
                            $isExpiring = false;
                            $isExpired = false;
                            
                            if ($expiryDate) {
                                $expiry = \Carbon\Carbon::parse($expiryDate);
                                $now = \Carbon\Carbon::now();
                                
                                if ($expiry->isPast()) {
                                    $isExpired = true;
                                } elseif ($expiry->diffInDays($now) <= 30) {
                                    $isExpired = true;
                                }
                            }
                        ?>
                        
                        <?php if($expiryDate): ?>
                            <?php if($isExpired): ?>
                                <span class="info-value status-badge Expired" style="animation: pulse 1s infinite; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; border: 2px solid #b91c1c;">
                                    <?php echo e($expiryDate); ?>

                                </span>
                            <?php elseif($isExpiring): ?>
                                <span class="info-value status-badge Expiring" style="animation: pulse 1.5s infinite; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; border: 2px solid #d97706;">
                                    <?php echo e($expiryDate); ?> (SOON!)
                                </span>
                            <?php else: ?>
                                <span class="info-value"><?php echo e($expiryDate); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="info-value">N/A</span>
                        <?php endif; ?>
                    </div>
                </li>

            </ul>
        </div>
        
        <!-- Action Buttons - Smaller size -->
        <div class="bike-actions-compact">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('item_edit')): ?>
            <a href="<?php echo e(route('bikes.edit', $bikes->id)); ?>" 
               class="btn-compact btn-edit-compact show-modal"
               data-title="Edit Vehicle #<?php echo e($bikes->plate); ?>">
                <i class="fas fa-edit"></i>
                <span>Edit Details</span>
            </a>
            <?php endif; ?>

            <?php if($bikes->rider_id): ?>
            <a href="javascript:void(0);" 
               class="btn-compact btn-view-assignment show-modal"
               data-size="xl"
               data-title="Assigned Rider Details"
               data-action="<?php echo e(route('bikes.assignrider', $bikes->id)); ?>">
                <i class="fas fa-user-check"></i>
                <span>Change Status</span>
            </a>
            <?php else: ?>
            <a href="javascript:void(0);" 
               class="btn-compact btn-assign-compact show-modal"
               data-size="xl"
               data-title="Assign Rider to Vehicle #<?php echo e($bikes->plate); ?>"
               data-action="<?php echo e(route('bikes.assign_rider', $bikes->id)); ?>">
                <i class="fas fa-user-plus"></i>
                <span>Assign Rider</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
  </div>
  
  <div class="col-xl-9 col-md-9 col-lg-8 order-0 order-md-1">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-3 row-gap-2">
        <li class="nav-item"><a class="nav-link <?php if(request()->segment(1) =='bikes'): ?> active <?php endif; ?> " href="<?php echo e(route('bikes.show',$bikes->id)); ?>"><i class="ti ti-motorbike ti-sm me-1_5 mx-2"></i> Bike</a></li>
        <li class="nav-item">
          <a href="<?php echo e(route('bikeHistories.index', ['bike_id'=>$bikes->id])); ?>" class="nav-link <?php if(request()->segment(1) =='bikeHistories'): ?> active <?php endif; ?>"><i class="fa fa-list-check"></i>&nbsp;History</a>
        </li>
        <li class="nav-item">
          <a href="<?php echo e(route('files.index',['type_id'=>$bikes->id,'type'=>'bike'])); ?>" class="nav-link <?php if(request()->segment(1) =='files'): ?> active <?php endif; ?>"><i class="fa fa-file-lines"></i>&nbsp;Files</a>
        </li>
      </ul>
    </div>
    <div class="card mb-5" id="cardBody" style="height:660px !important;overflow: auto;">
      <?php echo $__env->yieldContent('page_content'); ?>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/bikes/view.blade.php ENDPATH**/ ?>