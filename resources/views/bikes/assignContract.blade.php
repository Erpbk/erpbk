<!DOCTYPE html>
<html lang="en">
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Rider# {{$contract->rider->rider_id}} Bike Handing Over</title>
      <!-- Bootstrap CSS -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

      <style type="text/css"> 
         /* ========== COMPACT STYLES FOR 3-PAGE PRINT ========== */
         * {margin:0; padding:0; text-indent:0; box-sizing: border-box; }
         body { 
            font-family: 'Segoe UI', Calibri, sans-serif; 
            line-height: 1.3;
            background: #f5f7fa;
            padding: 10px 200px;
            font-size: 9pt; 
         }
         
         .document-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
         }
         
         .s1 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 14pt; }
         .s2 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 9pt; }
         h4 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 8.5pt; margin: 4px 0; }
         .s3 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 8.5pt; }
         .s4 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 8.5pt; }
         .s5 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 8.5pt; }
         .s6 { color: black; font-family:"Times New Roman", serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 8.5pt; }
         .s7 { color: #15C; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: underline; font-size: 8.5pt; }
         .s8 { color: #1F1F1F; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 8.5pt; }
         .s9 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 8.5pt; }
         .s10 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 8.5pt; }
         p { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 9pt; margin:0pt; line-height: 1.2; }
         h3 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 9pt; }
         h1 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 10pt; }
         .s11 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 9.5pt; }
         .s13 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 9.5pt; }
         .s14 { color: #15C; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: underline; font-size: 9.5pt; }
         .s15 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 9.5pt; }
         .s16 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }
         h2 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 9.5pt; }
         .s18 { color: black; font-family:Cambria, serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 9.5pt; }
         
         /* ========== COMPACT TABLE STYLES ========== */
         table, tbody {vertical-align: top; overflow: visible; border-collapse: collapse; }
         table { width: 100%; margin: 8px 0; }
         th, td { 
            padding: 3px 4px !important; /* Reduced padding */
            border: 1px solid #ccc !important; 
            vertical-align: middle;
            font-size: 8.5pt;
            line-height: 1.1;
         }
         
         br { line-height: 0.5; }
         
         .editable-field {
            width: 100%;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: inherit;
            color: inherit;
            padding: 2px 4px;
            margin: 0;
            min-height: auto;
         }

         .editable-div {
            width: 100%;
            min-height: 40px;
            border: 1px solid #ccc;
            padding: 6px;
            font-family: inherit;
            font-size: inherit;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow: visible;
            background: transparent;
        }

        body.edit-mode .editable-div[contenteditable="true"] {
            background-color: #e7f5ff;
            border-color: #4dabf7;
            outline: none;
        }
         
         .editable-textarea {
            width: 100%;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: inherit;
            color: inherit;
            padding: 3px;
            resize: vertical;
            min-height: 40px; 
            line-height: 1.1;
         }
         
         .checkbox-container {
            display: flex;
            align-items: center;
            gap: 3px;
         }
         
         .checkbox-container input[type="checkbox"] {
            width: 12px; 
            height: 12px;
            margin: 0;
         }
         
         .control-panel {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 15px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         }
         
         .control-panel h2 {
            color: white;
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
         }
         
         .edit-mode-indicator {
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 12px;
            display: none;
         }
         
         /* Page 3 specific styles */
         .clause-number {
            font-weight: bold;
            margin-right: 5px;
         }
         
         .clause-title {
            font-weight: bold;
            margin-bottom: 3px;
         }
         
         .clause-content {
            margin-bottom: 8px;
            text-align: justify;
         }
         
         @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8pt !important;
            }
            
            .document-container {
                max-width: 100% !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
            }

            .editable-div {
                border: none !important;
                background: transparent !important;
                overflow: visible !important;
                height: auto !important;
                min-height: auto !important;
            }
            
            .control-panel,
            .edit-mode-indicator,
            .no-print {
                display: none !important;
            }
            
            table {
                margin: 5px 0 !important;
            }
            
            th, td {
                padding: 2px 3px !important;
                font-size: 8pt !important;
            }
            
            .editable-field,
            .editable-textarea {
                padding: 1px 2px !important;
                font-size: 8pt !important;
            }
            
            .editable-textarea {
                min-height: 35px !important;
            }
            
            .page-break {
                page-break-before: always;
                margin-top: 20px;
            }
            
            /* Force compact layout for print */
            .compact-spacing {
                margin-top: 5px !important;
                margin-bottom: 5px !important;
            }
            
            .compact-padding {
                padding-top: 3px !important;
                padding-bottom: 3px !important;
            }

            input[type="checkbox"] {
               -webkit-print-color-adjust: exact !important;
               print-color-adjust: exact !important;
               color-adjust: exact !important;
               appearance: none !important;
               -webkit-appearance: none !important;
               background-color: white !important;
               border: 1px solid black !important;
            }
            
            input[type="checkbox"]:checked {
               background-color: black !important;
               background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3E%3Cpath fill='white' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26l2.974 2.99L8 2.193z'/%3E%3C/svg%3E") !important;
               background-repeat: no-repeat !important;
               background-position: center center !important;
               background-size: 8px 8px !important;
            }
         }
         
         /* ========== UTILITY CLASSES FOR COMPACT LAYOUT ========== */
         .compact-row { margin: 0 !important; padding: 0 !important; }
         .compact-cell { padding: 2px !important; }
         .small-text { font-size: 8pt !important; }
         .no-margin { margin: 0 !important; }
         .no-padding { padding: 0 !important; }
         .tight-line { line-height: 1 !important; }
         
         /* ========== LOCKED STATE ========== */
         body.locked .editable-field,
         body.locked .editable-textarea {
            pointer-events: none;
            background-color: #f8f9fa;
         }
         
         body.locked .checkbox-container input[type="checkbox"] {
            pointer-events: none;
         }
      </style>
   </head>
   <body>
      <div class="document-container">
         <!-- Control Panel -->
         <div class="control-panel no-print">
            <h2><i class="fas fa-motorcycle"></i> Bike Handover Contract</h2>
            <div class="d-flex gap-2 align-items-center">
               <button id="editToggle" class="btn btn-light btn-sm d-flex align-items-center gap-1">
                  <i class="fas fa-edit"></i> Enable Editing
               </button>
               <button id="printBtn" class="btn btn-light btn-sm d-flex align-items-center gap-1">
                  <i class="fas fa-print"></i> Print
               </button>
            </div>
            <div class="edit-mode-indicator">
               <i class="fas fa-pencil-alt"></i> Edit Mode
            </div>
         </div>
         
         <div class="container-fluid p-3">
            <!-- PAGE 1: RIDER DATA & BIKE DETAILS -->
            <div class="text-center mb-3 pb-2 border-bottom border-2 border-dark">
                <h1 class="fw-bold mb-0" style="font-size: 16pt;">
                    BIKE HANDOVER
                </h1>
            </div>
            
            <!-- Rider Data Section -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="mb-0">Rider Details :</h2>
                <input type="text" class="editable-field form-control-sm" id="date" value="{{@$contract->note_date->format('Y-m-d')}}" style="width: 20%">
            </div>    
            
            <!-- Personal Details Table -->
            <table class="table table-sm table-bordered mb-2">
               <tr>
                  <td class="w-15"><p class="s3 mb-0">Name</p></td>
                  <td class="w-35"><input type="text" class="editable-field form-control-sm" id="name" value="{{@$contract->rider->name}}"></td>
                  <td class="w-15"><p class="s3 mb-0">RIDER I.D.</p></td>
                  <td class="w-35"><input type="text" class="editable-field form-control-sm" id="riderId" value="{{@$contract->rider->rider_id}}"></td>
               </tr>
               <tr>
                  <td><p class="s3 mb-0">Emirates ID.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="emirateId" value="{{@$contract->rider->emirate_id}}"></td>
                  <td><p class="s3 mb-0">Passport No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="passport" value="{{@$contract->rider->passport}}"></td>
               </tr>
               <tr>
                  <td><p class="s3 mb-0">Phone No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="phone" value="{{@$contract->rider->personal_contact}}"></td>
                  <td><p class="s3 mb-0">License No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="license" value="{{@$contract->rider->license_no}}"></td>
               </tr>
               <tr>
                  <td><p class="s3 mb-0">Email I.D.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="email" value="{{@$contract->rider->personal_email}}"></td>
                  <td><p class="s3 mb-0">Emirate.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="emirate" value="{{@$contract->rider->emirate_hub}}"></td>
               </tr>
            </table>
            
            <!-- Mobile Sim Detail -->
            <h2 class="mt-2 mb-1">Sim Detail :</h2>
            <table class="table table-sm table-bordered mb-2">
               <tr>
                  <td class="w-30">
                     <div class="checkbox-container">
                        <input type="checkbox" id="companySim">
                        <label for="companySim" class="s3 mb-0">Company Sim Number.</label>
                     </div>
                  </td>
                  <td class="w-25"><input type="text" class="editable-field form-control-sm" id="simNumber" value="{{@$contract->rider->sims->sim_number}}"></td>
                  <td class="w-15"><p class="s3 mb-0">EMI Number.</p></td>
                  <td class="w-30"><input type="text" class="editable-field form-control-sm" id="simEmi" value="{{@$contract->rider->sims->sim_emi}}"></td>
               </tr>
            </table>
            
            <!-- Details of Bike -->
            <h2 class="mt-2 mb-1">Bike Details :</h2>
            <table class="table table-sm table-bordered mb-2">
               <tr>
                  <td class="w-15"><p class="s3 mb-0"><span class="s7">T.C.No</span>.</p></td>
                  <td class="w-30"><input type="text" class="editable-field form-control-sm" id="trafficFileNumber" value="{{@$contract->bike->traffic_file_number}}"></td>
                  <td class="w-20"><p class="s3 mb-0">Bike Plate No.</p></td>
                  <td class="w-35"><input type="text" class="editable-field form-control-sm" id="plate" value="{{@$contract->bike->plate}}"></td>
               </tr>
               <tr>
                  <td><p class="s3 mb-0">Model No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="model" value="{{@$contract->bike->model}}"></td>
                  <td><p class="s3 mb-0">Bike</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="modelType" value="{{@$contract->bike->model_type}}"></td>
               </tr>
               <tr>
                  <td><p class="s3 mb-0">Engine No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="engine" value="{{@$contract->bike->engine}}"></td>
                  <td><p class="s3 mb-0">Chassis No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="chassis" value="{{@$contract->bike->chassis_number}}"></td>
               </tr>
               <tr>
                  <td colspan="4" class="text-center py-1">
                     <div class="checkbox-container d-flex justify-content-center">
                        <input type="checkbox" id="motorcycle">
                        <label for="motorcycle" class="s3 mb-0">Motorcycle</label>
                     </div>
                  </td>
               </tr>
            </table>
            
            <!-- Compact Checkbox Grid -->
            <div class="row mb-2">
               <div class="col-4">
                  <table class="table table-sm table-borderless">
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="dent"> <label class="s3 mb-0">Dent</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="scratch"> <label class="s3 mb-0">Scratch</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="majorDamage"> <label class="s3 mb-0">Major Damage</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="deliveryBox"> <label class="s3 mb-0">Delivery Box</label></div></td></tr>
                  </table>
               </div>
               <div class="col-4">
                  <table class="table table-sm table-borderless">
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="mobileHolder"> <label class="s3 mb-0">Mobile Holder</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="toolKit"> <label class="s3 mb-0">Tool Kit</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="reflectingSticker"> <label class="s3 mb-0">Reflecting Sticker</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="motorcycleSeat"> <label class="s3 mb-0">Motorcycle Seat</label></div></td></tr>
                  </table>
               </div>
               <div class="col-4">
                  <table class="table table-sm table-borderless">
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="crashGuard"> <label class="s3 mb-0">Crash Guard</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="helmet"> <label class="s3 mb-0">Helmet</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="stand"> <label class="s3 mb-0">Stand</label></div></td></tr>
                     <tr><td class="p-1"><div class="checkbox-container"><input type="checkbox" id="rtaFine"> <label class="s3 mb-0">RTA Fine</label></div></td></tr>
                  </table>
               </div>
            </div>
            
            <!-- Additional checkboxes in single row -->
            <div class="row mb-2">
               <div class="col-4">
                  <div class="checkbox-container">
                     <input type="checkbox" id="fuelCard">
                     <label class="s3 mb-0" for="fuelCard">Fuel Card</label>
                  </div>
               </div>
               <div class="col-4">
                  <div class="checkbox-container">
                     <input type="checkbox" id="salik">
                     <label class="s3 mb-0" for="salik">Salik</label>
                  </div>
               </div>
               <div class="col-4">
                  <!-- Empty for alignment -->
               </div>
            </div>
            
            <!-- Remarks -->
            <h2 class="text-center my-2">Remarks</h2>
            <div class="mb-2" style="min-height: 170px;">
               <table class="table table-borderless" style="height: 170px;">
                   <tr><td class="border-bottom border-secondary p-1" style="height: 20px;">&nbsp;</td></tr>
                   <tr><td class="border-bottom border-secondary p-1" style="height: 20px;">&nbsp;</td></tr>
                   <tr><td class="border-bottom border-secondary p-1" style="height: 20px;">&nbsp;</td></tr>
                   <tr><td class="border-bottom border-secondary p-1" style="height: 20px;">&nbsp;</td></tr>
                   <tr><td class="border-bottom border-secondary p-1" style="height: 20px;">&nbsp;</td></tr>
                   <tr><td class="border-bottom border-secondary p-1" style="height: 20px;">&nbsp;</td></tr>
                   <tr><td class="border-bottom border-secondary p-1" style="height: 20px;">&nbsp;</td></tr>
                   <tr><td class="p-1" style="height: 20px;">&nbsp;</td></tr>
               </table>
            </div>
            
            <!-- Declaration Text -->
            <p class="text-center" style="font-size: 9pt; line-height: 1.2; margin: 10px 0;">
               I have thoroughly Checked and understood the present condition and damage on the motorcycle. I will be responsible for any new damages and shall bear all expenses for repair of the motorcycle, I will be responsible for any new damages as per the terms and condition mentioned on the handing over Document.
            </p>
            
            <!-- Signatures (Page 1) -->
            <div class="mt-5 pt-4">
               <div class="row">
                  <div class="col-4 text-center">
                     <div class="border-bottom border-dark mx-auto" style="width: 80%; margin-bottom: 10px;"></div>
                     <input type="text" class="editable-field form-control-sm text-center mx-auto" id="mechanic" value="Muhammad Sufyan Akbar Ali" style="width: 80%; border: none; margin-bottom: 5px;">
                     <p class="fw-bold mt-1">Motor Mechanic</p>
                  </div>
                  <div class="col-4 text-center">
                     <div class="border-bottom border-dark mx-auto" style="width: 80%; margin-bottom: 10px;"></div>
                     <input type="text" class="editable-field form-control-sm text-center mx-auto" id="riderSignature" value="{{@$contract->rider->name}}" style="width: 80%; border: none; margin-bottom: 5px;">
                     <p class="fw-bold mt-1">Rider Signature</p>
                  </div>
                  <div class="col-4 text-center">
                     <div class="border-bottom border-dark mx-auto" style="width: 80%; margin-bottom: 10px;"></div>
                     <input type="text" class="editable-field form-control-sm text-center mx-auto" id="supervisorSignature" value="{{@$contract->rider->fleet_supervisor}}" style="width: 80%; border: none; margin-bottom: 5px;">
                     <p class="fw-bold mt-1">Supervisor Signature</p>
                  </div>
               </div>
            </div>
            
            <!-- PAGE BREAK -->
            <div class="page-break"></div>
            
            <!-- PAGE 2: DECLARATION ONLY -->
            <h1 class="mt-3 mb-2">
               Declaration : <span class="float-end">Date: <input type="text" class="editable-field form-control-sm d-inline" id="declarationDate" value="{{@$contract->note_date}}" style="width: 100px;"></span>
            </h1>
            
            <p class="s11 mb-2">
               Confirmation of receipt of a "<b>Bike</b>": I hereby certify, I am 
            </p>
            
            <table class="table table-sm table-bordered mb-2">
               <tr>
                  <td class="w-15"><p class="s13 mb-0">Name</p></td>
                  <td class="w-35"><input type="text" class="editable-field form-control-sm" id="declarationName" value="{{@$contract->rider->name}}"></td>
                  <td class="w-15"><p class="s13 mb-0">Emirates I.D</p></td>
                  <td class="w-35"><input type="text" class="editable-field form-control-sm" id="declarationEmirateId" value="{{@$contract->rider->emirate_id}}"></td>
               </tr>
            </table>
            
            <p class="s11 my-2">
               I have received the following bike :
            </p>
            
            <table class="table table-sm table-bordered mb-2">
               <tr>
                  <td class="w-15"><p class="s13 mb-0"><span class="s14">T.C.No</span>.</p></td>
                  <td class="w-35"><input type="text" class="editable-field form-control-sm" id="declarationTcNo" value="{{@$contract->bike->traffic_file_number}}"></td>
                  <td class="w-15"><p class="s13 mb-0">Bike Plate No.</p></td>
                  <td class="w-35"><input type="text" class="editable-field form-control-sm" id="declarationPlate" value="{{@$contract->bike->plate}}"></td>
               </tr>
               <tr>
                  <td><p class="s13 mb-0">Model No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="declarationModel" value="{{@$contract->bike->model}}"></td>
                  <td><p class="s13 mb-0">Bike</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="declarationBikeType" value="{{@$contract->bike->model_type}}"></td>
               </tr>
               <tr>
                  <td><p class="s13 mb-0">Engine No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="declarationEngine" value="{{@$contract->bike->engine}}"></td>
                  <td><p class="s13 mb-0">Chassis No.</p></td>
                  <td><input type="text" class="editable-field form-control-sm" id="declarationChassis" value="{{@$contract->bike->chassis_number}}"></td>
               </tr>
            </table>
            
            <!-- Declaration Text Area -->
            <div class="border border-dark p-2 my-2" style="min-height: 180px;">
               <div class="editable-div" id="declarationText" contenteditable="false" style="min-height: 170px; border: none; padding: 8px;">
