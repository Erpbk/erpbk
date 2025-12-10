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
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
        width: 100%;
        overflow: hidden;
    }
    
    .vehicle-type {
        font-size: 1.5rem;
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
    
    /* Number Plate Styles - RAK style for all emirates */
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
        border: 2px solid #000000;
        background: linear-gradient(to bottom, #ffffff, #f0f0f0);
        color: #000000; /* Changed to black for all text */
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
        color: #000000; /* Changed to black */
        font-weight: 800;
        line-height: 1.2;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    
    .number-plate .plate-emirate-english {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #000000; /* Changed to black */
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
    
    .bike-details-list {
        margin-bottom: 28px;
        width: 100%;
        overflow: hidden;
    }
    
    .detail-row {
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f1f5f9;
        width: 100%;
        box-sizing: border-box;
    }
    
    .detail-row:last-child {
        margin-bottom: 0;
        border-bottom: none;
    }
    
    .detail-label {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        word-break: break-word;
    }
    
    .detail-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        text-align: right;
        word-break: break-word;
        overflow-wrap: break-word;
        line-height: 1.3;
        padding-left: 20px;
        box-sizing: border-box;
    }
    
    .detail-value.status-Active {
        color: #059669;
        background: #f0fdf4;
        padding: 8px 14px;
        border-radius: 8px;
        display: inline-block;
        text-align: center;
        margin-left: auto;
        min-width: 100px;
        max-width: 100%;
    }
    
    .detail-value.status-Inactive {
        color: #dc2626;
        background: #fef2f2;
        padding: 8px 14px;
        border-radius: 8px;
        display: inline-block;
        text-align: center;
        margin-left: auto;
        min-width: 100px;
        max-width: 100%;
    }
    
    .detail-value.status-Return {
        color: #ffc107;
        background: #faf4de;
        padding: 8px 14px;
        border-radius: 8px;
        display: inline-block;
        text-align: center;
        margin-left: auto;
        min-width: 100px;
        max-width: 100%;
    }

    .detail-value.status-Absconded {
        color: #dc2626;
        background: #fef2f2;
        padding: 8px 14px;
        border-radius: 8px;
        display: inline-block;
        text-align: center;
        margin-left: auto;
        min-width: 100px;
        max-width: 100%;
    }

    .detail-value.status-Vacation {
        color: #0dcaf0;
        background: #defafa;
        padding: 8px 14px;
        border-radius: 8px;
        display: inline-block;
        text-align: center;
        margin-left: auto;
        min-width: 100px;
        max-width: 100%;
    }

    .bike-actions-compact {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 2px solid #f1f5f9;
        width: 100%;
        box-sizing: border-box;
    }
    
    .btn-compact {
        flex: 1;
        min-width: 160px;
        padding: 14px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s ease;
        text-decoration: none;
        border: 2px solid transparent;
        cursor: pointer;
        box-sizing: border-box;
        max-width: 100%;
        word-break: break-word;
    }
    
    .btn-compact i {
        font-size: 1.1rem;
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
        }
        
        .btn-compact {
            min-width: 100%;
            padding: 12px 16px;
            font-size: 0.9rem;
        }
        
        .detail-value {
            text-align: right;
            padding-left: 0;
            font-size: 1.1rem;
        }
        
        .detail-value.status-active,
        .detail-value.status-inactive {
            margin-left: 0;
            text-align: center;
            width: 100%;
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
    }
    
    @media (min-width: 769px) and (max-width: 992px) {
        .bike-info-sidebar {
            max-width: 440px;
        }
        
        .number-plate {
            min-width: 170px;
            max-width: 85%;
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
    }
    
    /* Extra small devices */
    @media (max-width: 480px) {
        .bike-info-sidebar {
            padding: 15px 10px;
        }
        
        .vehicle-type {
            font-size: 1.3rem;
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
        
        .detail-value {
            font-size: 1rem;
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
                    'rak' => ['arabic' => 'رأس الخــيمة', 'english' => 'RAS AL KHAIMAH'],
                    'uaq' => ['arabic' => 'ام القوين', 'english' => 'UMM AL QUWAIN'],
                ];
                
                $currentEmirate = $emiratesData[$emirateCode] ?? null;
            ?>
            
            <div class="plate-container">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentEmirate): ?>
                    <!-- Unified RAK-style plate for all emirates -->
                    <div class="number-plate ">
                        <!-- Arabic emirate name - top left corner -->
                        <div class="plate-arabic-corner"><?php echo e($currentEmirate['arabic']); ?></div>
                        
                        <!-- Bike code - top right corner -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bikeCode != 'N/A'): ?>
                            <div class="plate-bike-code-corner"><?php echo e($bikeCode); ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        
                        <!-- Plate number - center -->
                        <div class="plate-number"><?php echo e($plateNumber); ?></div>
                        
                        <!-- English emirate name - bottom center -->
                        <div class="plate-emirate-english"><?php echo e($currentEmirate['english']); ?></div>
                    </div>
                <?php else: ?>
                    <!-- Default Badge for other emirates -->
                    <div class="plate-default">
                        <?php echo e($bikes->emirates ?? 'N/A'); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
        
        <!-- Compact Details List -->
        <div class="bike-details-list">
            
            <div class="detail-row">
                <span class="detail-label">Leasing Company</span>
                <span class="detail-value">
                    <?php
                        $company = DB::table('leasing_companies')->where('id', $bikes->company)->first();
                        echo $company->name ?? 'N/A';
                    ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value <?php if($bikes->status == 1): ?> status-Active <?php else: ?> status-Inactive <?php endif; ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bikes->status == 1): ?>
                        Active
                    <?php else: ?>
                        Inactive
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">WareHouse</span>
                <span class="detail-value status-<?php echo e($bikes->warehouse); ?>"><?php echo e($bikes->warehouse ?? 'N/A'); ?></span>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="bike-actions-compact">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('item_edit')): ?>
            <a href="<?php echo e(route('bikes.edit', $bikes->id)); ?>" 
               class="btn-compact btn-edit-compact show-modal"
               data-title="Edit Vehicle #<?php echo e($bikes->plate); ?>">
                <i class="fas fa-edit"></i>
                <span>Edit Details</span>
            </a>
            <?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bikes->rider_id): ?>
            <a href="javascript:void(0);" 
               class="btn-compact btn-view-assignment show-modal"
               data-size="xl"
               data-title="Assigned Rider Details"
               data-action="<?php echo e(route('bikes.assignrider', $bikes->id)); ?>">
                <i class="fas fa-user-check"></i>
                <span>View Assignment</span>
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
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/bikes/view.blade.php ENDPATH**/ ?>