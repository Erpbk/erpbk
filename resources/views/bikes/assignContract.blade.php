<!DOCTYPE html>
<html lang="en">
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Rider# {{$contract->rider->rider_id}} Bike Handing Over</title>
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

      <style type="text/css"> 
         /* ========== COMPACT STYLES FOR 2-PAGE PRINT ========== */
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
            <h2><i class="fas fa-motorcycle"></i> Bike Handover Contract</h2>
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
                    BIKE HANDOVER
                </h1>
            </div>
            <!-- Rider Data Section -->
            <div style="display: flex; justify-content: space-between; align-items: center;  margin-bottom: 8px;">
                <span><h2>Rider Details :</h2></span>
                <input type="text" style="width: 20%" class="editable-field" id="date" value="{{@$contract->note_date->format('Y-m-d')}}">
            </div>    
            
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
            <table style="width:100%; margin-bottom: 10px;">
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
            <p style="text-align: center; font-size: 9pt; line-height: 1.2; margin: 10px 0;">
               I have thoroughly Checked and understood the present condition and damage on the motorcycle. I will be responsible for any new damages and shall bear all expenses for repair of the motorcycle, I will be responsible for any new damages as per the terms and condition mentioned on the handing over Document.
            </p>
            
            
            <!-- Signatures (Page 1) - Headings BELOW input fields -->
            <div style="margin-top: 120px; padding-top: 20px; ">
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
            
            <!-- PAGE 2: DECLARATION ONLY -->
            
            <!-- Declaration Header -->
            <h1 style="margin: 15px 0 10px 0;">
               Declaration : <span style="float: right;">Date: <input type="text" class="editable-field" id="declarationDate" value="{{@$contract->note_date}}" style="width: 100px;"></span>
            </h1>
            
            <p class="s11" style="margin-bottom: 8px;">
               Confirmation of receipt of a "<b>Bike</b>": I hereby certify, I am 
            </p>
            
            <table style="border-collapse:collapse;width:100%; margin-bottom: 10px;" cellspacing="0">
               <tr>
                  <td style="width:15%;"><p class="s13">Name</p></td>
                  <td style="width:35%;"><input type="text" class="editable-field" id="declarationName" value="{{@$contract->rider->name}}"></td>
                  <td style="width:15%;"><p class="s13">Emirates I.D</p></td>
                  <td style="width:35%;"><input type="text" class="editable-field" id="declarationEmirateId" value="{{@$contract->rider->emirate_id}}"></td>
               </tr>
            </table>
            
            <p class="s11" style="margin: 8px 0;">
               I have received the following bike :</b>
            </p>
            
            <table style="border-collapse:collapse;width:100%; margin-bottom: 10px;" cellspacing="0">
               <tr>
                  <td style="width:15%;"><p class="s13"><span class="s14">T.C.No</span>.</p></td>
                  <td style="width:35%;"><input type="text" class="editable-field" id="declarationTcNo" value="{{@$contract->bike->traffic_file_number}}"></td>
                  <td style="width:15%;"><p class="s13">Bike Plate No.</p></td>
                  <td style="width:35%;"><input type="text" class="editable-field" id="declarationPlate" value="{{@$contract->bike->plate}}"></td>
               </tr>
               <tr>
                  <td><p class="s13">Model No.</p></td>
                  <td><input type="text" class="editable-field" id="declarationModel" value="{{@$contract->bike->model}}"></td>
                  <td><p class="s13">Bike</p></td>
                  <td><input type="text" class="editable-field" id="declarationBikeType" value="{{@$contract->bike->model_type}}"></td>
               </tr>
               <tr>
                  <td><p class="s13">Engine No.</p></td>
                  <td><input type="text" class="editable-field" id="declarationEngine" value="{{@$contract->bike->engine}}"></td>
                  <td><p class="s13">Chassis No.</p></td>
                  <td><input type="text" class="editable-field" id="declarationChassis" value="{{@$contract->bike->chassis_number}}"></td>
               </tr>
            </table>
            
            <!-- Declaration Text Area -->
            <div style="border: 1px solid #000; padding: 8px; margin: 10px 0; min-height: 180px;">
               <div class="editable-div" id="declarationText" contenteditable="false" style="min-height: 170px; border: none; padding: 8px; font-family: inherit; font-size: inherit;">
I undertake to preserve and use the bike for the purpose for which I received it and acknowledge that I am fully responsible for its maintenance and care. I commit to return the bike to the company upon its request or at the end of the contract, in the same condition as I received it.

In the event of non-return, I agree to pay an amount of <b>AED 8,000</b> for the bike and <b>AED 2,000</b> for Noon Assets (Delivery Bag & Noon Uniform with accessories), totaling <b>AED 10,000</b>.

I acknowledge that it is my responsibility to return the bike to the company upon termination of employment. Should I fail to do so, I shall be liable to pay the monthly rent of <b>AED 600</b>, any RTA fines, Salik charges, and any damages to the bike until it is returned in good working condition.
               </div>
            </div>
            
            <p style="margin: 15px 0 5px 0;"><br/></p>
            
            <h2 style="margin-bottom: 5px;">IN WITNESS WHEREOF</h2>
            
            <p class="s18" style="margin-bottom: 15px;">
               the Parties have executed this Contract as of the date first indicated above
            </p>
            
            <!-- Final Signatures -->
            <table style="width: 100%; margin-top: 20px; border: none !important;">
               <tr>
                  <td style="width:50%; vertical-align: top; border: none !important;">
                     <p><b>Express Fast Delivery Service Name & Signature</b></p>
                     
                  </td>
                  <td style="width:50%; vertical-align: top; padding-left: 20px; text-align: center;border: none !important;">
                     <p><b>Fingerprint</b><br/>(Thumbprint of the right hand)</p>
                     <div style="height: 60px; border-bottom: 1px solid #ccc; margin: 5px 0 15px 0;"></div>
                     <p style="font-weight: bold;">Name & Signature<br/>
                        <input type="text" class="editable-field" id="finalSignature" value="{{@$contract->rider->name}}" style="font-weight: bold;  width: 90%; border: none; background: transparent; text-align: center;">
                     </p>
                  </td>
               </tr>
            </table>
         </div>
      </div>

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
             });
             
             document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                 checkbox.disabled = true;
             });

             document.querySelectorAll('.editable-div').forEach(div => {
                div.setAttribute('contenteditable', 'false');
                div.style.pointerEvents = 'none';
                div.style.backgroundColor = '#f8f9fa'; // Visual feedback
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