I undertake to preserve and use the bike for the purpose for which I received it and acknowledge that I am fully responsible for its maintenance and care. I commit to return the bike to the company upon its request or at the end of the contract, in the same condition as I received it.

In the event of non-return, I agree to pay an amount of <b>AED 8,000</b> for the bike and <b>AED 2,000</b> for Noon Assets (Delivery Bag & Noon Uniform with accessories), totaling <b>AED 10,000</b>.

I acknowledge that it is my responsibility to return the bike to the company upon termination of employment. Should I fail to do so, I shall be liable to pay the monthly rent of <b>AED 600</b>, any RTA fines, Salik charges, and any damages to the bike until it is returned in good working condition.
               </div>
            </div>
            
            <h2 class="mb-1">IN WITNESS WHEREOF</h2>
            
            <p class="s18 mb-3">
               the Parties have executed this Contract as of the date first indicated above
            </p>
            
            <!-- Signatures -->
            <div class="row mt-5">
               <div class="col-6">
                  <p class="fw-bold">Express Fast Delivery Service Name & Signature</p>
               </div>
               <div class="col-6 text-center">
                  <p class="fw-bold">Fingerprint<br/>(Thumbprint of the right hand)</p>
                  <div class="border-bottom border-secondary mx-auto" style="height: 60px; margin: 5px 0 15px 0;"></div>
                  <p class="fw-bold">Name & Signature<br/>
                     <input type="text" class="editable-field form-control-sm text-center mx-auto" id="finalSignature" value="{{@$contract->rider->name}}" style="width: 90%; border: none;">
                  </p>
               </div>
            </div>
            
            <!-- PAGE BREAK -->
            <div class="page-break"></div>
            
            <!-- PAGE 3: MAINTENANCE & LIABILITY CLAUSES -->
            <div class="text-center mb-3 pb-2 border-bottom border-2 border-dark">
                <h1 class="fw-bold mb-0" style="font-size: 16pt;">
                    BIKE MAINTENANCE & LIABILITY CLAUSES
                </h1>
                <p class="fst-italic mb-0" style="font-size: 9pt; margin: 3px 0 0 0;">
                    (In accordance with UAE labor Law and applicable transport regulations)
                </p>
            </div>
            
            <!-- Clauses Section -->
            <div class="mt-2" style="font-size: 10pt;">
                <!-- Clause 1 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">1.</span> Excess Mileage Charges
                    </div>
                    <p class="text-justify ms-3">
                        The Rider shall comply with the approved mileage limit assigned by the Company. In case of excess usage, the Rider agrees to pay <b>AED 1 (One Dirham) per additional kilometer</b>, which may be deducted from salary or any other dues in accordance with UAE labor Law.
                    </p>
                </div>
                
                <!-- Clause 2 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">2.</span> Accident Without Police Report
                    </div>
                    <p class="text-justify ms-3">
                        In the event of any accident where the Rider fails to obtain a valid police report, <b>the Rider shall bear full responsibility for all repair, recovery, and related costs of the bike</b>. The Company shall not be liable for insurance claims in such cases.
                    </p>
                </div>
                
                <!-- Clause 3 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">3.</span> Accident – Rider at Fault
                    </div>
                    <p class="text-justify ms-3">
                        If the Rider is found to be at fault in an accident, <b>all fines, penalties, excess insurance charges, and third-party costs</b>, whether imposed by authorities or the leasing company, shall be payable by the Rider. The Company reserves the right to recover such amounts as per UAE law.
                    </p>
                </div>
                
                <!-- Clause 4 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">4.</span> Green Paper (Police Report – At Fault)
                    </div>
                    <p class="text-justify ms-3">
                        Where a green paper is issued by the police, any <b>insurance excess, non-covered repair amount, or deductible</b> charged by the insurance provider shall be the sole responsibility of the Rider.
                    </p>
                </div>
                
                <!-- Clause 5 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">5.</span> Damage Due to Negligence or Misuse
                    </div>
                    <p class="text-justify ms-3">
                        Any damage caused to the bike due to <b>negligence, careless driving, misuse, violation of traffic rules, or non-compliance</b> with Company policies shall be repaired at the Rider's expense. This includes mechanical and cosmetic damage.
                    </p>
                </div>
                
                <!-- Clause 6 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">6.</span> Traffic Fines, Salik & Administrative Charges
                    </div>
                    <p class="text-justify ms-3">
                        The Rider shall be fully responsible for all <b>RTA traffic fines</b> incurred during the course of employment. In addition, an <b>administrative handling fee of AED 25 per fine</b> and <b>Salik charges of AED 0.5 per transaction</b> shall be applicable. These amounts may be deducted from the Rider's salary or dues, subject to legal limits.
                    </p>
                </div>
                
                <!-- Clause 7 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">7.</span> Impounded Bike
                    </div>
                    <p class="text-justify ms-3">
                        If the bike is impounded due to the Rider's actions, negligence, or violation of UAE laws, the Rider shall be responsible for <b>all impound-related costs</b>, including <b>daily rental charges for the entire impound period</b>, fines, and release fees.
                    </p>
                </div>
                
                <!-- Clause 8 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">8.</span> Right of Recovery
                    </div>
                    <p class="text-justify ms-3">
                        The Rider expressly authorizes the Company to <b>recover any outstanding amounts</b> arising under this policy from salary, end-of-service benefits, security deposits, or any other lawful dues, strictly in compliance with UAE labor Law.
                    </p>
                </div>
                
                <!-- Clause 9 -->
                <div class="clause-content">
                    <div class="clause-title">
                        <span class="clause-number">9.</span> Compliance with UAE Laws
                    </div>
                    <p class="text-justify ms-3">
                        The Rider agrees to operate the bike strictly in accordance with <b>UAE Traffic Laws, RTA regulations, insurance requirements, and Company policies</b>. Any breach shall attract disciplinary and financial consequences as permitted by law.
                    </p>
                </div>
            </div>
            
            <!-- Signatures -->
            <div class="row mt-5">
               <div class="col-6">
                  <p class="fw-bold">Express Fast Delivery Service Name & Signature</p>
               </div>
               <div class="col-6 text-center">
                  <p class="fw-bold">Fingerprint<br/>(Thumbprint of the right hand)</p>
                  <div class="border-bottom border-secondary mx-auto" style="height: 60px; margin: 5px 0 15px 0;"></div>
                  <p class="fw-bold">Name & Signature<br/>
                     <input type="text" class="editable-field form-control-sm text-center mx-auto" id="finalSignaturePage3" value="{{@$contract->rider->name}}" style="width: 90%; border: none;">
                  </p>
               </div>
            </div>
         </div>
      </div>

      <!-- Bootstrap JS Bundle -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

      <script type="text/javascript">
         // Store original values
         let originalValues = {};
         let isEditMode = false;
         
         // Initialize
         document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.editable-field').forEach(input => {
                if (input.id) {
                    originalValues[input.id] = input.value;
                }
            });
             // Store all original values
             document.querySelectorAll('.editable-div').forEach(div => {
                if (div.id) {
                    originalValues[div.id] = div.innerHTML;
                }
            });
             
             // Store checkbox states
             document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                 if (checkbox.id) {
                     originalValues[checkbox.id] = checkbox.checked;
                 }
             });
             
             // Set initial locked state
             lockDocument();
             
         });
         
         // Edit toggle button
         document.getElementById('editToggle').addEventListener('click', function() {
             if (isEditMode) {
                 lockDocument();
                 this.innerHTML = '<i class="fas fa-edit"></i> Enable Editing';
                 document.querySelector('.edit-mode-indicator').style.display = 'none';
             } else {
                 unlockDocument();
                 this.innerHTML = '<i class="fas fa-lock"></i> Disable Editing';
                 document.querySelector('.edit-mode-indicator').style.display = 'flex';
             }
             isEditMode = !isEditMode;
         });
         
         // Print button
         document.getElementById('printBtn').addEventListener('click', function() {
             // Lock document before printing
             lockDocument();
             isEditMode = false;
             document.getElementById('editToggle').innerHTML = '<i class="fas fa-edit"></i> Enable Editing';
             
             // Print the document
             window.print();
         });
         
         // Lock document function
         function lockDocument() {
             document.body.classList.remove('edit-mode');
             document.body.classList.add('locked');
             
             document.querySelectorAll('.editable-field, .editable-textarea').forEach(element => {
                 element.setAttribute('readonly', true);
                 element.style.pointerEvents = 'none';
                 element.classList.add('bg-light');
             });
             
             document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                 checkbox.disabled = true;
             });

             document.querySelectorAll('.editable-div').forEach(div => {
                div.setAttribute('contenteditable', 'false');
                div.style.pointerEvents = 'none';
                div.classList.add('bg-light');
            });
             
             document.querySelector('.edit-mode-indicator').style.display = 'none';
         }
         
         // Unlock document function
         function unlockDocument() {
             document.body.classList.add('edit-mode');
             document.body.classList.remove('locked');
             
             document.querySelectorAll('.editable-field, .editable-textarea').forEach(element => {
                 element.removeAttribute('readonly');
                 element.style.pointerEvents = 'auto';
                 element.classList.remove('bg-light');
             });

             document.querySelectorAll('.editable-div').forEach(div => {
                div.setAttribute('contenteditable', 'true');
                div.style.pointerEvents = 'auto';
                div.classList.remove('bg-light');
            });
             
             document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                 checkbox.disabled = false;
             });
             
             document.querySelector('.edit-mode-indicator').style.display = 'flex';
         }
         
         // After print event - keep document locked
         window.addEventListener('afterprint', function() {
             lockDocument();
             isEditMode = false;
             document.getElementById('editToggle').innerHTML = '<i class="fas fa-edit"></i> Enable Editing';
         });
         
      </script>
   </body>
</html>