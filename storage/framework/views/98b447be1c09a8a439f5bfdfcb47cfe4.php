<?php $__env->startSection('page_content'); ?>
<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-user ti-sm me-1_5 me-2" style=" background: #cadaef;color: #024baa;"></i><b>Personal Information</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="personal">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">

                <label class="required">Rider ID </label>
                <p><?php echo e($result['rider_id']); ?></p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Courier ID </label>
                <p><?php echo e(@$result['courier_id']); ?></p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Rider Name </label>
                <p><?php echo e(@$result['name']); ?></p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Rider Contact</label>
                <p><?php echo e(@$result['personal_contact']); ?></p>
            </div>

            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Company Contact</label>
                <p><?php echo e(@$result['company_contact']); ?></p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Personal Gmail ID </label>
                <p><?php echo e(@$result['personal_email']); ?></p>
            </div>
            <!--col-->
            
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Nationality </label>
                <p><?php echo e($rider?->country?->name); ?></p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Ethnicity</label>
                <p><?php echo e(@$result['ethnicity']); ?></p>

            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>DOB </label>
                <p><?php echo e(@App\Helpers\General::DateFormat($result['dob'])); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Vendor </label>
                <p><?php echo e(@$rider->vendor->name); ?></p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Customer </label>
                <p><?php echo e(@$rider->customer->name); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Recruiter </label>
                <p><?php echo e(@$rider->recruiter->name); ?></p>
            </div>
        </div>

    </div>
    <!-- Edit Form for Personal Information -->
    <div class="card-body edit-form" id="edit-personal" style="display: none;">
        <form class="section-form" data-section="personal">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label class="required">Rider ID</label>
                    <input type="text" class="form-control form-control-sm" name="rider_id" value="<?php echo e($result['rider_id']); ?>" readonly>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Courier ID</label>
                    <input type="text" class="form-control form-control-sm" name="courier_id" value="<?php echo e(@$result['courier_id']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Rider Name</label>
                    <input type="text" class="form-control form-control-sm" name="name" value="<?php echo e(@$result['name']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Rider Contact</label>
                    <input type="text" class="form-control form-control-sm" name="personal_contact" value="<?php echo e(@$result['personal_contact']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Company Contact</label>
                    <input type="text" class="form-control form-control-sm" name="company_contact" value="<?php echo e(@$result['company_contact']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Personal Gmail ID</label>
                    <input type="email" class="form-control form-control-sm" name="personal_email" value="<?php echo e(@$result['personal_email']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Nationality</label>
                    <select class="form-control form-control-sm select2" name="nationality">
                        <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($id); ?>" <?php echo e($result['nationality'] == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Ethnicity</label>
                    <input type="text" class="form-control form-control-sm" name="ethnicity" value="<?php echo e(@$result['ethnicity']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>DOB</label>
                    <input type="date" class="form-control form-control-sm" name="dob" value="<?php echo e(@$result['dob']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Vendor</label>
                    <select class="form-control form-control-sm select2" name="VID">
                        <?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($id); ?>" <?php echo e($result['VID'] == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Recruiter</label>
                    <select class="form-control form-control-sm select2" name="recruiter_id">
                        <option value="">Select Recruiter</option>
                        <?php $__currentLoopData = DB::table('recruiters')->where('status', 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value->id); ?>" <?php echo e($result['recruiter_id'] == $value->id ? 'selected' : ''); ?>><?php echo e($value->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-briefcase ti-sm me-1_5 me-2" style=" background: #a002aa38;color: #a002aa;"></i><b>Job Detail</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="job">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Date of Joining </label>
                <p><?php echo e(@App\Helpers\General::DateFormat($result['doj'])); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Project </label>
                <p><?php echo e(@$rider->customer->name); ?></p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Designation </label>
                <p><?php echo e(@$result['designation']); ?></p>
            </div>
            
            <div class="col-md-3 form-group col-3">
                <label>CDM Deposit ID</label>
                <p><?php echo e(@$result['cdm_deposit_id']); ?></p>
            </div>
            
            <div class="col-md-3 form-group col-3">
                <label>Fleet Supervisor </label>
                <p><?php echo e(@$result['fleet_supervisor']); ?></p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Status </label>
                <p><?php echo e(App\Helpers\General::RiderStatus(@$result['status'])); ?></p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Salary Model </label>
                <p><?php echo e(@$result['salary_model']); ?></p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Rider Reference </label>
                <p><?php echo e(@$result['rider_reference']); ?></p>
            </div>
        </div>
    </div>
    <!-- Edit Form for Job Detail -->
    <div class="card-body edit-form" id="edit-job" style="display: none;">
        <form class="section-form" data-section="job">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Date of Joining</label>
                    <input type="date" class="form-control form-control-sm" name="doj" value="<?php echo e(@$result['doj']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Project</label>
                    <input type="text" class="form-control form-control-sm" name="project" value="<?php echo e(@$rider->project->name ?? ''); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Designation</label>
                    <input type="text" class="form-control form-control-sm" name="designation" value="<?php echo e(@$result['designation']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>CDM Deposit ID</label>
                    <input type="text" class="form-control form-control-sm" name="cdm_deposit_id" value="<?php echo e(@$result['cdm_deposit_id']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Fleet Supervisor</label>
                    <select class="form-control form-control-sm select2" name="fleet_supervisor">
                        <option value="">Select Fleet Supervisor</option>
                        <?php
                        $supervisorRow = DB::table('dropdowns')
                        ->where('label', 'Fleet Supervisor')
                        ->whereNotNull('values')
                        ->first();
                        $fleetSupervisors = [];
                        if ($supervisorRow && $supervisorRow->values) {
                        $fleetSupervisors = json_decode($supervisorRow->values, true);
                        }
                        ?>
                        <?php $__currentLoopData = $fleetSupervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($supervisor); ?>" <?php echo e(@$result['fleet_supervisor'] == $supervisor ? 'selected' : ''); ?>><?php echo e($supervisor); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Status</label>
                    <select class="form-control form-control-sm select2" name="status">
                        <option value="1" <?php echo e(@$result['status'] == 1 ? 'selected' : ''); ?>>Active</option>
                        <option value="2" <?php echo e(@$result['status'] == 2 ? 'selected' : ''); ?>>Inactive</option>
                        <option value="3" <?php echo e(@$result['status'] == 3 ? 'selected' : ''); ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Salary Model</label>
                    <input type="text" class="form-control form-control-sm" name="salary_model" value="<?php echo e(@$result['salary_model']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Rider Reference</label>
                    <input type="text" class="form-control form-control-sm" name="rider_reference" value="<?php echo e(@$result['rider_reference']); ?>">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-note ti-sm me-1_5 me-2" style=" background: #3a3a3c52;color: #3a3a3c;"></i><b>Visa & Registerations</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="visa">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Visa Sponsor</label>
                <p><?php echo e(@$result['visa_sponsor']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Occupation on Visa </label>
                <p><?php echo e(@$result['visa_occupation']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Visa Status</label>
                <p><?php echo e(@$result['visa_status']); ?></p>

            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Emirate ID </label>
                <p><?php echo e(@$result['emirate_id']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label <?php if(strtotime($result['emirate_exp']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>>EID EXP Date </label>
                <p <?php if(strtotime($result['emirate_exp']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>><?php echo e(@App\Helpers\General::DateFormat($result['emirate_exp'])); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Licence No </label>
                <p><?php echo e(@$result['license_no']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label <?php if(strtotime($result['license_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>>Licence Expiry </label>
                <p <?php if(strtotime($result['license_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>><?php echo e(@App\Helpers\General::DateFormat($result['license_expiry'])); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Passport </label>
                <p><?php echo e(@$result['passport']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label <?php if(strtotime($result['passport_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>>Passport Expiry </label>
                <p <?php if(strtotime($result['passport_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>><?php echo e(@App\Helpers\General::DateFormat($result['passport_expiry'])); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Passport Handover </label>
                <p><?php echo e(@$result['passport_handover']); ?></p>
            </div>
        </div>
    </div>
    <!-- Edit Form for Visa & Registrations -->
    <div class="card-body edit-form" id="edit-visa" style="display: none;">
        <form class="section-form" data-section="visa">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Visa Sponsor</label>
                    <input type="text" class="form-control form-control-sm" name="visa_sponsor" value="<?php echo e(@$result['visa_sponsor']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Occupation on Visa</label>
                    <input type="text" class="form-control form-control-sm" name="visa_occupation" value="<?php echo e(@$result['visa_occupation']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Visa Status</label>
                    <input type="text" class="form-control form-control-sm" name="visa_status" value="<?php echo e(@$result['visa_status']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Emirate ID</label>
                    <input type="text" class="form-control form-control-sm" name="emirate_id" value="<?php echo e(@$result['emirate_id']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>EID EXP Date</label>
                    <input type="date" class="form-control form-control-sm" name="emirate_exp" value="<?php echo e(@$result['emirate_exp']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Licence No</label>
                    <input type="text" class="form-control form-control-sm" name="license_no" value="<?php echo e(@$result['license_no']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Licence Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="license_expiry" value="<?php echo e(@$result['license_expiry']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport</label>
                    <input type="text" class="form-control form-control-sm" name="passport" value="<?php echo e(@$result['passport']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="passport_expiry" value="<?php echo e(@$result['passport_expiry']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport Handover</label>
                    <input type="text" class="form-control form-control-sm" name="passport_handover" value="<?php echo e(@$result['passport_handover']); ?>">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-user ti-sm me-1_5 me-2" style=" background: #3a3a3c52;color: #3a3a3c;"></i><b>Labor Info</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="labor">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Person Code</label>
                <p><?php echo e(@$result['person_code']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Labor Card Number</label>
                <p><?php echo e(@$result['labor_card_number']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label <?php if(strtotime($result['labor_card_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>>Labor Card Expiry </label>
                <p <?php if(strtotime($result['labor_card_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>><?php echo e(@App\Helpers\General::DateFormat($result['labor_card_expiry'])); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Insurance</label>
                <p><?php echo e(@$result['insurance']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label <?php if(strtotime($result['insurance_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>>Insurance Expiry</label>
                <p <?php if(strtotime($result['insurance_expiry']) <=strtotime(date('Y-m-d'))): ?> style="color:red;" <?php endif; ?>><?php echo e(@App\Helpers\General::DateFormat($result['insurance_expiry'])); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Policy No: </label>
                <p><?php echo e(@$result['policy_no']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>WPS: </label>
                <p><?php echo e(@$result['wps']); ?></p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>C3 Card:</label>
                <p><?php echo e(@$result['c3_card']); ?></p>
            </div>
        </div>
    </div>
    <!-- Edit Form for Labor Info -->
    <div class="card-body edit-form" id="edit-labor" style="display: none;">
        <form class="section-form" data-section="labor">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Person Code</label>
                    <input type="text" class="form-control form-control-sm" name="person_code" value="<?php echo e(@$result['person_code']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Labor Card Number</label>
                    <input type="text" class="form-control form-control-sm" name="labor_card_number" value="<?php echo e(@$result['labor_card_number']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Labor Card Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="labor_card_expiry" value="<?php echo e(@$result['labor_card_expiry']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Insurance</label>
                    <input type="text" class="form-control form-control-sm" name="insurance" value="<?php echo e(@$result['insurance']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Insurance Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="insurance_expiry" value="<?php echo e(@$result['insurance_expiry']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Policy No:</label>
                    <input type="text" class="form-control form-control-sm" name="policy_no" value="<?php echo e(@$result['policy_no']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>WPS:</label>
                    <input type="text" class="form-control form-control-sm" name="wps" value="<?php echo e(@$result['wps']); ?>">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>C3 Card:</label>
                    <input type="text" class="form-control form-control-sm" name="c3_card" value="<?php echo e(@$result['c3_card']); ?>">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



<style>
    .edit-form {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        border-radius: 23px;
    }

    .edit-btn {
        font-size: 12px;
        padding: 4px 1px;
        border-radius: 70px;
    }

    .section-form .form-control-sm {
        font-size: 12px;
    }

    .card-header .d-flex {
        align-items: center;
    }

    /* Select2 styling for small forms */
    .edit-form .select2-container .select2-selection--single {
        height: 31px;
        font-size: 12px;
    }

    .edit-form .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 29px;
        padding-left: 8px;
        padding-right: 20px;
    }

    .edit-form .select2-container .select2-selection--single .select2-selection__arrow {
        height: 29px;
        right: 3px;
    }

    .edit-form .select2-dropdown {
        font-size: 12px;
    }

    .edit-form .select2-container--bootstrap4 .select2-selection--single {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
</style>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>
<script>
    $(document).ready(function() {
        // Initialize Select2 for all select elements
        function initializeSelect2() {
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Select an option'
            });
        }

        // Handle edit button clicks
        $('.edit-btn').click(function() {
            const section = $(this).data('section');
            const editForm = $('#edit-' + section);
            const displaySection = $(this).closest('.card').find('.card-body').first();

            // Hide display section and show edit form
            displaySection.hide();
            editForm.show();

            // Initialize Select2 for this section's dropdowns
            editForm.find('.select2').select2({
                width: '100%',
                placeholder: 'Select an option'
            });

            // Change button text to Cancel
            $(this).html('<i class="ti ti-x me-1"></i>').removeClass('btn-primary').addClass('btn-secondary').addClass('cancel-edit');
        });

        // Handle cancel button clicks
        $(document).on('click', '.cancel-edit', function() {
            const section = $(this).data('section') || $(this).closest('form').data('section');
            const editForm = $('#edit-' + section);
            const displaySection = $(this).closest('.card').find('.card-body').first();
            const editBtn = $(this).closest('.card').find('.edit-btn');

            // Note: Select2 instances will be re-initialized when the form is shown again

            // Hide edit form and show display section
            editForm.hide();
            displaySection.show();

            // Reset button
            editBtn.html('<i class="ti ti-edit me-1"></i>').removeClass('btn-secondary cancel-edit').addClass('btn-primary');
        });

        // Handle form submissions
        $('.section-form').submit(function(e) {
            e.preventDefault();

            const form = $(this);
            const section = form.data('section');
            const formData = new FormData(this);
            formData.append('section', section);

            // Add loading state
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

            $.ajax({
                url: '<?php echo e(route("riders.updateSection", $rider->id)); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        toastr.success(response.message);

                        // Reload the page to show updated data
                        location.reload();
                    } else {
                        toastr.error('Error updating ' + section + ' information');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessage = 'Validation errors:\n';
                        Object.keys(errors).forEach(function(key) {
                            errorMessage += errors[key][0] + '\n';
                        });
                        toastr.error(errorMessage);
                    } else {
                        toastr.error('Error updating ' + section + ' information');
                    }
                },
                complete: function() {
                    // Reset button state
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('riders.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/riders/show_fields.blade.php ENDPATH**/ ?>