<!DOCTYPE html>
<html lang="en">
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Rider# {{$contract->rider->rider_id}} Bike Handing Over</title>
      <style type="text/css"> 
         /* ========== COMPACT STYLES FOR 2-PAGE PRINT ========== */
         * {margin:0; padding:0; text-indent:0; box-sizing: border-box; }
         body { 
            font-family: 'Segoe UI', Calibri, sans-serif; 
            line-height: 1.3;
            background: #f5f7fa;
            padding: 10px;
            font-size: 9pt; 
         }
         
         .document-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
         }
         
         .document-content {
            padding: 15px; 
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
         
         .button-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
         }
         
         .control-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 12px;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 6px;
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

         /* New styles for equipment form */
         .equipment-table {
            width: 100%;
            margin: 15px 0;
         }
         
         .equipment-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
         }
         
         .equipment-table td {
            text-align: center;
         }
         
         .equipment-table .item-name {
            text-align: left;
            font-weight: bold;
         }
         
         .qty-input {
            width: 80px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 3px;
            font-size: 9pt;
         }
         
         .declaration-box {
            border: 1px solid #000;
            padding: 10px;
            margin: 15px 0;
            min-height: 80px;
            font-size: 9pt;
            line-height: 1.3;
         }
         
         .signature-line {
            width: 60%;
            margin: 30px 0 5px 0;
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
            
            .document-content {
                padding: 5px !important;
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
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   </head>
   <body>
      <div class="document-container">
         <!-- Control Panel -->
         <div class="control-panel no-print">
            <h2><i class="fas fa-motorcycle"></i> Bike Return Contract</h2>
            <div class="button-group">
               <button id="editToggle" class="control-btn edit-btn">
                  <i class="fas fa-edit"></i> Enable Editing
               </button>
               <button id="printBtn" class="control-btn print-btn">
                  <i class="fas fa-print"></i> Print
               </button>
            </div>
            <div class="edit-mode-indicator">
               <i class="fas fa-pencil-alt"></i> Edit Mode
            </div>
         </div>
         
         <div class="document-content">
            <!-- PAGE 1: RIDER DATA & BIKE DETAILS -->
            <div style="text-align: center; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #000;">
                <h1 style="font-size: 16pt; font-weight: bold; margin: 0; color: #000;">
                    BIKE RETURN
                </h1>
            </div>
            <!-- Rider Data Section -->
            <h2 style="padding-left: 8pt; margin-bottom: 8px;">Rider Details :</h2>
            
            <table style="border-collapse:collapse;width:100%; margin-bottom: 10px;">
              <tr>
                  <th style="width: 15%;">Rider Status:</th>
                  <td style="width: 20%;">
                     <input type="text" class="editable-field" id="riderStatus" value="{{App\Helpers\General::RiderStatus(@$contract->rider->status)}}">
                  </td>
                  <th style="width: 15%; padding-left: 20px;">Supervisor:</th>
                  <td style="width: 20%;">
                     <input type="text" class="editable-field" id="supervisor" value="{{@$contract->rider->fleet_supervisor}}">
                  </td>
                  <th style="width: 10%; padding-left: 20px;">Date:</th>
                  <td style="width: 20%;">
                     <input type="text" class="editable-field" id="date" value="{{@$contract->return_date->format('Y-m-d')}}">
                  </td>
              </tr>
            </table>
            
            <!-- Personal Details Table -->
            <table style="border-collapse:collapse;width:100%; margin-bottom: 10px;" cellspacing="0">
               <tr>
                  <td style="width:15%;"><p class="s3">Name</p></td>
                  <td style="width:35%;"><input type="text" class="editable-field" id="name" value="{{@$contract->rider->name}}"></td>
                  <td style="width:15%;"><p class="s3">RIDER I.D.</p></td>
                  <td style="width:35%;"><input type="text" class="editable-field" id="riderId" value="{{@$contract->rider->rider_id}}"></td>
               </tr>
               <tr>
                  <td><p class="s3">Emirates ID.</p></td>
                  <td><input type="text" class="editable-field" id="emirateId" value="{{@$contract->rider->emirate_id}}"></td>
                  <td><p class="s3">Passport No.</p></td>
                  <td><input type="text" class="editable-field" id="passport" value="{{@$contract->rider->passport}}"></td>
               </tr>
               <tr>
                  <td><p class="s3">Phone No.</p></td>
                  <td><input type="text" class="editable-field" id="phone" value="{{@$contract->rider->personal_contact}}"></td>
                  <td><p class="s3">License No.</p></td>
                  <td><input type="text" class="editable-field" id="license" value="{{@$contract->rider->license_no}}"></td>
               </tr>
               <tr>
                  <td><p class="s3">Email I.D.</p></td>
                  <td><input type="text" class="editable-field" id="email" value="{{@$contract->rider->personal_email}}"></td>
                  <td><p class="s3">Emirate.</p></td>
                  <td><input type="text" class="editable-field" id="emirate" value="{{@$contract->rider->emirate_hub}}"></td>
               </tr>
            </table>
            
            <!-- Mobile Sim Detail -->
            <h2 style="margin-top: 8px; margin-bottom: 4px;">Sim Detail :</h2>
            <table style="border-collapse:collapse;width:100%; margin-bottom: 10px;" cellspacing="0">
               <tr>
                  <td style="width:30%;">
                     <span class="checkbox-container">
                        <input type="checkbox" id="companySim">
                        <label for="companySim" class="s3">Company Sim Number.</label>
                     </span>
                  </td>
                  <td style="width:25%;"><input type="text" class="editable-field" id="simNumber" value="{{@$contract->rider->sims->sim_number}}"></td>
                  <td style="width:15%;"><p class="s3">EMI Number.</p></td>
                  <td style="width:30%;"><input type="text" class="editable-field" id="simEmi" value="{{@$contract->rider->sims->sim_emi}}"></td>
               </tr>
            </table>
            
            <!-- Details of Bike -->
            <h2 style="margin-top: 8px; margin-bottom: 4px;">Bike Details :</h2>
            <table style="border-collapse:collapse;width:100%; margin-bottom: 10px;" cellspacing="0">
               <tr>
                  <td style="width:15%;"><p class="s3"><span class="s7">T.C.No</span>.</p></td>
                  <td style="width:30%;"><input type="text" class="editable-field" id="trafficFileNumber" value="{{@$contract->bike->traffic_file_number}}"></td>
                  <td style="width:20%;"><p class="s3">Bike Plate No.</p></td>
                  <td style="width:35%;"><input type="text" class="editable-field" id="plate" value="{{@$contract->bike->plate}}"></td>
               </tr>
               <tr>
                  <td><p class="s3">Model No.</p></td>
                  <td><input type="text" class="editable-field" id="model" value="{{@$contract->bike->model}}"></td>
                  <td><p class="s3">Bike</p></td>
                  <td><input type="text" class="editable-field" id="modelType" value="{{@$contract->bike->model_type}}"></td>
               </tr>
               <tr>
                  <td><p class="s3">Engine No.</p></td>
                  <td><input type="text" class="editable-field" id="engine" value="{{@$contract->bike->engine}}"></td>
                  <td><p class="s3">Chassis No.</p></td>
                  <td><input type="text" class="editable-field" id="chassis" value="{{@$contract->bike->chassis_number}}"></td>
               </tr>
               <tr>
                  <td colspan="4" style="text-align: center; padding: 4px;">
                     <span class="checkbox-container" style="justify-content: center;">
                        <input type="checkbox" id="motorcycle">
                        <label for="motorcycle" class="s3">Motorcycle</label>
                     </span>
                  </td>
               </tr>
            </table>
            
            <!-- Compact Checkbox Grid -->
            <table style="border-collapse:collapse; width:100%; margin-bottom: 10px;" cellspacing="0">
               <tr>
                  <td style="width:33%; vertical-align: top;">
                     <table style="width:100%;">
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="dent"> <label class="s3">Dent</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="scratch"> <label class="s3">Scratch</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="majorDamage"> <label class="s3">Major Damage</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="deliveryBox"> <label class="s3">Delivery Box</label></span></td></tr>
                     </table>
                  </td>
                  <td style="width:33%; vertical-align: top;">
                     <table style="width:100%;">
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="mobileHolder"> <label class="s3">Mobile Holder</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="toolKit"> <label class="s3">Tool Kit</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="reflectingSticker"> <label class="s3">Reflecting Sticker</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="motorcycleSeat"> <label class="s3">Motorcycle Seat</label></span></td></tr>
                     </table>
                  </td>
                  <td style="width:33%; vertical-align: top;">
                     <table style="width:100%;">
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="crashGuard"> <label class="s3">Crash Guard</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="helmet"> <label class="s3">Helmet</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="stand"> <label class="s3">Stand</label></span></td></tr>
                        <tr><td class="compact-cell"><span class="checkbox-container"><input type="checkbox" id="rtaFine"> <label class="s3">RTA Fine</label></span></td></tr>
                     </table>
                  </td>
               </tr>
            </table>
            
            <!-- Additional checkboxes in single row -->
            <table style="width:100%; margin-bottom: 60px !important;">
               <tr>
                  <td style="width:33%;" class="compact-cell">
                     <span class="checkbox-container"><input type="checkbox" id="fuelCard"> <label class="s3">Fuel Card</label></span>
                  </td>
                  <td style="width:33%;" class="compact-cell">
                     <span class="checkbox-container"><input type="checkbox" id="salik"> <label class="s3">Salik</label></span>
                  </td>
                  <td style="width:33%;" class="compact-cell">
                     <!-- Empty for alignment -->
                  </td>
               </tr>
            </table>
            
            <!-- Remarks -->
            <h2 style="text-align: center; margin: 8px 0;">Remarks</h2>
            <div style=" margin-bottom: 10px; min-height: 170px; padding: 0;">
               <table style="width: 100%; border-collapse: collapse; height: 170px;">
                   <tr><td style="border-bottom: 1px solid #ccc; height: 20px; padding: 0 10px;">&nbsp;</td></tr>
                   <tr><td style="border-bottom: 1px solid #ccc; height: 20px; padding: 0 10px;">&nbsp;</td></tr>
                   <tr><td style="border-bottom: 1px solid #ccc; height: 20px; padding: 0 10px;">&nbsp;</td></tr>
                   <tr><td style="border-bottom: 1px solid #ccc; height: 20px; padding: 0 10px;">&nbsp;</td></tr>
                   <tr><td style="border-bottom: 1px solid #ccc; height: 20px; padding: 0 10px;">&nbsp;</td></tr>
                   <tr><td style="border-bottom: 1px solid #ccc; height: 20px; padding: 0 10px;">&nbsp;</td></tr>
                   <tr><td style="border-bottom: 1px solid #ccc; height: 20px; padding: 0 10px;">&nbsp;</td></tr>
                   <tr><td style="height: 20px; padding: 0 10px;">&nbsp;</td></tr> <!-- Last line no border -->
               </table>
            </div>
            
            <!-- Declaration Text -->
            <p>
            </p>
            
            <!-- Signatures (Page 1) - Headings BELOW input fields -->
            <div style="margin-top: 120px !important; padding-top: 20px; ">
               <table style="width: 100%; border: none;">
                  <tr>
                     <td style="width:33%; text-align: center; border: none !important;">
                        <div style="height: 1px; border-bottom: 1px solid #000; width: 80%; margin: 0 auto 10px auto;"></div>
                        <input type="text" class="editable-field" id="mechanic" value="Muhammad Sufyan Akbar Ali" style="text-align: center; width: 80%; border: none; font-size: 9pt; margin-bottom: 5px;">
                        <p style="margin-top: 5px; font-weight: bold;">Motor Mechanic</p>
                     </td>
                     <td style="width:34%; text-align: center; border: none !important;">
                        <div style="height: 1px; border-bottom: 1px solid #000; width: 80%; margin: 0 auto 10px auto;"></div>
                        <input type="text" class="editable-field" id="riderSignature" value="{{@$contract->rider->name}}" style="text-align: center; width: 80%; border: none; font-size: 9pt; margin-bottom: 5px;">
                        <p style="margin-top: 5px; font-weight: bold;">Rider Signature</p>
                     </td>
                     <td style="width:33%; text-align: center; border: none !important;">
                        <div style="height: 1px; border-bottom: 1px solid #000; width: 80%; margin: 0 auto 10px auto;"></div>
                        <input type="text" class="editable-field" id="supervisorSignature" value="{{@$contract->rider->fleet_supervisor}}" style="text-align: center; width: 80%; border: none; font-size: 9pt; margin-bottom: 5px;">
                        <p style="margin-top: 5px; font-weight: bold;">Supervisor Signature</p>
                     </td>
                  </tr>
               </table>
            </div>
            
            <!-- PAGE BREAK -->
            <div class="page-break"></div>
            
            <!-- PAGE 2: EQUIPMENT RETURN FORM -->
            <div style="text-align: center; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #000;">
                <h1 style="font-size: 16pt; font-weight: bold; margin: 0; color: #000;">
                    Rider Off-boarding Form
                </h1>
                <h2 style="font-size: 12pt; margin: 5px 0 0 0;">Equipment Return - Check List</h2>
            </div>
            
            <!-- Equipment Return Table -->
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">S1 No.</th>
                        <th style="width: 50%; text-align: left;">Item List</th>
                        <th style="width: 20%;">Return Qty</th>
                        <th style="width: 20%;">Unreturn Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td class="item-name">Branded T-Shirts</td>
                        <td><input type="text" class="editable-field qty-input" id="item1_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item1_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td class="item-name">Pants</td>
                        <td><input type="text" class="editable-field qty-input" id="item2_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item2_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td class="item-name">Knee and Arm Guard</td>
                        <td><input type="text" class="editable-field qty-input" id="item3_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item3_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td class="item-name">Cap</td>
                        <td><input type="text" class="editable-field qty-input" id="item4_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item4_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td class="item-name">Gloves</td>
                        <td><input type="text" class="editable-field qty-input" id="item5_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item5_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td class="item-name">Safety Helmet</td>
                        <td><input type="text" class="editable-field qty-input" id="item6_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item6_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td class="item-name">Snood</td>
                        <td><input type="text" class="editable-field qty-input" id="item7_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item7_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td class="item-name">Winter Jacket</td>
                        <td><input type="text" class="editable-field qty-input" id="item8_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item8_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td class="item-name">Thermal Bags Big</td>
                        <td><input type="text" class="editable-field qty-input" id="item9_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item9_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td class="item-name">Thermal Bags Small</td>
                        <td><input type="text" class="editable-field qty-input" id="item10_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item10_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td class="item-name"><input type="text" class="editable-field" id="item11_name" value="" style="width: 100%; border: none; background: transparent; font-weight: bold;" ></td>
                        <td><input type="text" class="editable-field qty-input" id="item11_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item11_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td class="item-name"><input type="text" class="editable-field" id="item12_name" value="" style="width: 100%; border: none; background: transparent; font-weight: bold;" ></td>
                        <td><input type="text" class="editable-field qty-input" id="item12_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item12_unreturn" value=""></td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td class="item-name"><input type="text" class="editable-field" id="item13_name" value="" style="width: 100%; border: none; background: transparent; font-weight: bold;" ></td>
                        <td><input type="text" class="editable-field qty-input" id="item13_return" value=""></td>
                        <td><input type="text" class="editable-field qty-input" id="item13_unreturn" value=""></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Note -->
            <p style="font-style: italic; margin: 10px 0; font-size: 9pt; color: red;">
                Note: This form must be signed and stamped by the partner to be valid.
            </p>
            
            <!-- Declaration -->
            <div class="declaration-box ">
                I, the undersigned hereby declare that I have returned above-mentioned equipment and assure that any equipment will not be re-use, re-sell or any form of transaction which will be in conflict of interest/reputation of Keeta.
            </div>
            
            <!-- Rider Information -->
            <table style="width: 100%; margin: 20px 0;">
                <tr>
                    <td style="width: 40%;">Delivery Agent (Rider) ID:</td>
                    <td style="width: 60%;"><input type="text" class="editable-field" id="offboarding_rider_id" value="{{@$contract->rider->rider_id}}" style="width: 80%;"></td>
                </tr>
                <tr>
                    <td>Date: / /</td>
                    <td><input type="text" class="editable-field" id="offboarding_date" value="{{@$contract->return_date->format('Y-m-d')}}" style="width: 80%;"></td>
                </tr>
                <tr>
                    <td>Contractor Partner:</td>
                    <td><input type="text" class="editable-field" id="contractor_partner" value="" style="width: 80%;"></td>
                </tr>
                <tr>
                    <td>Rider Name:</td>
                    <td><input type="text" class="editable-field" id="offboarding_rider_name" value="{{@$contract->rider->name}}" style="width: 80%;"></td>
                </tr>
                <tr>
                    <td>Delivery mode:</td>
                    <td>
                        <span class="checkbox-container" style="margin-bottom: 2px; margin-right: 15px;">
                            <input type="checkbox" id="delivery_bike"> <label>Bike</label>
                        </span>
                        <span class="checkbox-container" style="margin-bottom: 2px; margin-right: 15px;">
                            <input type="checkbox" id="delivery_car"> <label>Car</label>
                        </span>
                        <span class="checkbox-container" style="margin-bottom: 2px; margin-right: 15px;">
                            <input type="checkbox" id="delivery_walker"> <label>Walker</label>
                        </span>
                        <span class="checkbox-container">
                            <input type="checkbox" id="delivery_cyclist"> <label>Cyclist</label>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Signature:</td>
                    <td><div class="signature-line"></div></td>
                </tr>
            </table>
            
            <!-- Partner and Equipment Team Signatures -->
            <div style="margin-top: 40px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <p><strong>Partner Sign. and Stamp:</strong></p>
                            <div style="height: 40px;  margin: 5px 0 15px 0;"></div>
                            <p>Date: <input type="text" class="editable-field" id="partner_date" value="" style="width: 100px;"></p>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding-left: 30px;">
                            <p><strong>Equipment Team Sign:</strong></p>
                            <div style="height: 40px;  margin: 5px 0 15px 0;"></div>
                            <p>Date: <input type="text" class="editable-field" id="equipment_date" value="" style="width: 100px;"></p>
                        </td>
                    </tr>
                </table>
            </div>
         </div>
      </div>

      <script type="text/javascript">
         // Store original values
         let originalValues = {};
         let isEditMode = false;
         
         // Initialize
         document.addEventListener('DOMContentLoaded', function() {
            // Store all original values from both pages
            document.querySelectorAll('.editable-field').forEach(input => {
                if (input.id) {
                    originalValues[input.id] = input.value;
                }
            });
             
            document.querySelectorAll('.editable-div').forEach(div => {
                if (div.id) {
                    originalValues[div.id] = div.innerHTML;
                }
            });
             
            // Store checkbox states from both pages
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
             
             document.querySelectorAll('.editable-field, .editable-textarea, .qty-input').forEach(element => {
                 element.setAttribute('readonly', true);
                 element.style.pointerEvents = 'none';
             });
             
             document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                 checkbox.disabled = true;
             });

             document.querySelectorAll('.editable-div').forEach(div => {
                div.setAttribute('contenteditable', 'false');
                div.style.pointerEvents = 'none';
                div.style.backgroundColor = '#f8f9fa';
            });
             
             document.querySelector('.edit-mode-indicator').style.display = 'none';
         }
         
         // Unlock document function
         function unlockDocument() {
             document.body.classList.add('edit-mode');
             document.body.classList.remove('locked');
             
             document.querySelectorAll('.editable-field, .editable-textarea, .qty-input').forEach(element => {
                 element.removeAttribute('readonly');
                 element.style.pointerEvents = 'auto';
             });

             document.querySelectorAll('.editable-div').forEach(div => {
                div.setAttribute('contenteditable', 'true');
                div.style.pointerEvents = 'auto';
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